<?php
// delete_staff.php - para sa pag-delete ng staff member 

session_start();

// i-redirect sa login kung hindi naka-login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// kunin yung id ng staff na ide-delete
$staff_id = $_GET['id'];

// check kung may id
if (empty($staff_id)) {
    header('Location: staff.php');
    exit();
}

// hindi pwedeng i-delete ang sarili mong account
if ($staff_id == $_SESSION['user_id']) {
    // babalik lang sa staff page, hindi mag-delete
    header('Location: staff.php');
    exit();
}

// i-delete na sa database
$sql = "DELETE FROM users WHERE user_id='$staff_id'";

if (mysqli_query($conn, $sql)) {
    // matagumpay na na-delete
    header('Location: staff.php');
    exit();
} else {
    // may problema sa pag-delete
    die("Hindi ma-delete ang staff: " . mysqli_error($conn));
}
?>