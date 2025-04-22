<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: /login.php");
    exit;
}

include '../layout/header.php';
include '../layout/navigation.php';
?>


<?php 
include '../layout/footer.php';
?>