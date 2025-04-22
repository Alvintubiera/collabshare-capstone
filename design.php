<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<style>
  .sidebar {
    width: 250px;
    height: 100vh;
    background: #252525;
    color: #fff;
    position: fixed;
  }
  .sidebar a {
    display: flex;
    align-items: center;
    padding: 12px;
    color: #ededed;
    text-decoration: none;
  }
  .sidebar a:hover {
    background: #28809a;
  }
  .sidebar i {
    margin-right: 10px;
  }
</style>

<div class="sidebar">
  <a href="#"><i class='bx bx-grid-alt'></i> Dashboard</a>
  <a href="#"><i class='bx bx-file'></i> Notes</a>
  <a href="#"><i class='bx bx-question-mark'></i> Questions</a>
  <a href="#"><i class='bx bx-bell'></i> Notifications</a>
  <a href="../auth/logout.php"><i class='bx bx-log-out'></i> Logout</a>
</div>

</body>
</html>