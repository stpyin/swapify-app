<?php
session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $portfolio_link = mysqli_real_escape_string($conn, $_POST['portfolio_link']);
    $explanation = mysqli_real_escape_string($conn, $_POST['explanation']);

    $check_stmt = $conn->prepare("SELECT verification_id, status FROM tr_userverification WHERE user_id = ? AND status IN ('Pending', 'Verified')");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $existing = $check_result->fetch_assoc();
        if ($existing['status'] == 'Verified') {
            $error = "You are already verified!";
        } else {
            $error = "You already have a pending verification request!";
        }
    } else {
        $resume_file_name = "";
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $allowed_extensions = ['pdf', 'doc', 'docx'];
            $file_extension = strtolower(pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION));
            $file_size = $_FILES["resume"]["size"];
            if (!in_array($file_extension, $allowed_extensions)) {
                $error = "Only PDF, DOC, and DOCX files are allowed!";
            }
            elseif ($file_size > 5 * 1024 * 1024) {
                $error = "File size must be less than 5MB!";
            }
            else {
                $target_dir = "uploads/resumes/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $resume_file_name = time() . "_" . $user_id . "_resume." . $file_extension;
                $target_file = $target_dir . $resume_file_name;
                
                if (!move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file)) {
                    $error = "Failed to upload file!";
                }
            }
        } else {
            $error = "Please upload your resume!";
        }

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO tr_userverification (user_id, cv_file, portfolio_link, explanation, status) VALUES (?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("isss", $user_id, $resume_file_name, $portfolio_link, $explanation);

            if ($stmt->execute()) {
                $success = "Verification request submitted successfully! We'll review it within 3-5 business days.";
            } else {
                $error = "An error occurred while submitting your request. Please try again.";
                if (file_exists($target_file)) {
                    unlink($target_file);
                }
            }
            $stmt->close();
        }
    }
    $check_stmt->close();
}

