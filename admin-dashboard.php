<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$users_result = mysqli_query($conn, "SELECT COUNT(*) as total_users, SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as total_regular_users, SUM(CASE WHEN role = 'expert' THEN 1 ELSE 0 END) as total_experts, SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins FROM ms_users");
$users_stats = mysqli_fetch_assoc($users_result);

$credits_result = mysqli_query($conn, "SELECT SUM(balance) as total_credits FROM ms_usercredits");
$credits_stats = mysqli_fetch_assoc($credits_result);

$revenue_result = mysqli_query($conn, "SELECT COUNT(*) as total_topups, SUM(tp.price_money) as total_revenue FROM tr_credittopup ct JOIN ms_topuppackages tp ON ct.package_id = tp.package_id WHERE ct.confirmed_at IS NOT NULL");
$revenue_stats = mysqli_fetch_assoc($revenue_result);

$sessions_result = mysqli_query($conn, "SELECT COUNT(*) as total_sessions, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions, SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_sessions FROM tr_swapsession");
$sessions_stats = mysqli_fetch_assoc($sessions_result);

$voucher_result = mysqli_query($conn, "SELECT COUNT(*) as total_vouchers, SUM(stock) as total_stock, (SELECT COUNT(*) FROM tr_redeemvoucher) as total_redeemed FROM ms_voucher");
$voucher_stats = mysqli_fetch_assoc($voucher_result);

