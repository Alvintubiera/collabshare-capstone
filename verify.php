<?php
require 'config/db.php';

$code = $_GET['code'] ?? $_POST['code'] ?? null;

if ($code) {
    $conn = getDatabaseConnection();

    $stmt = $conn->prepare("SELECT * FROM users WHERE verification_code = ? AND is_verified = 0");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE verification_code = ?");
        $update->bind_param("s", $code);
        $update->execute();
        echo "<div style='padding:20px; font-family:sans-serif;'>✅ Email successfully verified! You can now log in.</div>";
    } else {
        echo "<div style='padding:20px; font-family:sans-serif;'>⚠️ Invalid or already used verification code.</div>";
    }
} else {
    echo "<div style='padding:20px; font-family:sans-serif;'>❌ No verification code provided.</div>";
}
?>
