<?php
// logout.php - para sa pag-logout ng user

session_start();

// i-save muna yung username bago i-destroy ang session
$current_user = $_SESSION['username'];

// i-clear lahat ng session data
session_unset();
session_destroy();

// balik sa login page
header('Location: login.php');
exit();
?>