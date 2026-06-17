<?php
session_start();
include "includes/db_connect.php";
include "includes/header.php";

if (!isset($_GET['id'])) {
    header("Location: my-courses.php");
    exit;
}

$course_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$query = "SELECT c.*, u.name as author_name 
          FROM ms_courses c
          JOIN ms_users u ON c.user_id = u.user_id
          JOIN tr_coursepurchase cp ON c.course_id = cp.course_id
          WHERE c.course_id = ? AND cp.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    echo "<script>
            alert('Access denied or course not found.');
            window.location.href='my-courses.php';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> - Detail</title>
    <link rel="stylesheet" href="css/course-detail.css?v=4">
</head>
<body>
    <div class="container">
        <div class="video-card">
            <div class="video-wrapper">
                <?php if (!empty($course['video'])): ?>
                    <video width="100%" controls controlsList="nodownload" poster="uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>">
                        <source src="uploads/<?= htmlspecialchars($course['video']) ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <img src="uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" alt="thumbnail">
                    <div class="play-btn">▶</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="course-info">
            <h2><?= htmlspecialchars($course['title']) ?></h2>
            <p class="author">By <?= htmlspecialchars($course['author_name']) ?></p>

            <div class="progress-wrapper">
                <div class="progress-bar">
                    <div class="progress-fill" style="width:100%; height: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="description-card">
            <h3>Description</h3>
            <div class="description-text">
                <?= nl2br(htmlspecialchars($course['description'])) ?>
            </div>

            <p class="upload-date">
                Uploaded at: <?= date('d/m/Y', strtotime($course['upload_date'])) ?>
            </p>
            
            <a href="my-courses.php" class="back-btn">
                ← Back to My Courses
            </a>
        </div>
    </div>
</body>
</html>