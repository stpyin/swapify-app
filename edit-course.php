<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'includes/db_connect.php'; 

$message = "";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$q_user = "SELECT name, profile_picture FROM ms_users WHERE user_id = $user_id";
$res_user = mysqli_query($conn, $q_user);
$user_data = mysqli_fetch_assoc($res_user);

$user_img = $user_data['profile_picture']; 

$final_avatar = (!empty($user_img) && file_exists("uploads/" . $user_img)) 
                ? "uploads/" . $user_img 
                : "https://ui-avatars.com/api/?name=" . urlencode($user_data['name']) . "&background=FF6B35&color=fff";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Course ID is missing. Please select a course from the list.");
}
$course_id = (int)$_GET['id'];

if (isset($_POST['update_course'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc  = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (int)$_POST['credits_price'];
    $cat_id = (int)$_POST['category_id'];

    $thumbnail_query_part = ""; 
    
    if (!empty($_FILES['thumbnail']['name'])) {
        $target_dir = "uploads/thumbnails/"; 
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        $filename = time() . "_" . basename($_FILES["thumbnail"]["name"]);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $q_old = mysqli_query($conn, "SELECT thumbnail FROM ms_courses WHERE course_id = $course_id");
            $d_old = mysqli_fetch_assoc($q_old);
            if ($d_old && !empty($d_old['thumbnail']) && file_exists($d_old['thumbnail'])) {
                unlink($d_old['thumbnail']);
            }

            $thumbnail_query_part = ", thumbnail = '$target_file'";
        } else {
            echo "<script>alert('Error uploading thumbnail image.');</script>";
        }
    }

    $sql = "UPDATE ms_courses SET 
            title = '$title', 
            description = '$desc', 
            credits_price = '$price',
            category_id = '$cat_id'
            $thumbnail_query_part
            WHERE course_id = $course_id";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Course updated successfully!'); window.location.href='edit-course.php?id=$course_id';</script>";
    } else {
        echo "<script>alert('Error updating database: " . mysqli_error($conn) . "');</script>";
    }
}

if (isset($_POST['delete_course'])) {
    $query_file = "SELECT video, thumbnail FROM ms_courses WHERE course_id = $course_id";
    $result_file = mysqli_query($conn, $query_file);
    $data_file = mysqli_fetch_assoc($result_file);

    if ($data_file) {
        $video_path = $data_file['video'];
        if (!empty($video_path)) {
            if (file_exists($video_path)) {
                unlink($video_path);
            } 
            elseif (file_exists("uploads/videos/" . $video_path)) {
                unlink("uploads/videos/" . $video_path);
            }
        }

        $thumb_path = $data_file['thumbnail'];
        if (!empty($thumb_path)) {
            if (file_exists($thumb_path)) {
                unlink($thumb_path);
            } 
            elseif (file_exists("uploads/thumbnails/" . $thumb_path)) {
                unlink("uploads/thumbnails/" . $thumb_path);
            }
        }
    }

    mysqli_query($conn, "DELETE FROM tr_coursepurchase WHERE course_id = $course_id");
    $sql_del = "DELETE FROM ms_courses WHERE course_id = $course_id";
    if (mysqli_query($conn, $sql_del)) {
        echo "<script>alert('Course deleted permanently!'); window.location.href='edit-course.php?course_id=$course_id'';</script>";
        exit;
    } else {
        echo "<script>alert('Delete failed: " . mysqli_error($conn) . "');</script>";
    }
}

$query = "SELECT * FROM ms_courses WHERE course_id = $course_id";
$result = mysqli_query($conn, $query);
$course = mysqli_fetch_assoc($result);

if (!$course) { die("Course not found."); }

$query_cats = mysqli_query($conn, "SELECT * FROM ms_coursecategory");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Course - Swapify</title>
    <link rel="stylesheet" href="css/upload-course.css">
    <link rel="stylesheet" href="css/view-course.css">
    <link rel="stylesheet" href="css/edit-course.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header class="header-minimal">
    <div class="logo-container">
        <div class="logo-icon">
            <img src="images/v91_89.png" alt="Swapify Logo">
        </div>
        <span class="logo-text">SWAPIFY</span>
    </div>
    
    <div class="header-right">
        <div class="user-profile">
            <span class="user-name"><?php echo htmlspecialchars($user_data['name']); ?></span>
            <div class="avatar-container">
                <img src="<?php echo $final_avatar; ?>" alt="Profile" class="header-avatar">
            </div>
        </div>
    </div>
