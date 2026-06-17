<?php
session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$summary_query = "SELECT 
                    SUM(balance) as total_circulation, 
                    AVG(balance) as avg_balance,
                    COUNT(user_id) as total_wallets
                  FROM ms_usercredits";
$summary_res = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_res);

$credits_query = "SELECT 
                    u.user_id, 
                    u.name, 
                    u.email, 
                    u.profile_picture,
                    u.role,
                    uc.balance, 
                    uc.update_at
                  FROM ms_users u
                  LEFT JOIN ms_usercredits uc ON u.user_id = uc.user_id
                  ORDER BY uc.balance DESC"; 
$credits_result = mysqli_query($conn, $credits_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Credits - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin-detail.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
            <h1>System Credits</h1>
            <p>Monitoring the total distribution of credits across all users</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="summary-item economy">
                <label style="color: grey;">Total Circulation</label>
                <h2 style="color: #10B981;">🪙 <?= number_format($summary['total_circulation'] ?? 0) ?> Credits</h2>
            </div>
            <div class="summary-item">
                <label style="color: grey;">Average User Balance</label>
                <h2 style="color: #10B981;">🪙 <?= number_format($summary['avg_balance'] ?? 0, 1) ?></h2>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Balance Distribution</th>
                        <th>Current Balance</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while($row = mysqli_fetch_assoc($credits_result)): 
                        $max_scale = $summary['total_circulation'] > 0 ? $summary['total_circulation'] : 1;
                        $percentage = ($row['balance'] / $max_scale) * 100 * 5; 
                    ?>
                    <tr>
                        <td><span style="color: #9CA3AF; font-weight: 700;">#<?= $rank++ ?></span></td>
                        <td>
                            <div class="user-cell">
                                <?php 
                                $profilePic = !empty($row['profile_picture']) ? "uploads/" . $row['profile_picture'] : "https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&background=7C3AED&color=fff";
                                ?>
                                <img src="<?= $profilePic ?>" alt="" class="user-avatar">
                                <div>
                                    <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                                    <small style="color: #6B7280;"><?= htmlspecialchars($row['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-<?= $row['role'] ?>"><?= ucfirst($row['role']) ?></span></td>
                        <td>
                            <div class="wealth-indicator">
                                <div class="wealth-bar" style="width: <?= min($percentage, 100) ?>%"></div>
                            </div>
                            <small style="color: #9CA3AF;"><?= number_format(($row['balance'] / $max_scale) * 100, 1) ?>% share</small>
                        </td>
                        <td><span class="credit-value"><?= number_format($row['balance'] ?? 0) ?></span></td>
                        <td>
                            <span style="font-size: 13px; color: #4B5563;">
                                <?= $row['update_at'] ? date('d M Y', strtotime($row['update_at'])) : 'No activity' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>