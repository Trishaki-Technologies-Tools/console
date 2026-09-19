import openpyxl, datetime, json, mysql.connector

# Database connection
conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='u164024082_accounts'
)
conn.autocommit = False
cursor = conn.cursor(dictionary=True)

try:
    print("1. Preparing and cleaning previous test invoices, receipts, and transactions...")
    cursor.execute("SET FOREIGN_KEY_CHECKS = 0")
    cursor.execute("TRUNCATE TABLE invoices")
    cursor.execute("TRUNCATE TABLE receipts")
    cursor.execute("DELETE FROM transactions WHERE reference_table = 'invoices'")
    cursor.execute("SET FOREIGN_KEY_CHECKS = 1")
    conn.commit()
    print("   Tables cleaned.")

    # Load clients map from DB
    cursor.execute("SELECT id, name, phone, email, college_name, department FROM clients")
    db_clients = {}
    for c in cursor.fetchall():
        phone_key = str(c['phone']).replace(' ', '').replace('-', '').strip()
        db_clients[phone_key] = c

    # Load Excel file
    excel_path = r'c:\xampp\htdocs\console\Accounts\BCA-2026-batch-payments-data.xlsx'
    wb = openpyxl.load_workbook(excel_path)
    sheet = wb['interns_payments_march']

    year = 2026
    receipt_counter = 1
    invoice_counter = 1

    total_invoices_created = 0
    total_receipts_created = 0
    total_transactions_created = 0

    print("2. Processing 67 student records from Excel...")

    for r in range(2, 69):
        row = [sheet.cell(r, c).value for c in range(1, 16)]
        name = str(row[0]).strip()
        if not name:
            continue
        
        raw_phone = str(row[1] or '').replace(' ', '').replace('-', '').strip()
        total_payable = float(row[2] or 0)
        
        inv_date = row[3]
        if isinstance(inv_date, datetime.datetime):
            inv_date_str = inv_date.strftime('%Y-%m-%d')
        else:
            inv_date_str = str(inv_date)[:10]

        # Get or create client
        if raw_phone in db_clients:
            client_id = db_clients[raw_phone]['id']
            # Update client name to match sheet
            cursor.execute("UPDATE clients SET name = %s WHERE id = %s", (name, client_id))
        else:
            cursor.execute(
                "INSERT INTO clients (name, phone, client_type, college_name, department) VALUES (%s, %s, 'Student', 'BCA College', 'BCA')",
                (name, raw_phone)
            )
            client_id = cursor.lastrowid
            db_clients[raw_phone] = {'id': client_id, 'name': name, 'phone': raw_phone}

        # Collect installments for this student
        installments = []
        
        # Receipt 1
        r1_amt = row[4]
        r1_mode = str(row[5] or 'Online').strip()
        if r1_amt is not None and float(r1_amt) > 0:
            installments.append({'date': inv_date_str, 'amt': float(r1_amt), 'mode': r1_mode})

        # Receipt 2
        r2_date = row[6]
        r2_amt = row[7]
        r2_mode = str(row[8] or 'Online').strip()
        if r2_amt is not None and float(r2_amt) > 0:
            d_str = r2_date.strftime('%Y-%m-%d') if isinstance(r2_date, datetime.datetime) else str(r2_date)[:10]
            installments.append({'date': d_str, 'amt': float(r2_amt), 'mode': r2_mode})

        # Receipt 3
        r3_date = row[9]
        r3_amt = row[10]
        r3_mode = str(row[11] or 'Online').strip()
        if r3_amt is not None and float(r3_amt) > 0:
            d_str = r3_date.strftime('%Y-%m-%d') if isinstance(r3_date, datetime.datetime) else str(r3_date)[:10]
            installments.append({'date': d_str, 'amt': float(r3_amt), 'mode': r3_mode})

        # Receipt 4
        r4_date = row[12]
        r4_amt = row[13]
        r4_mode = str(row[14] or 'Online').strip()
        if r4_amt is not None and float(r4_amt) > 0:
            d_str = r4_date.strftime('%Y-%m-%d') if isinstance(r4_date, datetime.datetime) else str(r4_date)[:10]
            installments.append({'date': d_str, 'amt': float(r4_amt), 'mode': r4_mode})

        # 1. Create the initial Bill in invoices table
        invoice_items = [
            {
                'description': 'Internship cum Training Program Fee',
                'amount': total_payable,
                'totalInclTax': total_payable,
                'paidAmt': total_payable,
                'tax': 0,
                'charges': total_payable,
                'date': inv_date_str
            }
        ]
        invoice_items_json = json.dumps(invoice_items)

        # Insert bill record with invoice_no = NULL
        cursor.execute(
            """
            INSERT INTO invoices (invoice_no, client_id, type, items, original_total_payable, cumulative_total_paid, invoice_date, status)
            VALUES (NULL, %s, 'non-gst', %s, %s, 0.00, %s, 'unpaid')
            """,
            (client_id, invoice_items_json, total_payable, inv_date_str)
        )
        invoice_id = cursor.lastrowid
        total_invoices_created += 1

        # 2. Process each installment progressively
        running_paid = 0.0
        allocated_invoice_no = None
        final_settlement_date = inv_date_str

        for inst in installments:
            inst_amt = inst['amt']
            inst_mode = inst['mode']
            inst_date = inst['date']
            running_paid += inst_amt

            receipt_no = f"RECP-{year}-{receipt_counter:03d}"
            receipt_counter += 1

            receipt_items = [
                {
                    'description': 'Internship cum Training Program Fee',
                    'paymentMode': inst_mode,
                    'date': inst_date,
                    'amount': total_payable,
                    'totalInclTax': total_payable,
                    'paidAmt': inst_amt,
                    'tax': 0,
                    'charges': total_payable
                }
            ]
            receipt_items_json = json.dumps(receipt_items)

            is_settled = (running_paid >= total_payable - 0.01)
            rec_status = 'paid' if is_settled else 'partially_paid'

            # Insert receipt
            cursor.execute(
                """
                INSERT INTO receipts (receipt_no, client_id, invoice_id, invoice_no, type, items, original_total_payable, cumulative_total_paid, receipt_date, status)
                VALUES (%s, %s, %s, NULL, 'non-gst', %s, %s, %s, %s, %s)
                """,
                (receipt_no, client_id, invoice_id, receipt_items_json, total_payable, running_paid, inst_date, rec_status)
            )
            receipt_id = cursor.lastrowid
            total_receipts_created += 1

            if is_settled and not allocated_invoice_no:
                allocated_invoice_no = f"TSK-{year}-{invoice_counter:03d}"
                invoice_counter += 1
                final_settlement_date = inst_date

        # 3. Update Invoice to 100% paid and allocate Invoice Number
        cursor.execute(
            """
            UPDATE invoices 
            SET invoice_no = %s, cumulative_total_paid = %s, invoice_date = %s, status = 'paid'
            WHERE id = %s
            """,
            (allocated_invoice_no, running_paid, final_settlement_date, invoice_id)
        )

        # 4. Update all linked receipts with the allocated Tax Invoice number
        cursor.execute(
            """
            UPDATE receipts 
            SET invoice_no = %s 
            WHERE invoice_id = %s
            """,
            (allocated_invoice_no, invoice_id)
        )

        # 5. Insert transaction ledger entries for all receipts of this invoice
        cursor.execute("SELECT id, receipt_no, items, receipt_date FROM receipts WHERE invoice_id = %s ORDER BY id ASC", (invoice_id,))
        for rec_row in cursor.fetchall():
            r_no = rec_row['receipt_no']
            r_date = rec_row['receipt_date']
            r_items = json.loads(rec_row['items'])
            r_amt = r_items[0]['paidAmt']
            tx_desc = f"Invoice Payment ({r_no}): {allocated_invoice_no} ({name})"

            cursor.execute(
                """
                INSERT INTO transactions (type, amount, date, reference_id, reference_table, description)
                VALUES ('income', %s, %s, %s, 'invoices', %s)
                """,
                (r_amt, r_date, invoice_id, tx_desc)
            )
            total_transactions_created += 1

    conn.commit()
    print("3. Import completed successfully!")
    print(f"   Invoices created & allocated: {total_invoices_created} (TSK-{year}-001 to TSK-{year}-{total_invoices_created:03d})")
    print(f"   Payment Receipts created:    {total_receipts_created} (RECP-{year}-001 to RECP-{year}-{total_receipts_created:03d})")
    print(f"   Ledger Transactions created: {total_transactions_created}")

except Exception as e:
    conn.rollback()
    print("ERROR DURING IMPORT:", e)
    raise e
finally:
    cursor.close()
    conn.close()
