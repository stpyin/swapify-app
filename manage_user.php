<?php
session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $verification_id = intval($_POST['verification_id']);
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action == 'approve') {
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE tr_userverification SET status = 'approved', reviewed_at = NOW() WHERE verification_id = ?");
            $stmt1->bind_param("i", $verification_id);
            $stmt1->execute();

            $stmt2 = $conn->prepare("UPDATE ms_users SET role = 'expert' WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();

            $conn->commit();
            $success = "User successfully become an Expert!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed processing approval: " . $e->getMessage();
        }
    } 
    elseif ($action == 'reject') {
        $stmt = $conn->prepare("UPDATE tr_userverification SET status = 'rejected', reviewed_at = NOW() WHERE verification_id = ?");
        $stmt->bind_param("i", $verification_id);
        
        if ($stmt->execute()) {
            $success = "Verification request has been rejected.";
        } else {
            $error = "Failed rejecting request: " . $conn->error;
        }
        $stmt->close();
    }
}

$allowed_filters = ['pending', 'approved', 'rejected', 'all'];
$filter = isset($_GET['filter']) && in_array($_GET['filter'], $allowed_filters) ? $_GET['filter'] : 'pending';

$query = "SELECT v.*, u.name, u.email, u.role, u.profile_picture 
          FROM tr_userverification v 
          JOIN ms_users u ON v.user_id = u.user_id " . 
          ($filter !== 'all' ? "WHERE v.status = '$filter' " : "") . 
          "ORDER BY v.submitted_at DESC";
$result = mysqli_query($conn, $query);

$stats_result = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM tr_userverification");
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Management - SkillSwap</title>
    <link rel="stylesheet" href="css/admin-dashboard.css">
    <link rel="stylesheet" href="css/manage_user.css?v=7">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .dash-stat-card:not(.total-card):hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

    <?php include "includes/header.php"; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Verification Center</h1>
            <p>Review and manage user applications for Expert status.</p>
            
            <div class="filter-tabs">
                <?php foreach(['pending', 'approved', 'rejected', 'all'] as $f): ?>
                    <a href="?filter=<?= $f ?>" class="filter-tab <?= ($filter == $f) ? 'active' : ''; ?>">
                        <?= ucfirst($f) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="dash-stat-card total-card">
                <div class="dash-stat-label">Total Applications</div>
                <div class="dash-stat-value"><?= number_format($stats['total']) ?></div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-label" style="color:#F59E0B">Pending Review</div>
                <div class="dash-stat-value"><?= number_format($stats['pending']) ?></div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-label" style="color:#10B981">Total Approved</div>
                <div class="dash-stat-value"><?= number_format($stats['approved']) ?></div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-label" style="color:#EF4444">Total Rejected</div>
                <div class="dash-stat-value"><?= number_format($stats['rejected']) ?></div>
            </div>
        </div>

        <div class="verification-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="section-card">
                        <div class="section-header" style="border-bottom: 1px solid #F3F4F6;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="dash-activity-img-container" style="margin:0">
                                    <?php $pic = !empty($row['profile_picture']) ? "uploads/".$row['profile_picture'] : "https://ui-avatars.com/api/?name=".urlencode($row['name']); ?>
                                    <img src="<?= $pic ?>" class="dash-activity-avatar">
                                </div>
                                <div>
                                    <h2 style="margin:0; font-size: 18px;"><?= htmlspecialchars($row['name']) ?></h2>
                                    <div class="activity-subtitle"><?= htmlspecialchars($row['email']) ?> • Current Role: <strong><?= ucfirst($row['role']) ?></strong></div>
                                </div>
                            </div>
                            <span class="status-pill pill-<?= $row['status'] ?>"><?= $row['status'] ?></span>
                        </div>

                        <div class="card-details">
                            <div class="details-left">
                                <div style="margin-bottom: 20px;">
                                    <div class="dash-stat-label" style="margin-bottom:8px">Professional Documents</div>
                                    <a href="uploads/resumes/<?= $row['cv_file'] ?>" target="_blank" class="btn-download">
                                        📄 View Resume (CV)
                                    </a>
                                </div>
                                <div>
                                    <div class="dash-stat-label" style="margin-bottom:8px">Portfolio Link</div>
                                    <?php if($row['portfolio_link']): ?>
                                        <a href="<?= htmlspecialchars($row['portfolio_link']) ?>" target="_blank" style="color:#7C3AED; font-weight:600; text-decoration:none; font-size:14px">
                                            🔗 Visit Portfolio Website →
                                        </a>
                                    <?php else: ?>
                                        <span class="activity-subtitle">No link provided</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="details-right">
                                <div class="dash-stat-label" style="margin-bottom:8px">Motivation & Expertise</div>
                                <div class="motivation-text">
                                    <?= nl2br(htmlspecialchars($row['explanation'])) ?>
                                </div>
                                <div class="activity-subtitle" style="margin-top:10px">
                                    Submitted on: <?= date('d M Y, H:i', strtotime($row['submitted_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($row['status'] == 'pending'): ?>
                            <form method="POST">
                                <input type="hidden" name="verification_id" value="<?= $row['verification_id'] ?>">
                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                <div class="action-btns">
                                    <button type="button" class="btn-core btn-approve" onclick="confirmAction(this, 'approve')">Approve Request</button>
                                    <button type="button" class="btn-core btn-reject" onclick="confirmAction(this, 'reject')">Reject Application</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="section-card" style="text-align: center; padding: 60px;">
                    <div style="font-size: 40px; margin-bottom: 20px;">☕</div>
                    <h2 style="color: #9CA3AF;">All caught up!</h2>
                    <p class="activity-subtitle">No data found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
    <?php if($success): ?>
        Swal.fire({ icon: 'success', title: 'Success', text: '<?= $success ?>', confirmButtonColor: '#7C3AED' });
    <?php endif; ?>

    <?php if($error): ?>
        Swal.fire({ icon: 'error', title: 'Error', text: '<?= $error ?>' });
    <?php endif; ?>

    function confirmAction(button, type) {
        const form = button.closest('form');
        const isApprove = type === 'approve';
        
        Swal.fire({
            title: isApprove ? 'Approve Expert?' : 'Reject Application?',
            text: isApprove ? "This user will gain Expert privileges immediately." : "This application will be marked as rejected.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: isApprove ? '#10B981' : '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: isApprove ? 'Yes, Approve' : 'Yes, Reject',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const hiddenAction = document.createElement('input');
                hiddenAction.type = 'hidden';
                hiddenAction.name = 'action';
                hiddenAction.value = type;
                form.appendChild(hiddenAction);
                form.submit();
            }
        });
    }
</script>
</body>
</html>