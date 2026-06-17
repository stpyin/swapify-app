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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (empty($_FILES) || !isset($_FILES['video_file'])) {
        $message = "<div class='alert error'>
            <strong>FATAL ERROR:</strong> No files received.<br>
            Your video file likely exceeds the <code>post_max_size</code> limit in XAMPP.
        </div>";
    } elseif ($_FILES['video_file']['error'] !== 0) {
        $errorCode = $_FILES['video_file']['error'];
        $errorMessages = [
            1 => 'File exceeds upload_max_filesize directive in php.ini',
            2 => 'File exceeds MAX_FILE_SIZE specified in HTML form',
            3 => 'File was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'A PHP extension stopped the file upload',
        ];
        $errText = $errorMessages[$errorCode] ?? "Unknown Error";
        $message = "<div class='alert error'><strong>Upload Failed:</strong> $errText</div>";
    
    } else {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $category_id = $_POST['category_id'];
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = $_POST['price'];

        $videoDir = "uploads/videos/";
        $thumbDir = "uploads/thumbnails/";

        if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }
        if (!file_exists($videoDir)) { mkdir($videoDir, 0777, true); }
        if (!file_exists($thumbDir)) { mkdir($thumbDir, 0777, true); }

        function generateUniqueName($filename, $prefix) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            return $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
        }

        $videoName = $_FILES['video_file']['name'];
        $videoTmp = $_FILES['video_file']['tmp_name'];
        $videoNewName = generateUniqueName($videoName, 'vid');
        $videoUploadPath = $videoDir . $videoNewName;

        $thumbName = $_FILES['thumb_file']['name'];
        $thumbTmp = $_FILES['thumb_file']['tmp_name'];
        $thumbNewName = generateUniqueName($thumbName, 'img');
        $thumbUploadPath = $thumbDir . $thumbNewName;

        if (empty($message)) {
            if (move_uploaded_file($videoTmp, $videoUploadPath) && move_uploaded_file($thumbTmp, $thumbUploadPath)) {
                
                $sql = "INSERT INTO ms_courses (user_id, category_id, title, video, thumbnail, description, credits_price) 
                        VALUES ('$user_id', '$category_id', '$title', '$videoNewName', '$thumbNewName', '$description', '$price')";

                if (mysqli_query($conn, $sql)) {
                    header("Location: uploaded-courses.php");
                    exit();
                } else {
                    $message = "<div class='alert error'>Database Error: " . mysqli_error($conn) . "</div>";
                }

            } else {
                $sysErr = error_get_last();
                $message = "<div class='alert error'>
                    <strong>File Move Error:</strong> Failed to save files.<br>
                    Detail: " . $sysErr['message'] . "
                </div>";
            }
        }
    }
}


$query_cat = "SELECT * FROM ms_coursecategory";
$result_cat = mysqli_query($conn, $query_cat);

if (!$result_cat) {
    die("Error fetching categories: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Course - Swapify</title>
    <link rel="stylesheet" href="css/upload-course.css?v=1">
    <link rel="stylesheet" href="css/view-course.css">
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


<div class="container">
    <a href="uploaded-courses.php" class="back-link">
        <span class="arrow">&larr;</span> Back to Uploaded Course
    </a>
    <div class="page-header">
        <div class="status-badge">Ready to share?</div>
        <h1>Upload Course</h1>
        <p>Share your expertise and earn credits.</p>
    </div>

    <?= $message ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="grid-layout">
            
            <div class="main-content">
                
                <div class="card">
                    <div class="section-title">Course Video</div>
                    <div class="upload-area">
                        <input type="file" name="video_file" accept="video/*" required>
                        <img src="images/upload-logo.png" alt="Upload" class="upload-icon-img">
                        <div><span class="upload-text">Upload Video</span> or drag and drop</div>
                        <div class="upload-sub">MP4 or WEBM (Max 500MB)</div>
                    </div>
                </div>

                <div class="card">
                    <div class="section-title">Course Details</div>
                    
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Mastering React JS" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php 
                            if (mysqli_num_rows($result_cat) > 0) {
                                while($cat = mysqli_fetch_assoc($result_cat)) {
                                    echo '<option value="'.$cat['category_id'].'">'.$cat['category_name'].'</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" placeholder="Explain what students will learn..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Price (Credits)</label>
                        <input type="number" name="price" class="form-control" placeholder="150" min="0" required>
                    </div>

                     <div class="form-group">
                        <label class="form-label">Thumbnail Image</label>
                        <input type="file" name="thumb_file" class="form-control" accept="image/*" required>
                        <small class="helper-text">Recommended size: 1280x720 px (JPG/PNG)</small>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Publish Course</button>
            </div>

            <div class="sidebar">
                <div class="card">
                    <div class="section-title">Upload Guidelines</div>
                    
                    <div class="guide-item">
                        <div class="guide-icon">🎥</div>
                        <div class="guide-text">
                            <h4>High Quality Video</h4>
                            <p>Ensure your video is at least 720p resolution for a better learning experience.</p>
                        </div>
                    </div>

                    <div class="guide-item">
                        <div class="guide-icon">🖼️</div>
                        <div class="guide-text">
                            <h4>Clear Thumbnail</h4>
                            <p>Upload a catchy thumbnail that represents your topic clearly.</p>
                        </div>
                    </div>

                    <div class="guide-item">
                        <div class="guide-icon">📝</div>
                        <div class="guide-text">
                            <h4>Detailed Description</h4>
                            <p>Explain the prerequisites and what value users will get from this course.</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>
</div>

</body>
</html>