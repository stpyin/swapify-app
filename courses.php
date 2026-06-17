<?php
    session_start();
    include "includes/header.php";
    include "includes/db_connect.php";

    $user_id = $_SESSION['user_id'];
    $search = "";
    
    $filter_owned = " AND c.course_id NOT IN (SELECT course_id FROM tr_coursepurchase WHERE user_id = '$user_id')";

    if (isset($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $query = "SELECT c.*, u.name as author_name, cat.category_name 
                  FROM ms_courses c
                  JOIN ms_users u ON c.user_id = u.user_id
                  JOIN ms_coursecategory cat ON c.category_id = cat.category_id
                  WHERE (c.title LIKE '%$search%' OR cat.category_name LIKE '%$search%')
                  $filter_owned
                  ORDER BY c.upload_date DESC";
    } else {
        $query = "SELECT c.*, u.name as author_name, cat.category_name 
                  FROM ms_courses c
                  JOIN ms_users u ON c.user_id = u.user_id
                  JOIN ms_coursecategory cat ON c.category_id = cat.category_id
                  WHERE 1=1 $filter_owned
                  ORDER BY c.upload_date DESC";
    }

    $result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swapify - Courses</title>
    <link rel="stylesheet" href="css/courses.css">
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
            <button class="active">Explore Courses</button>
            <a href="my-courses.php">
                <button>My Courses</button>
            </a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'expert'): ?>
                <a href="uploaded-courses.php">
                    <button>Uploaded Courses</button>
                </a>
            <?php endif; ?>
        </div>

        <section>
            <h2 class="section-title">
                <?php echo empty($search) ? "Recommended Courses" : "Search Results for '".htmlspecialchars($search)."'"; ?>
            </h2>

            <div class="course-grid">
                <?php 
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) { 
                ?>
                    <div class="course-card">
                         <img src="uploads/thumbnails/<?php echo isset($row['thumbnail']) ? htmlspecialchars($row['thumbnail']) : ''; ?>" 
                            onerror="this.src='https:/images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=500'" 
                            alt="course">
                            
                        <div class="card-body">
                            <span class="category"><?php echo htmlspecialchars($row['category_name']); ?></span>
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p class="author">By <strong><?php echo htmlspecialchars($row['author_name']); ?></strong></p>
                            <p class="desc">
                                <?php 
                                    echo htmlspecialchars(substr($row['description'], 0, 80)) . '...'; 
                                ?>
                            </p>
                            
                            <button class="btn-action btn-view" style="display: flex; justify-content: center; align-items: center; gap: 8px; background-color: #FF6E30; color: white; border: none;" 
                                onclick="confirmPurchase(<?php echo $row['course_id']; ?>, '<?php echo addslashes($row['title']); ?>', <?php echo $row['credits_price']; ?>)">
                                Buy this course with <?php echo number_format($row['credits_price']); ?> <img src="images/Coin.png" alt="Coin" class="coin-img">
                            </button>
                        </div>
                    </div>
                <?php 
                    } 
                } else {
                    echo "<p style='text-align:center; width:100%; grid-column: 1/-1;'>No courses found.</p>";
                }
                ?>
            </div>
        </section>
    </main>

    <script>
    function confirmPurchase(id, title, price) {
        Swal.fire({
            title: 'Confirm Purchase',
            text: "Unlock '" + title + "' for " + price + " credits?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#FF6B35',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Buy it!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Deducting credits...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const formData = new FormData();
                formData.append('course_id', id);

                fetch('purchase_course_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close(); 
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Course unlocked. You can find it in My Courses.',
                            icon: 'success',
                            confirmButtonColor: '#FF6B35'
                        }).then(() => {
                            location.reload(); 
                        });
                    } else {
                        Swal.fire('Failed', data.message, 'error');
                    }
                })
                .catch(() => {
                    Swal.close();
                    Swal.fire('Error', 'Something went wrong', 'error');
                });
            }
        })
    }
    </script>
</body>
</html>