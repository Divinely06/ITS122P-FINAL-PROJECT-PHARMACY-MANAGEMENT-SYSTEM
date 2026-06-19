<?php
// config.php - database connection file
$db_host = "bjwp2fpsaulvdv9hejkp-mysql.services.clever-cloud.com";
$db_user = "uk7tfecxmfqcfjb3";
$db_pass = "ZGlaYFY13Pt8eX2LXM6t"; // <-- Put the real password here!
$db_name = "bjwp2fpsaulvdv9hejkp";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Hindi makakonekta sa database: " . mysqli_connect_error());
}
?>
