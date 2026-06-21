<?php
// soap_server.php - The backend service that processes requests

// Safety Shield: Prevent PHP warnings from corrupting the XML SOAP response
ini_set("display_errors", 0); 
ini_set("soap.wsdl_cache_enabled", "0");

// The actual function that the SOAP client will call
function checkMedicineStock($medicine_id) {
    // Include your existing database connection
    include 'config.php';
    
    // Clean the input to prevent basic SQL injection
    $safe_id = mysqli_real_escape_string($conn, $medicine_id);
    
    $query = mysqli_query($conn, "SELECT name, stock_quantity FROM medicines WHERE medicine_id = '$safe_id'");
    
    if (mysqli_num_rows($query) > 0) {
        $med = mysqli_fetch_assoc($query);
        return "HopeMed currently has " . $med['stock_quantity'] . " units of " . $med['name'] . " in stock.";
    } else {
        return "Error: Medicine ID not found in HopeMed inventory.";
    }
}

// 1. Initialize the SOAP Server in Non-WSDL mode
$options = array('uri' => 'http://localhost/pharmacy_management/');
$server = new SoapServer(NULL, $options);

// 2. Register the specific function so external systems can use it
$server->addFunction('checkMedicineStock');

// 3. Process the incoming SOAP request
$server->handle();
?>