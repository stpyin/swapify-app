<?php
session_start();
include "includes/db_connect.php";
include "includes/header.php";

$course_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$check = $conn->prepare("SELECT c.* FROM tr_coursepurchase cp JOIN ms_courses c ON cp.course_id = c.course_id WHERE cp.user_id = ? AND cp.course_id = ?");
$check->bind_param("ii", $user_id, $course_id);
$check->execute();
$course = $check->get_result()->fetch_assoc();

if (!$course) {
    die("Access Denied: You do not own this course.");
}
?>

<div class="container" style="padding: 40px 20px;">
    <h2 style="margin-bottom: 20px;"><?= htmlspecialchars($course['title']) ?></h2>
    
    <div class="video-container" style="background: #000; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <video width="100%" controls controlsList="nodownload">
            <source src="uploads/videos/<?= htmlspecialchars($course['video']) ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <div class="description" style="margin-top: 30px; background: white; padding: 25px; border-radius: 12px;">
        <h3>Description</h3>
        <p style="color: #666; line-height: 1.6;"><?= nl2br(htmlspecialchars($course['description'])) ?></p>
    </div>
</div>