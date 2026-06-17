<?php
session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$sessions_query = "SELECT ss.*, 
                   sa.user_a_id, sa.user_b_id,
                   ua.name as user_a_name, ub.name as user_b_name,
                   us.skill_name
                   FROM tr_swapsession ss
                   JOIN tr_swapagreement sa ON ss.agreement_id = sa.agreement_id
                   JOIN ms_users ua ON sa.user_a_id = ua.user_id
                   JOIN ms_users ub ON sa.user_b_id = ub.user_id
                   LEFT JOIN ms_userskills us ON ss.skill_id = us.skill_id
                   ORDER BY ss.session_id ASC";
$sessions_result = mysqli_query($conn, $sessions_query);

$total_sessions = mysqli_num_rows($sessions_result);
$pending_schedule = 0;
$proposed = 0;
$scheduled = 0;
$ongoing = 0;
$completed = 0;
$cancelled = 0;

mysqli_data_seek($sessions_result, 0);
while($s = mysqli_fetch_assoc($sessions_result)) {
    if($s['status'] == 'pending_schedule') $pending_schedule++;
    elseif($s['status'] == 'proposed') $proposed++;
    elseif($s['status'] == 'scheduled') $scheduled++;
    elseif($s['status'] == 'ongoing') $ongoing++;
    elseif($s['status'] == 'completed') $completed++;
    elseif($s['status'] == 'cancelled') $cancelled++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swap Sessions - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin-detail.css?v=5">
    <style>
        .summary-item {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .summary-item:not(.total-item):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .summary-item.active {
            background: linear-gradient(90deg, #FF6B35, #FF9A5A) !important;
            box-shadow: 0 4px 12px rgba(255,107,53,0.3);
        }
        
        .summary-item.active .label,
        .summary-item.active .value {
            color: #f7f7f7 !important;
        }
        
        tr.hidden {
            display: none;
        }

        .badge-pending_schedule {
            background: #FEF3C7;
            color: #92400E;
        }

        .badge-proposed {
            background: #E0E7FF;
            color: #3730A3;
        }

        .badge-scheduled {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .badge-ongoing {
            background: #FED7AA;
            color: #9A3412;
        }

        .badge-completed {
            background: #D1FAE5;
            color: #065F46;
        }

        .badge-cancelled {
            background: #FEE2E2;
            color: #991B1B;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
            <h1>Swap Sessions</h1>
            <p>All skill exchange sessions</p>
        </div>

        <div class="stats-summary">
            <div class="summary-item total-item active" data-filter="all" onclick="filterSessions('all')">
                <span class="label">Total Sessions</span>
                <span class="value"><?= $total_sessions ?></span>
            </div>
            <div class="summary-item" data-filter="pending_schedule" onclick="filterSessions('pending_schedule')">
                <span class="label">Pending Schedule</span>
                <span class="value"><?= $pending_schedule ?></span>
            </div>
            <div class="summary-item" data-filter="proposed" onclick="filterSessions('proposed')">
                <span class="label">Proposed</span>
                <span class="value"><?= $proposed ?></span>
            </div>
            <div class="summary-item" data-filter="scheduled" onclick="filterSessions('scheduled')">
                <span class="label">Scheduled</span>
                <span class="value"><?= $scheduled ?></span>
            </div>
            <div class="summary-item" data-filter="ongoing" onclick="filterSessions('ongoing')">
                <span class="label">Ongoing</span>
                <span class="value"><?= $ongoing ?></span>
            </div>
            <div class="summary-item" data-filter="completed" onclick="filterSessions('completed')">
                <span class="label">Completed</span>
                <span class="value"><?= $completed ?></span>
            </div>
            <div class="summary-item" data-filter="cancelled" onclick="filterSessions('cancelled')">
                <span class="label">Cancelled</span>
                <span class="value"><?= $cancelled ?></span>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Participants</th>
                        <th>Skill</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($sessions_result, 0);
                    while($session = mysqli_fetch_assoc($sessions_result)): 
                        $status_display = $session['status'];
                        if ($status_display == 'pending_schedule') {
                            $status_display = 'Pending Schedule';
                        } else {
                            $status_display = ucfirst($status_display);
                        }
                    ?>
                    <tr data-status="<?= $session['status'] ?>">
                        <td><?= $session['session_id'] ?></td>
                        <td>
                            <div class="participants">
                                <div><?= htmlspecialchars($session['user_a_name']) ?><small style="margin: 0px 10px; color: orange;">⇄</small></div>
                                <div><?= htmlspecialchars($session['user_b_name']) ?></div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($session['skill_name'] ?? '-') ?></td>
                        <td><?= $session['scheduled_datetime'] ? date('d M Y, H:i', strtotime($session['scheduled_datetime'])) : '-' ?></td>
                        <td><span class="badge badge-<?= $session['status'] ?>"><?= $status_display ?></span></td>
                        <td><?= date('d M Y', strtotime($session['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterSessions(status) {
            document.querySelectorAll('.summary-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelector(`[data-filter="${status}"]`).classList.add('active');
            
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                
                if (status === 'all') {
                    row.classList.remove('hidden');
                } else {
                    if (rowStatus === status) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                }
            });
        }
    </script>
</body>
</html>