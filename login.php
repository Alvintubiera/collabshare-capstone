<?php
session_start();
require_once '/laragon/www/collabshare/config/db.php';

// Debug Mode (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$email = $password = "";
$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Database
    $conn = getDatabaseConnection();

    // Prepare query
    $stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, is_verified, role FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();

            if ((int)$row['is_verified'] === 1) {
                if (hash("sha256", $password) === $row['password']) {
                    $_SESSION["user_id"] = $row["id"];
                    $_SESSION["user_name"] = $row["firstname"] . " " . $row["lastname"];
                    $_SESSION["role"] = $row["role"];

                    // Redirection based on role
                    if ($row["role"] === "admin") {
                        header("Location: /admin/admin_dashboard.php");
                    } else if ($row["role"] === "student") {
                        header("Location: /student/student_dashboard.php");
                    } else {
                        $login_error = "❌ Role not recognized.";
                    }
                    exit;
                } else {
                    $login_error = "❌ Invalid password.";
                }
            } else {
                $login_error = "❌ Please verify your email first.";
            }
        } else {
            $login_error = "❌ No account found with that email.";
        }
    } else {
        $login_error = "❌ Something went wrong!";
    }

    $stmt->close();
    $conn->close();
}
?>



<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
  </head>
  <body>
    <div class="container">
        <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
            <div class="cs-login-wrapper">
            <div class="cs-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <h3 class="text-center mb-4">Login</h3>

            <!-- Optional error message -->
            <?php if(!empty($login_error)): ?>
                <div class="alert alert-danger alert-dismissable fade show d-flex justify-content-between" role="alert">
                    <strong><?= $login_error ?></strong>
                    <button type="submit" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-floating mb-3">
                <input type="email" class="form-control cs-input" id="email" name="email" placeholder="Email" required />
                <label for="email">Email address</label>
                <i class="bi bi-envelope-fill position-absolute top-50 end-0 translate-middle-y pe-3" style="color: #28809a;"></i>                
                </div>

                <div class="form-floating mb-4">
                <input type="password" class="form-control cs-input" id="password" name="password" placeholder="Password" required />
                <label for="password">Password</label>
                <i class="bi bi-lock-fill position-absolute top-50 end-0 translate-middle-y pe-3" style="color: #28809a;"></i>
                </div>

                <div class="d-grid mb-3">
                <button type="submit" class="btn cs-btn py-3">Login</button>
                </div>

                <div class="text-center">
                <small>Don't have an account? <a href="register.php" class="cs-link">Register here</a></small>
                </div>
            </form>
            </div>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
  </body>
</html>