$verification_status = null;
$status_stmt = $conn->prepare("SELECT status, submitted_at FROM tr_userverification WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
$status_stmt->bind_param("i", $user_id);
$status_stmt->execute();
$result = $status_stmt->get_result();
if ($result->num_rows > 0) {
    $verification_status = $result->fetch_assoc();
}
$status_stmt->close();

$form_disabled = false;
$status_message = "Not Submitted";
$status_class = "status-not-submitted";

if ($verification_status) {
    $db_status = trim(strtolower($verification_status['status'])); 

    if ($db_status == 'approved') {
        $form_disabled = true;
        $status_message = "Verified";
        $status_class = "status-verified";
    } elseif ($db_status == 'pending') {
        $form_disabled = true;
        $status_message = "Pending Review";
        $status_class = "status-pending";
    } elseif ($db_status == 'rejected') {
        $status_message = "Rejected - Resubmit";
        $status_class = "status-rejected";
    } else {
        $status_message = "Status: " . htmlspecialchars($verification_status['status']);
        $status_class = "status-not-submitted";
    }
}

include "includes/header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=SF+Pro&display=swap" rel="stylesheet" />
    <title>Swapify - Account Verification</title>
    <link rel="stylesheet" href="css/verification.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .status-verified { color: #10b981; font-weight: bold; }
        .status-pending { color: #f59e0b; font-weight: bold; }
        .status-rejected { color: #ef4444; font-weight: bold; }
        .status-not-submitted { color: #6b7280; font-weight: bold; }
        .form-disabled { opacity: 0.6; pointer-events: none; }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Account Verification</h1>
                <p class="page-subtitle">Submit your credentials to become a verified Expert.</p>
                <div class="title-underline"></div>
            </div>
            <div class="status-badge">
                <div class="status-item">
                    Current Status: <span class="<?php echo $status_class; ?>"><?php echo $status_message; ?></span>
                </div>
            </div>
        </div>

        <?php if ($form_disabled && $verification_status['status'] == 'Verified'): ?>
            <div style="background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <h3 style="color: #10b981; margin: 0;">🎉 Congratulations! You are verified!</h3>
                <p style="margin: 10px 0 0 0; color: #065f46;">You can now offer your skills as a verified provider.</p>
            </div>
        <?php elseif ($form_disabled && $verification_status['status'] == 'Pending'): ?>
            <div style="background: #fef3c7; border: 2px solid #f59e0b; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <h3 style="color: #f59e0b; margin: 0;">⏳ Verification Under Review</h3>
                <p style="margin: 10px 0 0 0; color: #92400e;">Your request is being reviewed. We'll notify you within 3-5 business days.</p>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="left-column">
                <form id="verificationForm" action="" method="POST" enctype="multipart/form-data" class="<?php echo $form_disabled ? 'form-disabled' : ''; ?>">
                    <div class="resume-card">
                        <h2 class="card-title">Professional Resume</h2>
                        <label class="upload-area" id="uploadArea">
                            <div class="upload-icon">↑</div>
                            <p class="upload-text"><strong>Upload a file</strong> or drag and drop</p>
                            <p class="upload-hint">PDF or DOCX up to 5MB</p>
                            <p class="file-name" id="fileName"></p>
                            <input type="file" name="resume" id="resumeFile" accept=".pdf,.doc,.docx" required <?php echo $form_disabled ? 'disabled' : ''; ?>>
                        </label>
                    </div>

                    <div class="details-card">
                        <h2 class="card-title">Additional Details</h2>
                        
                        <div class="form-group">
                            <label class="form-label" for="portfolioLink">Portfolio Link/Website</label>
                            <div class="input-wrapper">
                                <span class="input-icon">🔗</span>
                                <input 
                                    type="url" 
                                    id="portfolioLink"
                                    name="portfolio_link" 
                                    class="form-input with-icon" 
                                    placeholder="https://dribbble.com/yourusername"
                                    required
                                    <?php echo $form_disabled ? 'disabled' : ''; ?>
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="explanation">Supporting Explanation</label>
                            <textarea 
                                id="explanation"
                                name="explanation" 
                                class="form-input" 
                                placeholder="Tell us about your expertise and why you're a good fit for verification..."
                                required
                                <?php echo $form_disabled ? 'disabled' : ''; ?>
                            ></textarea>
                        </div>

                        <button type="submit" class="submit-btn" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                            <?php echo $form_disabled ? 'Form Disabled' : 'Submit for Verification'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="guidelines-card">
                <h2 class="guidelines-title">Verification Guidelines</h2>
                <p class="guidelines-subtitle">To ensure quality on Swapify, we require the following from all verified providers:</p>

                <div class="guideline-item">
                    <div class="guideline-icon">🆔</div>
                    <div class="guideline-content">
                        <h4>Valid Identification</h4>
                        <p>Government-issued ID required for identity proof.</p>
                    </div>
                </div>

                <div class="guideline-item">
                    <div class="guideline-icon">📄</div>
                    <div class="guideline-content">
                        <h4>Professional Resume</h4>
                        <p>Up-to-date CV highlighting relevant skills.</p>
                    </div>
                </div>

                <div class="guideline-item">
                    <div class="guideline-icon">✅</div>
                    <div class="guideline-content">
                        <h4>Experience</h4>
                        <p>Minimum 1 month of proven experience in your field.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('resumeFile');
        const fileName = document.getElementById('fileName');
        const uploadArea = document.getElementById('uploadArea');

        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); 
                
                if (fileSize > 5) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    fileName.textContent = '';
                    return;
                }
                
                fileName.textContent = `✓ ${file.name} (${fileSize} MB)`;
            }
        });

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            <?php if (!$form_disabled): ?>
            this.style.borderColor = '#FF6B35';
            this.style.background = '#FFF9F7';
            <?php endif; ?>
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#D1D5DB';
            this.style.background = '#FAFAFA';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            <?php if (!$form_disabled): ?>
            this.style.borderColor = '#D1D5DB';
            this.style.background = '#FAFAFA';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
            <?php endif; ?>
        });

        <?php if($success): ?>
            Swal.fire({
                title: 'Success!',
                text: '<?php echo $success; ?>',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#4A90E2'
            }).then(() => {
                window.location.href = 'verification.php';
            });
        <?php endif; ?>

        <?php if($error): ?>
            Swal.fire({
                title: 'Error!',
                text: '<?php echo addslashes($error); ?>',
                icon: 'error',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#d33'
            });
        <?php endif; ?>
    </script>
</body>
</html>