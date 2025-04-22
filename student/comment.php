<?php
session_start();
require_once '../config/db.php';
require_once '../mail/emailsender.php';

// 1) Ensure user is logged in
if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header('Location: ../login.php');
    exit;
}
$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// 2) Connect once
$conn = getDatabaseConnection();

// 3) Handle deletion of a question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_question'], $_POST['question_id_delete'])) {
    $del_qid = (int)$_POST['question_id_delete'];
    if ($del_qid > 0) {
        $stmt = $conn->prepare("DELETE FROM questions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $del_qid, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: ../student/comment.php');
    exit;
}

// 4) Handle posting a new question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question']) && empty($_POST['answer']) && empty($_POST['delete_question'])) {
    $q = trim($_POST['question']);
    if ($q !== '') {
        $stmt = $conn->prepare("INSERT INTO questions (user_id, question, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $user_id, $q);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: ../student/comment.php');
    exit;
}

// 5) Handle posting an answer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'], $_POST['question_id']) && empty($_POST['delete_question'])) {
    $a   = trim($_POST['answer']);
    $qid = (int)$_POST['question_id'];
    if ($a !== '' && $qid > 0) {
        // Insert answer
        $stmt = $conn->prepare(
          "INSERT INTO answers (question_id, user_id, answer, created_at) VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param("iis", $qid, $user_id, $a);
        $stmt->execute();
        $stmt->close();

        // Notify question owner
        $n = $conn->prepare(
          "SELECT u.email, u.firstname
           FROM questions q
           JOIN users u ON q.user_id = u.id
           WHERE q.id = ?"
        );
        $n->bind_param("i", $qid);
        $n->execute();
        $res = $n->get_result()->fetch_assoc();
        $n->close();

        if ($res) {
            $toEmail = $res['email'];
            $toName  = $res['firstname'];
            $subject = "New answer to your question";
            $body    = "<p>Hi {$toName},</p>"
                     . "<p><strong>{$user_name}</strong> answered your question:</p>"
                     . "<blockquote>{$a}</blockquote>"
                     . "<p><a href='http://yourdomain.com/student/comments.php'>View discussion</a></p>";
            sendMail($toEmail, $toName, $subject, $body);
        }
    }
    header('Location: ../student/comment.php');
    exit;
}


if (isset($_GET['delete_answer'])) {
    $answer_id = $_GET['delete_answer'];
    $user_id = $_SESSION['user_id'];

    // Prepare and execute delete query
    $stmt = $conn->prepare("DELETE FROM answers WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $answer_id, $user_id);
    
    if ($stmt->execute()) {
        // Redirect back to the comments page after deletion
        header("Location: comment.php?question_id=" . $_GET['question_id']);
        exit();
    } else {
        echo "Error deleting answer.";
    }
}


// 6) Fetch all questions (with asker info)
$questions = $conn->query(
  "SELECT q.id, q.user_id, q.question, q.created_at, u.firstname, u.lastname
   FROM questions q
   JOIN users u ON q.user_id = u.id
   ORDER BY q.created_at DESC"
);

// 7) Utility to fetch answers per question
function fetchAnswers($conn, $qid) {
    $s = $conn->prepare(
      "SELECT a.id, a.answer, a.created_at, a.user_id, u.firstname, u.lastname
       FROM answers a
       JOIN users u ON a.user_id=u.id
       WHERE a.question_id=?
       ORDER BY a.created_at ASC"
    );
    $s->bind_param("i", $qid);
    $s->execute();
    return $s->get_result();
}
// 8) Handle updating a question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_question'], $_POST['question_id_edit'])) {
  $qid = (int)$_POST['question_id_edit'];
  $updatedQuestion = trim($_POST['updated_question']);
  if ($qid > 0 && $updatedQuestion !== '') {
      $stmt = $conn->prepare("UPDATE questions SET question = ? WHERE id = ? AND user_id = ?");
      $stmt->bind_param("sii", $updatedQuestion, $qid, $user_id);
      $stmt->execute();
      $stmt->close();
  }
  header('Location: comment.php');
  exit;
}

// 9) Handle updating an answer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_answer'], $_POST['answer_id_edit'])) {
  $aid = (int)$_POST['answer_id_edit'];
  $updatedAnswer = trim($_POST['updated_answer']);
  if ($aid > 0 && $updatedAnswer !== '') {
      $stmt = $conn->prepare("UPDATE answers SET answer = ? WHERE id = ? AND user_id = ?");
      $stmt->bind_param("sii", $updatedAnswer, $aid, $user_id);
      $stmt->execute();
      $stmt->close();
  }
  header('Location: comment.php');
  exit;
}



require_once '../layout/header.php';
require_once '../layout/navigation.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Comments</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        /* ───────────── General Styles ───────────── */
  body {
    background: #f8f9fa;
  }

  .main {
    margin-top: 60px;
  }

  .card {
    border: none;
    border-radius: .75rem;
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, 0.05);
  }

  .avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
  }

  .timestamp {
    font-size: .85rem;
    color: #6c757d;
  }

  .comment-body {
    background: #fff;
    border-radius: .75rem;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: box-shadow 0.2s;
  }

  .comment-body:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
  }

  .comment-body p {
    font-size: 1rem;
  }

  /* ───────────── Question Form ───────────── */
  .card form {
    display: flex;
    gap: 1rem;
  }

  textarea {
    border-radius: .5rem;
  }

  textarea:focus {
    box-shadow: 0 0 0 0.25rem rgba(41, 128, 154, 0.25);
  }

  .btn-primary {
    border-radius: .5rem;
    font-weight: 500;
  }

  /* ───────────── Button Container ───────────── */
  .comment-buttons {
    display: flex;
    flex-direction: column; /* Stack buttons vertically */
    gap: 0.5rem; /* Add space between the buttons */
    align-items: flex-start; /* Align buttons to the left */
  }

  /* ───────────── Delete Button ───────────── */
  .btn-outline-danger {
    border-radius: 0.5rem;
    padding: 0.25rem 0.75rem;
    font-weight: 500;
    transition: all 0.2s;
  }

  .btn-outline-danger:hover {
    background-color: red;
    border-color: red;
    color: #fff;
  }

  /* ───────────── Edit Button ───────────── */
  .edit-btn {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    background-color: #28809a !important;
    color: #fff !important;
    border-radius: 0.375rem;
    border: none !important;
    font-size: 0.85rem;
    transition: background-color 0.2s;
  }

  .edit-btn:hover {
    background-color: #1f5f74 !important;
  }

  /* ───────────── Comment Styling ───────────── */
  .comment-body p {
    font-size: 1rem;
    line-height: 1.5;
  }

  /* ───────────── Answer Form ───────────── */
  input[type="text"] {
    border-radius: .5rem;
    padding: 0.5rem 1rem;
  }

  input[type="text"]:focus {
    box-shadow: 0 0 0 0.25rem rgba(41, 128, 154, 0.25);
  }

  /* ───────────── Textarea for Edit ───────────── */
  textarea {
    border-radius: .5rem;
    padding: .5rem;
    resize: none;
  }

  textarea:focus {
    box-shadow: 0 0 0 0.25rem rgba(41, 128, 154, 0.25);
  }

  /* ───────────── Reply Button ───────────── */
  .btn-outline-secondary {
    border-radius: .5rem;
    font-size: .85rem;
    padding: .375rem 0.75rem;
    background-color: #ced4da;
    border-color: #ced4da;
    color: #495057;
  }

  .btn-outline-secondary:hover {
    background-color: #adb5bd;
    border-color: #adb5bd;
    color: #ffffff;
  }

  /* ───────────── Form Controls ───────────── */
  input, textarea {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
  }

  input:focus, textarea:focus {
    border-color: #28809a;
    outline: none;
    background-color: #ffffff;
  }

  textarea {
    resize: none;
  }

  /* ───────────── Mobile Responsiveness ───────────── */
  @media (max-width: 767px) {
    .card {
      margin-bottom: 1rem;
    }

    .comment-body {
      padding: .75rem;
    }

    .comment-buttons {
      gap: 0.25rem;
    }

    .avatar {
      width: 30px;
      height: 30px;
    }

    .timestamp {
      font-size: .75rem;
    }

    .btn-primary, .edit-btn, .btn-outline-danger, .btn-outline-secondary {
      font-size: .75rem;
      padding: .25rem .5rem;
    }

    .card form {
      flex-direction: column;
      gap: 0.75rem;
    }
  }

      </style>
