<?php
// delete_medicine.php - para sa pag-delete ng medicine

session_start();

// i-redirect sa login kung hindi naka-login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// kunin yung id ng medicine na ide-delete
$med_id = $_GET['id'];

// check muna kung may id talaga
if (empty($med_id)) {
    header('Location: inventory.php');
    exit();
}

// i-delete sa database
$sql = "DELETE FROM medicines WHERE medicine_id='$med_id'";

if (mysqli_query($conn, $sql)) {
    // successful na na-delete
    header('Location: inventory.php');
    exit();
} else {
    // may error sa pag-delete
    die("Unable to delete: " . mysqli_error($conn));
}
?>