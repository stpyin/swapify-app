<?php
    session_start();
    include "includes/db_connect.php";
    include "includes/header.php";

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $currentPage = basename($_SERVER['PHP_SELF']);

    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";

    $verifyQuery = "SELECT status FROM tr_userverification WHERE user_id = ? AND status = 'approved'";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bind_param("i", $user_id);
    $verifyStmt->execute();
    $isVerified = $verifyStmt->get_result()->num_rows > 0;

    if (!$isVerified) {
        header("Location: courses.php");
        exit();
    }

    $searchCondition = "";
    if (!empty($search)) {
        $searchCondition = " AND c.title LIKE '%$search%' ";
    }

    $query = "SELECT c.*, cat.category_name, COUNT(p.purchase_id) as total_sales
              FROM ms_courses c
              JOIN ms_coursecategory cat ON c.category_id = cat.category_id
              LEFT JOIN tr_coursepurchase p ON c.course_id = p.course_id
              WHERE c.user_id = $user_id $searchCondition
              GROUP BY c.course_id
              ORDER BY c.upload_date DESC";
              
    $result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swapify - Courses</title>
    <link rel="stylesheet" href="css/courses.css">
    <link rel="stylesheet" href="css/uploaded-courses.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <main class="container">
        <section class="page-header">
            <h1>Courses</h1>
            <p>Discover courses and expand your knowledge</p>
            <div class="underline"></div>
        </section>

        <div class="search-box">
            <form action="" method="GET">
                <input type="text" name="search" placeholder="Search for a course..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>

        <div class="course-toggle">
            <a href="courses.php">
                <button class="<?= $currentPage == 'courses.php' ? 'active' : '' ?>">Explore Courses</button>
            </a>
            
            <a href="my-courses.php">
                <button class="<?= $currentPage == 'my-courses.php' ? 'active' : '' ?>">My Courses</button>
            </a>

            <?php if ($isVerified): ?>
            <a href="uploaded-courses.php">
                <button class="<?= $currentPage == 'uploaded-courses.php' ? 'active' : '' ?>">Uploaded Courses</button>
            </a>
            <?php endif; ?>
        </div>

        <div class="section-header-flex">
            <h2 class="section-title">Your Course</h2>
            <a href="upload-course.php" class="btn-upload">
                <span class="plus-icon">+</span> Upload New Course
            </a>
        </div>

        <div class="course-list-wrapper">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="my-course-card">
                        <?php 
                            $thumb = $row['thumbnail'];
                            $thumbPath = (file_exists("uploads/".$thumb)) ? "uploads/".$thumb : "uploads/thumbnails/".$thumb;
                            if(empty($thumb) || !file_exists($thumbPath)) $thumbPath = "https://images.unsplash.com/photo-1582582621959-48d27397dc69";
                        ?>
                        <img src="<?= $thumbPath ?>" alt="course thumbnail">

                        <div class="course-content">
                            <span class="category-tag"><?= htmlspecialchars($row['category_name']) ?></span>
                            <h3><?= htmlspecialchars($row['title']) ?></h3>
                            <p class="author">Published on: <strong><?= date('d M Y', strtotime($row['upload_date'])) ?></strong></p>

                            <div class="course-meta">
                                <span class="price-info">Price: <strong><?= number_format($row['credits_price']) ?> Credits</strong></span>

                                <span class="purchase-info" style="font-size: 13px; color: #6B7280; display: flex; align-items: center; gap: 4px;">
                                    <strong><?= $row['total_sales'] ?></strong> purchase
                                </span>
                            </div>

                            <div class="actions">
                                <a href="edit-course.php?id=<?= $row['course_id']?>" class="primary">Edit Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't uploaded any courses yet. Start sharing your skills!</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>