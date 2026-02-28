<?php
header('Content-Type: application/json');
require_once 'config.php';

$results = [
    'connection' => false,
    'tables' => [],
    'errors' => []
];

try {
    // Test connection
    $results['connection'] = true;
    $results['database'] = DB_NAME;
    
    // Check if customers table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'customers'");
    if ($stmt->num_rows > 0) {
        $results['tables']['customers'] = 'exists';
        
        // Count customers
        $count = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc();
        $results['tables']['customers_count'] = $count['count'];
    } else {
        $results['tables']['customers'] = 'missing';
        $results['errors'][] = 'customers table does not exist';
    }
    
    // Check if invoices table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'invoices'");
    if ($stmt->num_rows > 0) {
        $results['tables']['invoices'] = 'exists';
        
        // Count invoices
        $count = $conn->query("SELECT COUNT(*) as count FROM invoices")->fetch_assoc();
        $results['tables']['invoices_count'] = $count['count'];
    } else {
        $results['tables']['invoices'] = 'missing';
        $results['errors'][] = 'invoices table does not exist';
    }
    
    // Get table structure
    if ($results['tables']['customers'] === 'exists') {
        $stmt = $conn->query("DESCRIBE customers");
        $columns = [];
        while ($row = $stmt->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        $results['tables']['customers_columns'] = $columns;
    }
    
    if ($results['tables']['invoices'] === 'exists') {
        $stmt = $conn->query("DESCRIBE invoices");
        $columns = [];
        while ($row = $stmt->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        $results['tables']['invoices_columns'] = $columns;
    }
    
    $results['success'] = count($results['errors']) === 0;
    $results['message'] = $results['success'] 
        ? 'Database is ready for invoice system!' 
        : 'Please run add_invoice_tables.sql to create missing tables';
    
} catch (Exception $e) {
    $results['success'] = false;
    $results['errors'][] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>
