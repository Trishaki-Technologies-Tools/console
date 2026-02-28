# Invoice Database Setup Guide

## Overview
The invoice system now uses MySQL database for persistent storage instead of browser localStorage. This ensures data is preserved even if browser data is cleared and allows proper invoice numbering across sessions.

## Database Tables

### 1. customers
Stores customer information:
- `id` - Primary key
- `name` - Customer name
- `phone` - Unique phone number (used as identifier)
- `email` - Email address (optional)
- `gst_number` - GST number (optional)
- `created_at` - Timestamp
- `updated_at` - Timestamp

### 2. invoices
Stores all invoices:
- `id` - Primary key
- `invoice_no` - Unique invoice number (TSK-YYYY-XXX or TSK-YYYY-XXX/P1, /P2, etc.)
- `customer_id` - Foreign key to customers table
- `type` - 'gst' or 'non-gst'
- `items` - JSON field containing invoice line items
- `original_total_payable` - Original total amount (for part payments)
- `cumulative_total_paid` - Total paid so far (for part payments)
- `invoice_date` - Date of invoice
- `created_at` - Timestamp

## Setup Instructions

### Step 1: Run Database Migration
Execute the updated `database.sql` file in your MySQL database:

```bash
mysql -u your_username -p finance_dashboard < database.sql
```

Or import via phpMyAdmin:
1. Open phpMyAdmin
2. Select `finance_dashboard` database
3. Go to Import tab
4. Choose `database.sql` file
5. Click Go

### Step 2: Verify Database Connection
Make sure `api/config.php` has correct database credentials:

```php
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "finance_dashboard";
```

### Step 3: Test the System
1. Open the Accounts application
2. Create a new invoice
3. Check the database to verify the invoice was saved
4. Try creating a part payment to test invoice numbering

## Invoice Numbering Logic

### New Invoice
- Format: `TSK-YYYY-XXX`
- Example: `TSK-2026-001`, `TSK-2026-002`
- Sequential numbering based on total invoices in database

### Part Payment
- Format: `TSK-YYYY-XXX/P1`, `TSK-YYYY-XXX/P2`, etc.
- Example: 
  - First invoice: `TSK-2026-001` (₹5,000 total, ₹1,000 paid)
  - Second payment: `TSK-2026-001/P1` (₹2,000 paid)
  - Third payment: `TSK-2026-001/P2` (₹2,000 paid, balance cleared)

### How It Works
1. System checks if customer has existing invoices with balance due
2. If yes, creates part payment with /P suffix
3. If no, creates new invoice with sequential number
4. Invoice numbers are generated server-side to ensure uniqueness

## API Endpoints

### POST /api/save_invoice.php
Saves a new invoice to database
- Handles customer creation/update
- Generates invoice number
- Stores invoice data

### GET /api/get_invoices.php
Retrieves all invoices with customer details

### GET /api/get_customer_invoices.php?phone=XXX&type=gst
Retrieves invoices for specific customer
- Used for checking existing customers
- Used for part payment calculations

### GET /api/get_customers.php
Retrieves all customers with invoice counts

## Benefits of Database Storage

1. **Persistent Data**: Invoices survive browser cache clearing
2. **Correct Numbering**: Sequential invoice numbers even after deletions
3. **Multi-User**: Multiple users can access same data
4. **Backup**: Easy to backup and restore data
5. **Reporting**: Can run SQL queries for reports
6. **Scalability**: Can handle large number of invoices

## Migration from localStorage

If you have existing invoices in localStorage, they will no longer be visible. To migrate:

1. Open browser console
2. Run: `console.log(JSON.stringify(localStorage.getItem('generatedInvoices')))`
3. Copy the output
4. Create a migration script to insert into database
5. Or manually recreate important invoices

## Troubleshooting

### Invoices not saving
- Check database connection in `api/config.php`
- Check browser console for errors
- Verify database tables exist

### Invoice numbers not sequential
- Check if there are gaps in database
- System counts total invoices, not max ID
- Delete test invoices if needed

### Part payments not working
- Verify customer phone number matches exactly
- Check that original invoice has balance due
- Ensure `continueFrom` parameter is passed correctly

## Database Maintenance

### Clear all invoices
```sql
DELETE FROM invoices;
DELETE FROM customers;
```

### Reset invoice numbering
Invoice numbers are based on count, so deleting all invoices will reset to TSK-YYYY-001

### View invoice details
```sql
SELECT 
    i.invoice_no,
    c.name,
    c.phone,
    i.type,
    i.invoice_date,
    i.original_total_payable,
    i.cumulative_total_paid
FROM invoices i
JOIN customers c ON i.customer_id = c.id
ORDER BY i.created_at DESC;
```