</head>
<body>
<div class="container py-5 main">
  <h2 class="mb-4">Community Questions</h2>

  <!-- Post Question -->
  <div class="card mb-4 p-3">
    <form method="POST" class="d-flex">
      <textarea name="question" class="form-control me-2" rows="2"
        placeholder="Ask a question..." required></textarea>
      <button class="btn btn-primary">Post</button>
    </form>
  </div>

  <!-- List Questions & Answers -->
  <?php while ($q = $questions->fetch_assoc()): ?>
    <div class="card mb-4 p-3">
      <div class="d-flex justify-content-between mb-2">
        <div class="d-flex">
          <img src="/path/to/default-avatar.png" class="avatar me-2" alt="Avatar">
          <div>
            <strong><?= htmlspecialchars($q['firstname'].' '.$q['lastname']) ?></strong><br>
            <span class="timestamp"><?= date('M j, Y H:i', strtotime($q['created_at'])) ?></span>
          </div>
        </div>
        <?php if ($user_id === (int)$q['user_id']): ?>
          <form method="POST" class="mb-0">
            <input type="hidden" name="delete_question" value="1">
            <input type="hidden" name="question_id_delete" value="<?= $q['id'] ?>">
            <button class="btn btn-outline-danger btn-delete">Delete</button>
          </form>
        <?php endif; ?>
      </div>
          
      <p><?= nl2br(htmlspecialchars($q['question'])) ?></p>

      <?php if ($user_id === (int)$q['user_id']): ?>
        <a href="#" class="btn btn-sm btn-outline-primary edit-btn mb-2" data-target="edit-q<?= $q['id'] ?>">Edit</a>

        <form method="POST" id="edit-q<?= $q['id'] ?>" style="display:none;">
          <input type="hidden" name="question_id_edit" value="<?= $q['id'] ?>">
          <textarea name="updated_question" class="form-control mb-2" rows="2"><?= htmlspecialchars($q['question']) ?></textarea>
          <button class="btn btn-primary btn-sm" name="update_question">Update</button>
        </form>
      <?php endif; ?>


      <!-- Answers -->
      <?php 
  $ans = fetchAnswers($conn, $q['id']);
  while ($a = $ans->fetch_assoc()): 
