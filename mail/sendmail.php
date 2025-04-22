<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require dirname(__DIR__) . '/vendor/autoload.php';


function sendVerificationEmail($email, $firstname, $verification_code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                            // Use SMTP
        $mail->Host       = 'smtp.gmail.com';                       // Gmail SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'collabshare101@gmail.com';                 // Your Gmail address
        $mail->Password   = 'msdg tfyb okld wrjj';                    // Use Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Encryption
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('collabshare101@gmail.com', 'CollabShare Team');
        $mail->addAddress($email, $firstname);                   // Recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - CollabShare';
        $mail->Body    = "
            <h3>Hello $firstname,</h3>
            <p>Thank you for registering at <strong>CollabShare</strong>.</p>
            <p>Please click the button below to verify your email address:</p>
            <a href='http://localhost/collabshare/verify.php?code=$verification_code' 
               style='display:inline-block;padding:10px 15px;background-color:#28a745;color:white;text-decoration:none;border-radius:5px;'>
               Verify Email
            </a>
            <br><br>
            <p>If you didn’t request this, you can ignore this email.</p>
            <p>— CollabShare Team</p>
        ";

        // Send Email
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
