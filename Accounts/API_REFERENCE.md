# Invoice API Reference

## Endpoints

### 1. Save Invoice
**POST** `/api/save_invoice.php`

Creates a new invoice and customer (if not exists).

**Request Body (JSON):**
```json
{
    "billToName": "John Doe",
    "phone": "9876543210",
    "email": "john@example.com",
    "gstNumber": "29AABCT1234C1Z5",
    "type": "gst",
    "items": "[{\"description\":\"Course Fee\",\"paymentMode\":\"Online\",\"date\":\"2026-02-28\",\"totalInclTax\":\"5000\",\"paidAmt\":\"1000\",\"gst\":\"762.71\",\"charges\":\"4237.29\",\"hasDesc\":false}]",
    "date": "2026-02-28",
    "continueFrom": null,
    "originalTotalPayable": null,
    "cumulativeTotalPaid": null
}
```

**Response:**
```json
{
    "success": true,
    "invoiceNo": "TSK-2026-001",
    "customerId": 1
}
```

---

### 2. Get All Invoices
**GET** `/api/get_invoices.php`

Retrieves all invoices with customer details.

**Response:**
```json
[
    {
        "id": 1,
        "invoiceNo": "TSK-2026-001",
        "type": "gst",
        "items": "[...]",
        "originalTotalPayable": null,
        "cumulativeTotalPaid": null,
        "billToName": "John Doe",
        "phone": "9876543210",
        "email": "john@example.com",
        "gstNumber": "29AABCT1234C1Z5",
        "date": "2026-02-28",
        "generatedAt": "2026-02-28 10:30:00"
    }
]
```

---

### 3. Get Customer Invoices
**GET** `/api/get_customer_invoices.php?phone=9876543210&type=gst`

Retrieves invoices for a specific customer.

**Parameters:**
- `phone` (required) - Customer phone number
- `type` (optional) - Filter by 'gst' or 'non-gst'

**Response:**
```json
[
    {
        "invoiceNo": "TSK-2026-001",
        "type": "gst",
        "items": "[...]",
        "originalTotalPayable": null,
        "cumulativeTotalPaid": null,
        "billToName": "John Doe",
        "phone": "9876543210",
        "email": "john@example.com",
        "gstNumber": "29AABCT1234C1Z5",
        "date": "2026-02-28",
        "generatedAt": "2026-02-28 10:30:00"
    }
]
```

---

### 4. Get All Customers
**GET** `/api/get_customers.php`

Retrieves all customers with invoice counts.

**Response:**
```json
[
    {
        "id": 1,
        "name": "John Doe",
        "phone": "9876543210",
        "email": "john@example.com",
        "gstNumber": "29AABCT1234C1Z5",
        "invoiceCount": 3,
        "lastInvoiceDate": "2026-02-28 10:30:00"
    }
]
```

---

### 5. Generate Invoice PDF
**GET** `/api/generate_invoice.php?invoiceNo=TSK-2026-001&type=gst&...`

Generates printable invoice in browser.

**Parameters:**
All invoice data passed as URL parameters (automatically handled by JavaScript).

---

### 6. Test Database
**GET** `/api/test_db.php`

Tests database connection and table structure.

**Response:**
```json
{
    "connection": true,
    "database": "u164024082_console",
    "tables": {
        "customers": "exists",
        "customers_count": 5,
        "customers_columns": ["id", "name", "phone", "email", "gst_number", "created_at", "updated_at"],
        "invoices": "exists",
        "invoices_count": 12,
        "invoices_columns": ["id", "invoice_no", "customer_id", "type", "items", "original_total_payable", "cumulative_total_paid", "invoice_date", "created_at"]
    },
    "success": true,
    "message": "Database is ready for invoice system!"
}
```

---

## Invoice Items JSON Structure

### GST Invoice Item
```json
{
    "description": "Course Fee Payment",
    "paymentMode": "Online",
    "date": "2026-02-28",
    "totalInclTax": "5000.00",
    "paidAmt": "1000.00",
    "gst": "762.71",
    "charges": "4237.29",
    "hasDesc": false
}
```

### Non-GST Invoice Item
```json
{
    "description": "Course Fee Payment",
    "paymentMode": "Cash",
    "date": "2026-02-28",
    "amount": "5000.00",
    "paidAmt": "1000.00"
}
```

---

## Invoice Numbering Logic

### Server-Side (save_invoice.php)

1. **Check if continuing from existing invoice:**
   - If `continueFrom` is provided, extract base number and increment /P suffix
   - Example: `TSK-2026-001` → `TSK-2026-001/P1` → `TSK-2026-001/P2`

2. **Check if customer has unpaid invoices:**
   - Query database for customer's invoices
   - Calculate total payable vs total paid for each
   - If balance due exists, create part payment with /P1

3. **Generate new invoice number:**
   - Count total invoices with pattern `TSK-YYYY-%`
   - Increment count and format as `TSK-YYYY-XXX`

---

## Error Handling

All endpoints return JSON with error information:

```json
{
    "success": false,
    "error": "Error message here"
}
```

Common errors:
- `"Invalid data"` - Missing required fields
- `"Database connection failed"` - Cannot connect to database
- `"Phone number required"` - Missing phone parameter
- `"Customer not found"` - No customer with that phone

---

## Usage Examples

### JavaScript Fetch Examples

**Save Invoice:**
```javascript
fetch('api/save_invoice.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(invoiceData)
})
.then(response => response.json())
.then(data => console.log(data));
```

**Get Customer Invoices:**
```javascript
fetch(`api/get_customer_invoices.php?phone=9876543210&type=gst`)
    .then(response => response.json())
    .then(invoices => console.log(invoices));
```

**Get All Invoices:**
```javascript
fetch('api/get_invoices.php')
    .then(response => response.json())
    .then(invoices => console.log(invoices));
```

---

## Database Queries

### Get invoice with customer details:
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
WHERE i.invoice_no = 'TSK-2026-001';
```

### Get customer's total business:
```sql
SELECT 
    c.name,
    c.phone,
    COUNT(i.id) as total_invoices,
    SUM(JSON_EXTRACT(i.items, '$[0].paidAmt')) as total_paid
FROM customers c
LEFT JOIN invoices i ON c.id = i.customer_id
GROUP BY c.id;
```

### Get unpaid invoices:
```sql
SELECT 
    i.invoice_no,
    c.name,
    i.original_total_payable,
    i.cumulative_total_paid,
    (i.original_total_payable - i.cumulative_total_paid) as balance_due
FROM invoices i
JOIN customers c ON i.customer_id = c.id
WHERE i.original_total_payable > i.cumulative_total_paid;
```
