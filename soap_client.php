<?php
// soap_client.php - Simulates an external system requesting data

$response = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['med_id'])) {
    
    $requested_id = $_POST['med_id'];
    
    try {
        // 1. Set up the connection options matching the server
        $options = array(
            'location' => 'http://localhost/pharmacy_management-main/soap_server.php', 
            'uri'      => 'http://localhost/pharmacy_management-main/'
        );
        
        // 2. Initialize the SOAP Client
        $client = new SoapClient(NULL, $options);
        
        // 3. Call the function as if it were a local PHP function
        $response = $client->checkMedicineStock($requested_id);
        
        // Check if the response contains "Error" to format the alert box color
        $status = (strpos($response, 'Error') !== false) ? 'error' : 'success';
        
    } catch (SoapFault $e) {
        $response = "SOAP Error: " . $e->getMessage();
        $status = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>External Partner Portal - SOAP API</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Override the sidebar margin for this specific external page */
        body {
            margin: 0 !important;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #F2F6FA; /* Matching HopeMed background */
            font-family: system-ui, -apple-system, sans-serif;
        }

        /* Modern Card Wrapper */
        .portal-card {
            background-color: #ffffff;
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(12,123,123,0.08);
            border-top: 5px solid #0C7B7B; /* HopeMed Primary Color */
        }

        .portal-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .portal-header h2 {
            color: #0E1C2B;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .portal-header p {
            color: #6B7E91;
            font-size: 0.875rem;
            margin: 0;
        }

        .soap-form label {
            font-weight: 600;
            color: #0E1C2B;
            font-size: 0.875rem;
        }

        .soap-form input {
            width: 100%;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(12,123,123,0.2);
            border-radius: 8px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }

        .soap-form input:focus {
            border-color: #0C7B7B;
            box-shadow: 0 0 0 3px rgba(12,123,123,0.1);
        }

        .soap-form button {
            width: 100%;
            background-color: #0C7B7B;
            color: white;
            padding: 0.875rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .soap-form button:hover {
            background-color: #095c5c;
        }

        /* Response Box Styling */
        .response-box {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .response-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .response-error {
            background-color: #fdeaea;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
    </style>
</head>
<body>

    <div class="portal-card">
        <div class="portal-header">
            <h2>Partner Clinic Portal</h2>
            <p>Secure SOAP API Connection to HopeMed Pharmacy</p>
        </div>

        <form method="POST" class="soap-form">
            <label for="med_id">Check Medicine Availability (ID)</label>
            <input type="number" id="med_id" name="med_id" placeholder="e.g., 1" required>
            <button type="submit">Send SOAP Request</button>
        </form>

        <?php if (!empty($response)): ?>
            <div class="response-box <?php echo $status == 'error' ? 'response-error' : 'response-success'; ?>">
                <strong>API Response:</strong><br>
                <?php echo htmlspecialchars($response); ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>