$course_result = mysqli_query($conn, "SELECT COUNT(*) as total_courses, (SELECT COUNT(*) FROM tr_coursepurchase) as total_purchases FROM ms_courses");
$course_stats = mysqli_fetch_assoc($course_result);
$recent_topups = mysqli_query($conn, "SELECT ct.topup_id, u.name, u.profile_picture, tp.name as package_name, tp.price_money, ct.created_at FROM tr_credittopup ct JOIN ms_users u ON ct.user_id = u.user_id JOIN ms_topuppackages tp ON ct.package_id = tp.package_id ORDER BY ct.created_at DESC LIMIT 5");
$recent_purchases = mysqli_query($conn, "SELECT cp.purchase_id, u.name, c.title, c.credits_price, cp.created_at FROM tr_coursepurchase cp JOIN ms_users u ON cp.user_id = u.user_id JOIN ms_courses c ON cp.course_id = c.course_id ORDER BY cp.created_at DESC LIMIT 5");
$recent_vouchers = mysqli_query($conn, "SELECT rv.redeem_id, u.name, v.voucher_name, v.credits_price, rv.redeem_date FROM tr_redeemvoucher rv JOIN ms_users u ON rv.user_id = u.user_id JOIN ms_voucher v ON rv.voucher_id = v.voucher_id ORDER BY rv.redeem_date DESC LIMIT 5");
$pending_verifications = mysqli_query($conn, "SELECT uv.verification_id, u.name, u.email, uv.submitted_at FROM tr_userverification uv JOIN ms_users u ON uv.user_id = u.user_id WHERE uv.status = 'pending' ORDER BY uv.submitted_at DESC");
$top_users = mysqli_query($conn, "SELECT u.name, u.email, uc.balance, u.role FROM ms_usercredits uc JOIN ms_users u ON uc.user_id = u.user_id ORDER BY uc.balance DESC LIMIT 5");
$monthly_revenue = mysqli_query($conn, "SELECT DATE_FORMAT(ct.created_at, '%Y-%m') as month, COUNT(*) as transactions, SUM(tp.price_money) as revenue FROM tr_credittopup ct JOIN ms_topuppackages tp ON ct.package_id = tp.package_id WHERE ct.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(ct.created_at, '%Y-%m') ORDER BY month DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SkillSwap</title>
    <link rel="stylesheet" href="css/admin-dashboard.css?v=4">
</head>
<body>

    <?php include "includes/header.php"; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back! Here's what's happening with SkillSwap today.</p>
        </div>

        <div class="stats-grid">
            <a href="admin_users_detail.php" class="stat-link">
                <div class="dash-stat-card dash-purple">
                    <div class="dash-stat-icon dash-purple">👥</div>
                    <div class="dash-stat-label">Total Users</div>
                    <div class="dash-stat-value"><?= number_format($users_stats['total_users']) ?></div>
                    <div class="dash-stat-subtext">
                        <?= $users_stats['total_experts'] ?> Experts • <?= $users_stats['total_regular_users'] ?> Users
                    </div>
                </div>
            </a>

            <a href="admin_revenue_detail.php">
                <div class="dash-stat-card dash-blue">
                    <div class="dash-stat-icon dash-blue">💵</div>
                    <div class="dash-stat-label">Total Revenue</div>
                    <div class="dash-stat-value">Rp <?= number_format($revenue_stats['total_revenue'] ?? 0) ?></div>
                    <div class="dash-stat-subtext"><?= $revenue_stats['total_topups'] ?> successful top-ups</div>
                </div>
            </a>

            <a href="admin_detail_credits.php">
                <div class="dash-stat-card dash-green">
                    <div class="dash-stat-icon dash-green">💰</div>
                    <div class="dash-stat-label">System Credits</div>
                    <div class="dash-stat-value"><?= number_format($credits_stats['total_credits'] ?? 0) ?></div>
                    <div class="dash-stat-subtext">Circulating in system</div>
                </div>
            </a>

            <a href="admin_sessions_detail.php">
                <div class="dash-stat-card dash-orange">
                    <div class="dash-stat-icon dash-orange">🔄</div>
                    <div class="dash-stat-label">Swap Sessions</div>
                    <div class="dash-stat-value"><?= number_format($sessions_stats['total_sessions']) ?></div>
                    <div class="dash-stat-subtext"><?= $sessions_stats['completed_sessions'] ?> Completed</div>
                </div>
            </a>

        </div>

        <div class="content-grid">
            <div class="left-col">
                <div class="section-card">
                    <div class="section-header">
                        <h2>📊 Recent Transactions</h2>
                    </div>
                    
                    <div class="activity-list">
                        <?php if (mysqli_num_rows($recent_topups) > 0): ?>
                            <?php while ($topup = mysqli_fetch_assoc($recent_topups)): ?>
                            <div class="activity-item">
                                <div class="dash-activity-img-container">
                                    <?php 
                                        $profilePic = !empty($topup['profile_picture']) ? "uploads/" . $topup['profile_picture'] : "assets/default-avatar.png"; 
                                    ?>
                                    <img src="<?= $profilePic ?>" alt="Profile" class="dash-activity-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($topup['name']) ?>&background=7C3AED&color=fff'">
                                </div>
                                <div class="activity-info">
                                    <div class="activity-title"><?= htmlspecialchars($topup['name']) ?></div>
                                    <div class="activity-subtitle">Top-up: <?= htmlspecialchars($topup['package_name']) ?> • <?= date('d M Y, H:i', strtotime($topup['created_at'])) ?></div>
                                </div>
                                <div class="activity-amount">Rp <?= number_format($topup['price_money']) ?></div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">No recent transactions</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <h2>📈 Monthly Revenue</h2>
                    </div>
                    <?php 
                    $max_rev = 0;
                    $data = [];
                    mysqli_data_seek($monthly_revenue, 0);
                    while($m = mysqli_fetch_assoc($monthly_revenue)) {
                        $data[] = $m;
                        if($m['revenue'] > $max_rev) $max_rev = $m['revenue'];
                    }
                    
                    if (count($data) > 0):
                        $data = array_reverse($data);
                    ?>
                    <div class="line-chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        const ctx = document.getElementById('revenueChart').getContext('2d');
                        const revenueChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: [
                                    <?php foreach($data as $row): ?>
                                        '<?= date('M Y', strtotime($row['month'].'-01')) ?>',
                                    <?php endforeach; ?>
                                ],
                                datasets: [{
                                    label: 'Revenue',
                                    data: [
                                        <?php foreach($data as $row): ?>
                                            <?= $row['revenue'] ?>,
                                        <?php endforeach; ?>
                                    ],
                                    borderColor: '#7C3AED',
                                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 6,
                                    pointBackgroundColor: '#7C3AED',
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointHoverRadius: 8,
                                    pointHoverBackgroundColor: '#6D28D9',
                                    pointHoverBorderWidth: 3
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                aspectRatio: 2,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        padding: 12,
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: '#7C3AED',
                                        borderWidth: 2,
                                        displayColors: false,
                                        callbacks: {
                                            label: function(context) {
                                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: '#F3F4F6',
                                            drawBorder: false
                                        },
                                        ticks: {
                                            color: '#6B7280',
                                            font: {
                                                size: 12,
                                                weight: 500
                                            },
                                            callback: function(value) {
                                                return 'Rp ' + (value >= 1000000 ? (value/1000000).toFixed(1) + 'M' : (value >= 1000 ? (value/1000).toFixed(0) + 'K' : value));
                                            }
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            color: '#6B7280',
                                            font: {
                                                size: 12,
                                                weight: 600
                                            }
                                        }
                                    }
                                },
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                }
                            }
                        });
                    </script>
                    <?php else: ?>
                        <div class="empty-state">No revenue data available</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right-col">
                <div class="section-card">
                    <div class="section-header">
                        <h2>⏳ Pending Verifications</h2>
                    </div>
                    <?php if(mysqli_num_rows($pending_verifications) > 0): ?>
                        <?php while($pv = mysqli_fetch_assoc($pending_verifications)): ?>
                        <div class="pending-item">
                            <div class="name"><?= htmlspecialchars($pv['name']) ?></div>
                            <div class="info"><?= htmlspecialchars($pv['email']) ?></div>
                            <div class="info">Submitted: <?= date('d M Y', strtotime($pv['submitted_at'])) ?></div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">No pending verifications</div>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <h2>🏆 Top Users by Balance</h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Credits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($tu = mysqli_fetch_assoc($top_users)): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600"><?= htmlspecialchars($tu['name']) ?></div>
                                    <div style="font-size: 12px; color: #6B7280;"><?= htmlspecialchars($tu['email']) ?></div>
                                </td>
                                <td>
                                    <span class="dash-badge <?= $tu['role'] ?>"><?= ucfirst($tu['role']) ?></span>
                                </td>
                                <td style="font-weight:700; color:#7C3AED">
                                    <?= number_format($tu['balance']) ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>