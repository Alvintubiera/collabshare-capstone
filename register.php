<?php
require 'config/db.php';
require 'mail/sendmail.php';

$firstname = "";
$lastname = "";
$department = "";
$email = "";
$password = "";
$confirm_password = "";

$firstname_error = "";
$lastname_error = "";
$department_error = "";
$email_error = "";
$password_error = "";
$confirm_password_error = "";

$error = '';
$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = trim($_POST['password']);
    $department_id = $_POST['department_id'];
    $confirm_password = $_POST['confirm_password'];
    $verification_code = bin2hex(random_bytes(16)); // 32-char token

    // Validate: Check if email is a real domain (MX record check)
    $domainParts = explode("@", $email);
    $domain = array_pop($domainParts);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !checkdnsrr($domain, "MX")) {
    $error_message = "Invalid email or domain doesn't have MX record";
    }

    if(empty($firstname)){
        $firstname_error = 'firstname is required';
           $error = true;
      }
      if(empty($lastname)){
        $lastname_error = 'lastname is required';
           $error = true;
      }
      if(empty($email)){
        $email_error = 'email is required';
        $error = true;
      }
      if (empty($department_id)) {
        $department_error = 'Department is required.';
        $error = true;
    }
    
    if (empty($password)) {
        $password_error = 'Password is required.';
        $error = true;
    }
        
    // Hash the password (consider using password_hash() instead)
    
    
    

    // Save to DB
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, verification_code) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $firstname, $lastname, $email, $password, $verification_code);

        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $email_error = 'Email already exists!';
            $error = true;
        }   
        if(strlen($password) < 8){
            $password_error = 'password must be at least 8 characters';
               $error = true;
        }
        if($password !== $confirm_password){
            $confirm_password_error = 'passwords do not match';
               $error = true;
        }
            
        if (!$error) {
            $hashedPassword = hash('sha256', $password);
            $insertStmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, department_id, verification_code) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("ssssis", $firstname, $lastname, $email, $hashedPassword, $department_id, $verification_code);
        
            if ($insertStmt->execute()) {
                sendVerificationEmail($email, "$firstname $lastname", $verification_code);
                $success_message = "✅ Registration successful! Check your email to verify.";
            } else {
                $error_message = "❌ Something went wrong: " . $insertStmt->error;
            }
        
            $insertStmt->close();
        }
        

        $checkStmt->close();
        $conn->close();
        }
            
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link rel="stylesheet" href="/css/register.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
  </head>
  <body>
  <div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-sm-12">
            <div class="form-wrapper">

                <h2 class="form-title">Create an Account</h2>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="firstname">First Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" name="firstname" id="firstname" value="<?= $firstname ?>">
                        </div>
                        <div class="text-danger"><?= $firstname_error ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="lastname">Last Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
                            <input type="text" class="form-control" name="lastname" id="lastname" value="<?= $lastname ?>">
                        </div>
                        <div class="text-danger"><?= $lastname_error ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="department">Department</label>
                        <select class="form-select" name="department_id" id="department" required>
                            <option value="">-- Select Department --</option>
                            <option value="1">Arts and Sciences</option>
                            <option value="2">Business Management</option>
                            <option value="3">Criminal Justice</option>
                            <option value="4">Education</option>
                            <option value="5">Engineering</option>
                            <option value="6">Computer Studies</option>
                        </select>
                        <div class="text-danger"><?= $department_error ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="email">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" class="form-control" name="email" id="email" value="<?= $email ?>">
                        </div>
                        <div class="text-danger"><?= $email_error ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" name="password" id="password">
                        </div>
                        <div class="text-danger"><?= $password_error ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password">
                        </div>
                        <div class="text-danger"><?= $confirm_password_error ?></div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-custom">Register</button>
                    </div>

                    <p class="text-center mt-3 small">Already have an account? <a href="login.php" class="text-info">Login here</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
  </body>
</html>