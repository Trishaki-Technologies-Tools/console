# Invoice System Setup Instructions

## Quick Setup (3 Steps)

### Step 1: Test Database Connection
Open in browser:
```
http://your-domain.com/Accounts/api/test_db.php
```

This will show:
- ✅ Database connection status
- ✅ Which tables exist
- ✅ Table structure
- ❌ Any missing tables

### Step 2: Create Invoice Tables
If tables are missing, run the migration:

**Option A: Using phpMyAdmin**
1. Login to phpMyAdmin
2. Select database: `u164024082_console`
3. Go to "SQL" tab
4. Copy and paste contents of `add_invoice_tables.sql`
5. Click "Go"

**Option B: Using MySQL Command Line**
```bash
mysql -h 82.25.121.32 -u u164024082_console -p u164024082_console < add_invoice_tables.sql
```
Enter password: `Trishaki@tech-consoledb#304`

**Option C: Using cPanel File Manager**
1. Go to cPanel → phpMyAdmin
2. Select `u164024082_console` database
3. Click Import
4. Choose `add_invoice_tables.sql`
5. Click Go

### Step 3: Verify Setup
Refresh the test page:
```
http://your-domain.com/Accounts/api/test_db.php
```

You should see:
```json
{
    "connection": true,
    "tables": {
        "customers": "exists",
        "customers_count": 0,
        "invoices": "exists",
        "invoices_count": 0
    },
    "success": true,
    "message": "Database is ready for invoice system!"
}
```

## What's Changed?

### Before (localStorage)
- Invoices stored in browser
- Lost when clearing cache
- Not shared between devices
- No backup

### After (Database)
- Invoices stored in MySQL
- Persistent and secure
- Accessible from anywhere
- Easy to backup
- Correct invoice numbering even after deletions

## Invoice Numbering

### New Invoice
```
TSK-2026-001
TSK-2026-002
TSK-2026-003
```

### Part Payments
```
First invoice:  TSK-2026-001 (₹5,000 total, ₹1,000 paid)
Second payment: TSK-2026-001/P1 (₹2,000 paid)
Third payment:  TSK-2026-001/P2 (₹2,000 paid, fully paid)
```

## Features

✅ Customer database with phone number lookup
✅ Automatic invoice numbering (TSK-YYYY-XXX)
✅ Part payment tracking (/P1, /P2, etc.)
✅ GST and Non-GST invoices
✅ Balance due calculation
✅ Customer history
✅ Professional A4 print format

## Troubleshooting

### "Database connection failed"
- Check if database credentials in `api/config.php` are correct
- Verify database server is accessible
- Check if database exists

### "Tables missing"
- Run `add_invoice_tables.sql` migration script
- Check if you have CREATE TABLE permissions

### "Invoice not saving"
- Check browser console for errors
- Verify `api/save_invoice.php` is accessible
- Check database permissions (INSERT, UPDATE)

### "Invoice numbers not sequential"
- This is normal after deleting invoices
- System counts total invoices in database
- To reset: DELETE FROM invoices; (starts from 001 again)

## Database Structure

### customers table
```sql
id              INT (Primary Key)
name            VARCHAR(255)
phone           VARCHAR(20) UNIQUE
email           VARCHAR(255)
gst_number      VARCHAR(50)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### invoices table
```sql
id                      INT (Primary Key)
invoice_no              VARCHAR(50) UNIQUE
customer_id             INT (Foreign Key)
type                    ENUM('gst', 'non-gst')
items                   JSON
original_total_payable  DECIMAL(10,2)
cumulative_total_paid   DECIMAL(10,2)
invoice_date            DATE
created_at              TIMESTAMP
```

## Support

If you encounter any issues:
1. Check `api/test_db.php` for diagnostics
2. Check browser console for JavaScript errors
3. Check database error logs
4. Verify all API files are uploaded correctly

## Security Notes

- Database credentials are in `api/config.php`
- Keep this file secure (already protected by .htaccess)
- Regular backups recommended
- Use HTTPS in production
