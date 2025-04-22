<?php 
include '../config/db.php'; 
include '../layout/header.php';
include '../layout/navigation.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CollabShare Analytics</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: #f2f6f9;
      color: #333;
    }
    header {
        margin-top: 70px;
      text-align: center;
      padding: 40px 20px 20px;
      background: #71bbb2;
      color: white;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    header h1 {
      margin: 0;
      font-size: 2.5em;
    }
    .container {
        margin-top: 20px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
      gap: 30px;
      padding: 40px;
    }
    .chart-card {
      background: white;
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }
    .chart-card:hover {
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      transform: translateY(-5px);
    }
    .chart-card h2 {
      font-size: 20px;
      margin-bottom: 15px;
      text-align: center;
      color: #444;
    }
    canvas {
      max-height: 300px;
    }
    @media (max-width: 600px) {
      header h1 {
        font-size: 1.8em;
      }
    }
  </style>
</head>
<body>

<header>
  <h1>📊 CollabShare Analytics Dashboard</h1>
</header>

<?php
$conn = getDatabaseConnection();
// Chart 1: Users per Department
$userDept = [];
$res1 = $conn->query("SELECT d.department_name, COUNT(u.id) AS count
                      FROM department d
                      LEFT JOIN users u ON d.id = u.department_id
                      GROUP BY d.id");
while ($row = $res1->fetch_assoc()) {
    $userDept['labels'][] = $row['department_name'];
    $userDept['data'][] = $row['count'];
}

// Chart 2: Notes per User
$notesUser = [];
$res2 = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS fullname, COUNT(n.id) AS note_count
                      FROM users u
                      LEFT JOIN notes n ON u.id = n.users_id
                      GROUP BY u.id LIMIT 6");
while ($row = $res2->fetch_assoc()) {
    $notesUser['labels'][] = $row['fullname'];
    $notesUser['data'][] = $row['note_count'];
}

// Chart 3: Files per Note
$filesNote = [];
$res3 = $conn->query("SELECT title, COUNT(f.id) AS file_count
                      FROM notes n
                      LEFT JOIN files f ON n.id = f.notes_id
                      GROUP BY n.id LIMIT 6");
while ($row = $res3->fetch_assoc()) {
    $filesNote['labels'][] = $row['title'];
    $filesNote['data'][] = $row['file_count'];
}

// Chart 4: User Verification Status
$verified = $unverified = 0;
$res4 = $conn->query("SELECT is_verified, COUNT(*) AS count FROM users GROUP BY is_verified");
while ($row = $res4->fetch_assoc()) {
    if ($row['is_verified']) $verified = $row['count'];
    else $unverified = $row['count'];
}
?>

<div class="container">
  <div class="chart-card">
    <h2>User Distribution by Department</h2>
    <canvas id="userDepartmentChart"></canvas>
  </div>

  <div class="chart-card">
    <h2>Notes per User</h2>
    <canvas id="notesPerUserChart"></canvas>
  </div>

  <div class="chart-card">
    <h2>Files per Note</h2>
    <canvas id="filesPerNoteChart"></canvas>
  </div>

  <div class="chart-card">
    <h2>Verification Status</h2>
    <canvas id="verificationStatusChart"></canvas>
  </div>
</div>

<script>
  new Chart(document.getElementById('userDepartmentChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($userDept['labels']) ?>,
      datasets: [{
        label: 'Users',
        data: <?= json_encode($userDept['data']) ?>,
        backgroundColor: '#71bbb2',
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: { beginAtZero: true }
      }
    }
  });

  new Chart(document.getElementById('notesPerUserChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($notesUser['labels']) ?>,
      datasets: [{
        label: 'Notes Created',
        data: <?= json_encode($notesUser['data']) ?>,
        backgroundColor: '#ffb347',
        borderRadius: 8
      }]
    }
  });

  new Chart(document.getElementById('filesPerNoteChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode($filesNote['labels']) ?>,
      datasets: [{
        label: 'Files Uploaded',
        data: <?= json_encode($filesNote['data']) ?>,
        borderColor: '#4a90e2',
        backgroundColor: 'rgba(74, 144, 226, 0.1)',
        fill: true,
        tension: 0.3
      }]
    }
  });

  new Chart(document.getElementById('verificationStatusChart'), {
    type: 'doughnut',
    data: {
      labels: ['Verified', 'Unverified'],
      datasets: [{
        data: [<?= $verified ?>, <?= $unverified ?>],
        backgroundColor: ['#4caf50', '#f44336']
      }]
    }
  });
</script>

</body>
</html>

<?php include '../layout/footer.php'; ?>