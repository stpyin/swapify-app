<?php
session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$users_query = "SELECT u.*, uc.balance, 
                (SELECT COUNT(*) FROM tr_swapsession ss 
                 JOIN tr_swapagreement sa ON ss.agreement_id = sa.agreement_id 
                 WHERE (sa.user_a_id = u.user_id OR sa.user_b_id = u.user_id) 
                 AND ss.status = 'completed') as sessions_count
                FROM ms_users u
                LEFT JOIN ms_usercredits uc ON u.user_id = uc.user_id
                ORDER BY u.user_id ASC";
$users_result = mysqli_query($conn, $users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin-detail.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
            <h1>All Users</h1>
            <p>Complete list of registered users</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($users_result, 0);
                    while($user = mysqli_fetch_assoc($users_result)): 
                    ?>
                    <tr>
                        <td><?= $user['user_id'] ?></td>
                        <td>
                            <div class="user-cell">
                                <?php 
                                $profilePic = !empty($user['profile_picture']) ? "uploads/" . $user['profile_picture'] : "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=7C3AED&color=fff";
                                ?>
                                <img src="<?= $profilePic ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="user-avatar">
                                <strong><?= htmlspecialchars($user['name']) ?></strong>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone_number'] ?? '-') ?></td>
                        <td><span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                        <td><?= date('d M Y', strtotime($user['joined_date'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>