?>
  <div class="comment-body">
      <strong><?= htmlspecialchars($a['firstname'].' '.$a['lastname']) ?></strong>
      <span class="timestamp float-end"><?= date('M j, Y H:i', strtotime($a['created_at'])) ?></span>
      <p class="mt-2"><?= nl2br(htmlspecialchars($a['answer'])) ?></p>

      <?php if ($user_id === (int)$a['user_id']): ?>
        <!-- Edit Button -->
       

        <div class="comment-buttons">
        <a href="#" class="btn btn-sm btn-outline-primary edit-btn mb-2" data-target="edit-a<?= $a['id'] ?>">Edit</a> 
        <!-- Delete Button -->
        <a href="comment.php?delete_answer=<?= $a['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
        </div>
        

        <!-- Edit Answer Form (hidden by default) -->
        <form method="POST" id="edit-a<?= $a['id'] ?>" style="display:none; margin-top: 20px;">
          <input type="hidden" name="answer_id_edit" value="<?= $a['id'] ?>">
          <input type="text" name="updated_answer" class="form-control mb-2"
                 value="<?= htmlspecialchars($a['answer']) ?>" required>
          <button class="btn btn-primary btn-sm" name="update_answer">Update</button>
        </form>
      <?php endif; ?>
  </div>
<?php endwhile; ?>

<!-- Reply Form (for new answers) -->
<form method="POST" class="d-flex mt-2">
  <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
  <input type="text" name="answer" class="form-control me-2"
         placeholder="Write an answer..." required>
  <button class="btn btn-outline-secondary">Reply</button>
</form>

    </div>
  <?php endwhile; ?>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const target = this.dataset.target;
      const form = document.getElementById(target);
      form.style.display = (form.style.display === 'none') ? 'block' : 'none';
    });
  });
</script>

</body>
</html>
