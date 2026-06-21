<?php
// export_xml.php - Generates and validates an XML inventory report

session_start();

// Security check: Only logged-in users (or admins) should export data
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access Denied. Only administrators can export reports.");
}

include 'config.php';

// 1. Initialize DOMDocument
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true; // Makes the XML nicely indented

// 2. Create the Root Element
$root = $dom->createElement('PharmacyInventory');
$dom->appendChild($root);

// 3. Fetch Data from the database
$query = mysqli_query($conn, "SELECT * FROM medicines ORDER BY name ASC");

if (!$query) {
    die("Database query failed: " . mysqli_error($conn));
}

// 4. Loop through inventory and build XML nodes
while ($row = mysqli_fetch_assoc($query)) {
    $medicine = $dom->createElement('Medicine');

    $medicine->appendChild($dom->createElement('ID', $row['medicine_id']));
    // Using htmlspecialchars to prevent issues with characters like '&' in medicine names
    $medicine->appendChild($dom->createElement('Name', htmlspecialchars($row['name']))); 
    $medicine->appendChild($dom->createElement('Category', htmlspecialchars($row['category'])));
    $medicine->appendChild($dom->createElement('Price', $row['price']));
    $medicine->appendChild($dom->createElement('Stock', $row['stock_quantity']));
    $medicine->appendChild($dom->createElement('Unit', htmlspecialchars($row['unit'])));
    $medicine->appendChild($dom->createElement('ExpiryDate', $row['expiry_date']));

    $root->appendChild($medicine);
}

// 5. Validate against the XSD Schema
if (!$dom->schemaValidate('inventory.xsd')) {
    // If validation fails, stop and show an error
    echo "<b>XML Generation Failed:</b> The data does not match the strict rules defined in inventory.xsd.<br>";
    echo "Check your database for invalid data types (e.g., text inside a number field).";
    exit();
}

// 6. Force the browser to download the file if validation passes
$date_today = date('Y-m-d');
header('Content-Type: text/xml');
header('Content-Disposition: attachment; filename="Inventory_Report_' . $date_today . '.xml"');

// Output the generated XML
echo $dom->saveXML();
exit();
?>