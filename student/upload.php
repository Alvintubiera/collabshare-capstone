<?php 
require_once '../config/db.php';
include '../layout/header.php';
include '../layout/navigation.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $topic = trim($_POST['topic']);
    $description = trim($_POST['description']);
    $user_id = $_SESSION['user_id'];

    if (!empty($_FILES['file']['name'])) {
        $file = $_FILES['file'];
        $filename = basename($file['name']);
        $filesize = $file['size'];
        $target_dir = "uploads/";
        $target_file = $target_dir . $filename;

        if ($filesize > 10485760) {
            $message = '<div class="alert alert-danger">File is too large. Max is 10MB.</div>';
        } elseif (file_exists($target_file)) {
            $message = '<div class="alert alert-warning">This file already exists.</div>';
        } else {
            $conn = getDatabaseConnection();
            $stmt = $conn->prepare("INSERT INTO notes (users_id, title, topic, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $title, $topic, $description);
            if ($stmt->execute()) {
                $note_id = $stmt->insert_id;
                move_uploaded_file($file["tmp_name"], $target_file);

                $stmt_file = $conn->prepare("INSERT INTO files (notes_id, filename, filesize) VALUES (?, ?, ?)");
                $stmt_file->bind_param("isi", $note_id, $filename, $filesize);
                if ($stmt_file->execute()) {
                    $message = '<div class="alert alert-success">Upload successful!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Could not save file info.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Could not save note.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-danger">Choose a file to upload.</div>';
    }
}
?>

<style>
body {
    background: linear-gradient(135deg, #28809a, #1b2a41);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
}

.main-content {
    padding-top: 80px;
}

body.open .main-content {
  margin-left: 0; /* Sidebar width */
  transition: margin-left 0.4s ease;
}

/* If sidebar is closed */
body:not(.open) .main-content {
    padding-left: 0;
}

/* Prevent content shift on small screens */
@media (max-width: 768px) {
  body.open .main-content {
    padding-left: 0;
    padding-top: 80px;
    margin-left: 0;
  }
  body.open nav .sidebar {
    left: 0; /* Make the sidebar visible on small screens */
    position: fixed; /* Fix the sidebar position to overlay content */
    z-index: 9999; /* Ensure it overlaps the content */
    height: 100%; /* Full height sidebar */
  }
  .overlay {
    left: 0;
    opacity: 1;
    pointer-events: auto;
  }

  /* Prevent horizontal scrolling on small screens */
  html, body {
    overflow-x: hidden; /* Hide horizontal overflow */
    width: 100%; /* Ensure body takes full width */
  }
}
@media (min-width: 768px) and (max-width: 1024px) {
    body.open nav .sidebar {
        position: fixed; /* Keep sidebar fixed on tablets */
        left: 0; /* Ensure sidebar is always visible */
        top: 0; /* Keep sidebar at the top */
        height: 100%; /* Sidebar takes full height */
        z-index: 9999; /* Ensure sidebar is above the content */
    }

    .main-content {
        margin-left: 0;
    }

    body.open .main-content {
        padding-left: 0;
        padding-top: 80px;
        margin-left: 0;
    }

    body {
        overflow-x: hidden; /* Prevent horizontal scrolling on tablet */
    }
}

.upload-card {
    max-width: 650px;
    margin: 60px auto;
    border-radius: 15px;
    background-color: #fff;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.6s ease;
    padding: 30px 25px;
}

.upload-card .card-header {
    background-color: #28809a;
    color: #fff;
    border: none;
    padding: 1rem 0;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
    border-radius: 15px 15px 0 0;
}

.upload-card .form-control {
    width: 100%;
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 12px 20px;
    margin-bottom: 15px;
    font-size: 1rem;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.upload-card .form-control:focus {
    border-color: #28809a;
    box-shadow: 0 0 10px rgba(40, 128, 154, 0.3);
}

.upload-card .form-label {
    font-weight: 500;
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.upload-card .form-text {
    font-size: 0.875rem;
    color: #888;
}

.upload-card button {
    width: 100%;
    padding: 12px;
    border: none;
    background-color: #28809a;
    color: #fff;
    font-size: 1.1rem;
    border-radius: 8px;
    transition: background-color 0.3s;
}

.upload-card button:hover {
    background-color: #1b2a41 !important;
}

.upload-card .alert {
    margin-top: 15px;
    padding: 10px;
    text-align: center;
    border-radius: 8px;
}

.upload-card .file-preview {
    display: none;
    text-align: center;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes showToast {
    0% { opacity: 0; transform: translateY(-20px); }
    100% { opacity: 1; transform: translateY(0); }
}

.toast {
    position: absolute;
    top: 15px;
    right: 15px;
    background-color: #28809a;
    color: #fff;
    padding: 15px;
    border-radius: 5px;
    font-size: 1rem;
    display: none;
    animation: showToast 1s forwards;
}
</style>

<div class="main-content py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8 col-xl-6">
        <div class="upload-card card shadow">
          <div class="card-header text-white text-center fw-bold fs-4" style="background-color: #28809a;">
            📤 Upload Your Study Material
          </div>
          <div class="card-body">
            <?= $message ?>
            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
              <div class="mb-3">
                <label class="form-label">📌 Title <span class="text-danger"></span></label>
                <input type="text" name="title" class="form-control" required placeholder="Enter a descriptive title">
              </div>
              <div class="mb-3">
                <label class="form-label">📚 Topic</label>
                <input type="text" name="topic" class="form-control" placeholder="Optional subject or category">
              </div>
              <div class="mb-3">
                <label class="form-label" for="description">📝 Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Provide some context or summary"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">📎 Select File <span class="text-danger"></span></label>
                <input type="file" name="file" class="form-control" required onchange="previewFile()">
                <div class="form-text">Max size: 10MB | PDF, DOCX, PPTX preferred</div>
                <div id="filePreview" class="file-preview mt-2">
                  <strong>Preview: </strong> <span id="fileName"></span>
                </div>
              </div>
              <div class="text-end">
                <button type="submit" class="btn text-white w-100" style="background-color: #28809a;">Upload</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>









<script>
    // File preview function
    function previewFile() {
    const fileInput = document.querySelector('input[name="file"]');
    const fileName = document.getElementById('fileName');
    const filePreview = document.getElementById('filePreview');

    if (fileInput.files.length > 0) {
        fileName.textContent = fileInput.files[0].name;
        filePreview.style.display = 'block';
    }
}

    // Toast Notification Function
    function showToast(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }

    // Show toast when file upload is successful
    <?php if ($message == '<div class="alert alert-success">Upload successful!</div>') { ?>
        showToast('File uploaded successfully!');
    <?php } ?>
</script>

<?php 
include '../layout/footer.php';
?>