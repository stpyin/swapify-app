<?php
    session_start();
    include "includes/header.php";
    include "includes/db_connect.php";

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $query = "SELECT c.*, u.name as author_name 
              FROM tr_coursepurchase cp
              JOIN ms_courses c ON cp.course_id = c.course_id
              JOIN ms_users u ON c.user_id = u.user_id
              WHERE cp.user_id = ?
              ORDER BY cp.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
?>
<!DOCTYPE html>

<html lang="en">



<head>
    <meta charset="UTF-8">
    <title>Swapify - My Courses</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/my-courses.css?v=3">
</head>



<body>

    <main class="container">
        <section class="page-header">
            <h1>Courses</h1>
            <p>Discover courses and expand your knowledge</p>
            <div class="underline"></div>
        </section>

        <div class="search-bar">
            <input type="text" placeholder="Search for a course...">
        </div>

        <div class="toggle">
            <a href="courses.php">
                <button>Explore Courses</button>
            </a>
            <button class="active">My Courses</button>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'expert'): ?>
                <a href="uploaded-courses.php">
                    <button>Uploaded Courses</button>
                </a>
            <?php endif; ?>
        </div>
        <div class="courses-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <a href="course-detail.php?id=<?= $row['course_id'] ?>" style="text-decoration: none; color: inherit;">
                        <div class="my-course-card">
                            <img src="uploads/thumbnails/<?php echo isset($row['thumbnail']) ? htmlspecialchars($row['thumbnail']) : ''; ?>" 
                                onerror="this.src='https:/images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=500'" 
                                alt="course">
                            <div class="course-content">
                                <h3 style="margin-top: 7px"><?= htmlspecialchars($row['title']) ?></h3>
                                <p class="author">By <strong><?= htmlspecialchars($row['author_name']) ?></strong></p>

                                <div class="course-meta">
                                    <span class="status completed">OWNED</span>
                                    <span class="percent">Ready to Watch</span>
                                </div>

                                <div class="actions">
                                    <button class="secondary">Continue Learning</button>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px;">
                    <p>You haven't purchased any courses yet.</p>
                    <a href="courses.php" style="color: #FF6B35; font-weight: bold;">Start exploring now!</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>