<?php
// export_sales_xml.php - Generates and validates an XML sales report

session_start();

// Security check: Only admins should export financial data
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access Denied. Only administrators can export reports.");
}

include 'config.php';

// 1. Initialize DOMDocument
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true; 

// 2. Create the Root Element (No xsi attributes to prevent that error!)
$root = $dom->createElement('PharmacySales');
$dom->appendChild($root);

// 3. Fetch Data from the database (Joining users to get the cashier name)
$query = mysqli_query($conn, "SELECT sales.*, users.full_name 
                              FROM sales 
                              JOIN users ON sales.user_id = users.user_id 
                              ORDER BY sale_date DESC");

if (!$query) {
    die("Database query failed: " . mysqli_error($conn));
}

// 4. Loop through sales and build XML nodes
while ($row = mysqli_fetch_assoc($query)) {
    $sale = $dom->createElement('Sale');

    $sale->appendChild($dom->createElement('SaleID', $row['sale_id']));
    $sale->appendChild($dom->createElement('CashierName', htmlspecialchars($row['full_name'])));
    $sale->appendChild($dom->createElement('TotalAmount', $row['total_amount']));
    $sale->appendChild($dom->createElement('AmountPaid', $row['amount_paid']));
    $sale->appendChild($dom->createElement('ChangeAmount', $row['change_amount']));
    $sale->appendChild($dom->createElement('SaleDate', $row['sale_date']));

    $root->appendChild($sale);
}

// 5. Validate against the XSD Schema
if (!$dom->schemaValidate('sales.xsd')) {
    echo "<b>XML Generation Failed:</b> The data does not match the strict rules defined in sales.xsd.";
    exit();
}

// 6. Force the browser to download the file
$date_today = date('Y-m-d');
header('Content-Type: text/xml');
header('Content-Disposition: attachment; filename="Sales_Report_' . $date_today . '.xml"');

echo $dom->saveXML();
exit();
?>