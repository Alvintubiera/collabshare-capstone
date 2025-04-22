<?php 

function getDatabaseConnection() {
    $host       = "localhost";
    $user       = "root";
    $pass       = "";
    $db_name    = "collabshare";

    $conn = new mysqli($host, $user, $pass, $db_name);
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
    return $conn; 
}
?>