</header>

<div class="main-wrapper">
    <div class="top-nav">
        <a href="uploaded-courses.php" class="back-link">
            <span class="arrow">&larr;</span> Back to Uploaded Course
        </a>
    </div>

    <div class="container">
        
        <div class="header-section">
            <div class="title-area">
                <h1>Manage Course</h1>
                <p class="subtitle">Edit details or preview your video content.</p>
            </div>
            
            <form action="edit-course.php?id=<?= $course_id ?>" method="POST" onsubmit="return confirm('Are you sure you want to DELETE this course? This cannot be undone.');">
                <button type="submit" name="delete_course" class="btn btn-danger">Delete Course</button>
            </form>
        </div>

        <div class="video-section">
            <div class="video-box">
                <?php 
                    $video_db = $course['video'];
                    $video_src = "";
                    $video_error = "";

                    if (!empty($video_db) && file_exists($video_db)) {
                        $video_src = $video_db;
                    }
                    elseif (!empty($video_db) && file_exists("uploads/videos/" . $video_db)) {
                        $video_src = "uploads/videos/" . $video_db;
                    }
                    else {
                        $video_error = "Video file not found. (DB Path: " . htmlspecialchars($video_db) . ")";
                    }
                ?>

                <?php if ($video_src): ?>
                    <video controls width="100%">
                        <source src="<?php echo htmlspecialchars($video_src); ?>" type="video/mp4">
                        Your browser does not support HTML5 video.
                    </video>
                <?php else: ?>
                    <div style="height: 400px; background: #000; color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
                        <p style="font-size: 18px; margin-bottom: 10px;">⚠️ Video Error</p>
                        <p style="font-size: 14px; color: #aaa;"><?php echo $video_error; ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <p class="video-label">Current Video Preview</p>
        </div>

        <hr class="divider">

        <form method="POST" enctype="multipart/form-data" class="edit-form">
            
            <div class="form-row">
                <div class="form-group half">
                    <label>Course Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($course['title']); ?>" required placeholder="Input title...">
                </div>

                <div class="form-group half">
                    <label>Category</label>
                    <div class="select-wrapper">
                        <select name="category_id" class="form-control" required>
                            <option value="">-- Select Category --</option>
                            <?php while($cat = mysqli_fetch_assoc($query_cats)): ?>
                                <option value="<?php echo $cat['category_id']; ?>" 
                                    <?php if($cat['category_id'] == $course['category_id']) echo 'selected'; ?>>
                                    <?php echo $cat['category_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Thumbnail</label>
                    <div class="thumbnail-preview-box">
                        <?php 
                            $db_path = $course['thumbnail'];
                            $final_src = "";
                            $status_text = "";

                            if (empty($db_path)) {
                                $status_text = "Database kosong (Belum ada gambar).";
                            } 
                            elseif (file_exists($db_path)) {
                                $final_src = $db_path;
                            } 
                            elseif (file_exists("uploads/thumbnails/" . $db_path)) {
                                $final_src = "uploads/thumbnails/" . $db_path;
                            }
                            else {
                                $status_text = "File tidak ditemukan di folder.";
                            }
                        ?>

                        <?php if (!empty($final_src)): ?>
                            <img src="<?php echo htmlspecialchars($final_src); ?>" class="current-thumbnail" alt="Thumbnail Image">
                        <?php else: ?>
                            <div class="no-thumb">
                                <span>No Image</span>
                            </div>
                        <?php endif; ?>

                        <input type="file" name="thumbnail" class="file-input" accept="image/*">
                    </div>

                </div>

                <div class="form-group half">
                    <label>Credits Price</label>
                    <input type="number" name="credits_price" class="form-control" value="<?php echo $course['credits_price']; ?>" required placeholder="0">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" required placeholder="Input description..."><?php echo htmlspecialchars($course['description']); ?></textarea>
            </div>

            <div class="action-footer">
                <button type="submit" name="update_course" class="btn btn-primary">Save Changes</button>
            </div>
        </form>

    </div>
</div>

</body>
</html>