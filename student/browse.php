<?php
require '../config/db.php';
require '../layout/header.php';
require '../layout/navigation.php';

$conn = getDatabaseConnection();

$sql = "SELECT f.id, f.filename, f.filesize, f.uploaded_at, n.title, n.topic, n.description,
               u.firstname, u.lastname, d.department_name
        FROM files f
        JOIN notes n ON f.notes_id = n.id
        JOIN users u ON n.users_id = u.id
        JOIN department d ON u.department_id = d.id
        ORDER BY f.uploaded_at DESC";

$result = $conn->query($sql);
?>

<style>
body {
    background: linear-gradient(135deg, #28809a, #1b2a41);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #ededed;
    min-height: 100vh;
    padding-top: 60px;
}

.container h2 {
    margin-top: 40px;
    font-weight: 700;
    font-size: 2rem;
    margin-bottom: 40px;
    color: #ffffff;
    text-align: center;
    position: relative;
}

.container h2::after {
    content: "";
    width: 80px;
    height: 3px;
    background-color: #ededed;
    display: block;
    margin: 12px auto 0;
    border-radius: 2px;
}

/* Responsive Grid */
.row {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    justify-content: center;
}

/* Modern Minimal Card */
.card {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #ddd;
    border-radius: 16px;
    overflow: hidden;
    width: 100%;
    max-width: 340px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    position: relative;
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.card-body {
    padding: 20px;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1b2a41;
    margin-bottom: 8px;
}

.card-subtitle {
    font-size: 0.95rem;
    color: #28809a;
    margin-bottom: 12px;
    font-weight: 500;
}

.card-text {
    color: #444;
    font-size: 0.95rem;
    margin-bottom: 18px;
}

.list-group-item {
    background-color: #f9f9f9;
    color: #333;
    border: none;
    padding: 8px 14px;
    font-size: 0.9rem;
    display: flex;
    justify-content: space-between;
}

.list-group-item strong {
    font-weight: 600;
    margin-right: 30px;
}

/* File Type Badge */
.file-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background-color: #28809a;
    color: white;
    padding: 4px 8px;
    font-size: 0.75rem;
    border-radius: 20px;
    font-weight: 500;
}

/* Download Button */
.btn-download {
    background-color: #28809a !important;
    color: white;
    font-weight: 600;
    border-radius: 8px;
    padding: 10px 15px;
    width: 100%;
    transition: background-color 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-decoration: none;
}

.btn-download:hover {
    background-color: #1b2a41 !important;
}

/* Responsive Fixes */
@media (max-width: 768px) {
    .card {
        max-width: 100%;
    }

    body {
        overflow-x: hidden;
    }
}
</style>

<div class="main-content p-4">
    <div class="container">
        <h2 class="mb-4 mt-4 text-white">📁 Browse Uploaded Files</h2>
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $fileExt = strtoupper(pathinfo($row['filename'], PATHINFO_EXTENSION));
                ?>
                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <span class="file-badge"><?= $fileExt ?></span>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                                <h6 class="card-subtitle mb-2"><?= htmlspecialchars($row['topic']) ?></h6>
                                <p class="card-text"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Uploaded by:</strong> <span><?= $row['firstname'] . ' ' . $row['lastname'] ?></span></li>
                                <li class="list-group-item"><strong>Department:</strong> <span><?= $row['department_name'] ?></span></li>
                                <li class="list-group-item"><strong>Size:</strong> <span><?= round($row['filesize'] / 1024, 2) ?> KB</span></li>
                                <li class="list-group-item"><strong>Date:</strong> <span><?= date('F j, Y, g:i a', strtotime($row['uploaded_at'])) ?></span></li>
                            </ul>
                            <div class="card-body text-center">
                                <a href="uploads/<?= urlencode($row['filename']) ?>" class="btn btn-download" download>Download</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No files uploaded yet.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require '../layout/footer.php'; ?>
