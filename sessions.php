<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 

session_start();

if (!file_exists("includes/header.php") || !file_exists("includes/db_connect.php")) {
    die("Error: File includes tidak ditemukan.");
}

include "includes/header.php";
include "includes/db_connect.php";

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$req_query = "SELECT a.agreement_id, a.created_at, a.status, a.user_a_id, a.user_b_id, 
              a.skill_requested_name, a.text as message, 
              sk.skill_name as offered_skill_name, sc.category_name, 
              u.name AS partner_name, u.profile_picture AS partner_pic 
              FROM tr_swapagreement a 
              JOIN ms_userskills sk ON a.skill_offered_id = sk.skill_id 
              JOIN ms_skillcategory sc ON sk.category_id = sc.category_id 
              JOIN ms_users u ON u.user_id = CASE WHEN a.user_a_id = ? THEN a.user_b_id ELSE a.user_a_id END 
              WHERE (a.user_a_id = ? OR a.user_b_id = ?) 
              AND a.status = 'pending'
              ORDER BY a.created_at DESC";

$stmt_req = $conn->prepare($req_query);
$stmt_req->bind_param("iii", $user_id, $user_id, $user_id);
$stmt_req->execute();
$requests = $stmt_req->get_result();

$agg_query = "SELECT a.agreement_id, a.status, a.created_at, a.user_a_id, a.user_b_id, 
              a.skill_requested_name, sk.skill_name as offered_skill_name,
              u.name AS partner_name, u.profile_picture AS partner_pic, u.user_id as partner_id,
              (SELECT COUNT(*) FROM tr_userreviews r WHERE r.agreement_id = a.agreement_id AND r.reviewer_id = ?) as has_reviewed
              FROM tr_swapagreement a 
              JOIN ms_userskills sk ON a.skill_offered_id = sk.skill_id 
              JOIN ms_users u ON u.user_id = CASE WHEN a.user_a_id = ? THEN a.user_b_id ELSE a.user_a_id END 
              WHERE (a.user_a_id = ? OR a.user_b_id = ?) 
              AND a.status IN ('scheduled', 'half_completed', 'completed')
              ORDER BY 
                CASE WHEN a.status = 'completed' THEN 2 ELSE 1 END ASC, 
                a.created_at DESC";

$stmt_agg = $conn->prepare($agg_query);
$stmt_agg->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt_agg->execute();
$agreements_result = $stmt_agg->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Swap - Sessions</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'SF Pro', system-ui, sans-serif; }
        body { background: #F5F1ED; color: #1F2937; }
        .container { padding: 40px 60px; max-width: 1200px; margin: auto; }

        .page-header h1 { font-size: 40px; margin-bottom: 6px; font-weight: 800; color: #1F2937; }
        .page-header p { color: #9CA3AF; font-size: 16px; }
        .underline { width: 120px; height: 3px; background: #FF6B35; margin-top: 10px; margin-bottom: 30px; }

        .search-box { margin: 28px 0; }
        .search-box input { width: 100%; padding: 14px 18px; border-radius: 30px; border: 1px solid #E5E7EB; font-size: 14px; outline: none; }
        .search-box input:focus { border-color: #FF6B35; }

        .filter-tabs { display: flex; gap: 12px; margin-bottom: 32px; }
        .filter-tabs button { padding: 10px 18px; border-radius: 20px; border: none; background: #E5E7EB; cursor: pointer; font-weight: 600; color: #4B5563; transition: all 0.2s; }
        .filter-tabs button.active { background: #FF6B35; color: white; box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3); }
        .req-count-badge { background: #EF4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: 5px; vertical-align: middle; }

        .request-card {
            background: white; border-radius: 16px; padding: 24px;
            border: 1px solid #E5E7EB; margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: transform 0.2s ease;
        }
        .request-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); }

        .req-left { display: flex; gap: 16px; align-items: center; }
        .req-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .req-info h3 { margin: 0 0 6px 0; font-size: 18px; font-weight: 700; color: #1F2937; }
        .req-skills { font-size: 14px; color: #6B7280; line-height: 1.5; }
        .skill-tag { font-weight: 600; }
        .skill-tag.offer { color: #FF6B35; }
        .skill-tag.want { color: #4F46E5; }

        .req-right { display: flex; gap: 10px; align-items: center; }
        
        .btn-req { padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
        
        .btn-accept { background: #ECFDF5; color: #047857; }
        .btn-accept:hover { background: #D1FAE5; }
        
        .btn-reject { background: #FEF2F2; color: #B91C1C; }
        .btn-reject:hover { background: #FEE2E2; }

        .status-pending { background: #FFF7ED; color: #C2410C; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }

        .agreement-card { background: white; border-radius: 14px; overflow: hidden; border: 1px solid #E5E7EB; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: transform 0.2s ease; }
        .agreement-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); }

        .agreement-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; background: #FAFAFA; border-bottom: 1px solid #F3F4F6; }
        .partner-info { display: flex; align-items: center; gap: 15px; }
        .partner-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .partner-details h3 { margin: 0; font-size: 18px; font-weight: 700; color: #1F2937; }
        .partner-details span { font-size: 13px; color: #6B7280; }
        .status-badge { padding: 6px 14px; border-radius: 30px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px; }
        .st-scheduled { background: #EFF6FF; color: #1D4ED8; }
        .st-half { background: #FFF7ED; color: #C2410C; }
        .st-completed { background: #ECFDF5; color: #047857; }

        .session-split { display: flex; }
        .session-column { flex: 1; padding: 24px; display: flex; flex-direction: column; }
        .session-column.teach { border-right: 1px solid #F3F4F6; }
        
        .col-header { color: #9CA3AF; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; margin-bottom: 15px; }
        .skill-name { font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 10px; }
        .session-meta { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #4B5563; margin-bottom: 20px; }

        .action-wrapper { margin-top: auto; }
        .btn-action { width: 100%; padding: 12px; border-radius: 10px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-schedule { background: white; color: #FF6B35; border: 1px solid #FF6B35; }
        .btn-schedule:hover { background: #FFF7ED; }
        .btn-join-teach { background: #4F46E5; color: white; }
        .btn-join-teach:hover { background: #4338CA; transform: translateY(-2px); }
        .btn-join-learn { background: #059669; color: white; }
        .btn-join-learn:hover { background: #047857; transform: translateY(-2px); }

        .status-text { font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 6px; }
        .status-finished { color: #059669; }
        .status-waiting { color: #9CA3AF; font-style: italic; }

        .rating-section { padding: 20px 24px; background: #FAFAFA; border-top: 1px solid #F3F4F6; text-align: center; }
        .btn-rating-modern { background: #FF6B35; color: white; padding: 12px 24px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; transition:all 0.2s; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-rating-modern:hover { background: #e05e2e; }
        .reviewed-state { color: #059669; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; }

        .empty-state { text-align: center; padding: 60px 20px; color: #9CA3AF; }

        @media (max-width: 768px) {
            .container { padding: 30px; }
            .session-split { flex-direction: column; }
            .session-column.teach { border-right: none; border-bottom: 1px solid #F3F4F6; }
            .request-card { flex-direction: column; text-align: center; gap: 20px; }
            .req-left { flex-direction: column; }
        }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); animation: slideUp 0.3s ease-out; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="page-header">
            <h1>Sessions & Requests</h1>
            <p>Manage your upcoming classes and swap proposals</p>
            <div class="underline"></div>
        </div>
        
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search for session or partner...">
        </div>

        <div class="filter-tabs">
            <button class="active" data-filter="scheduled">Active Swaps</button>
            <button data-filter="completed">Completed</button>
            <button data-filter="request">Requests <?php if($requests->num_rows>0) echo "<span class='req-count-badge'>".$requests->num_rows."</span>"; ?></button>
        </div>

        <div id="sessions-list">
            
            <?php if ($requests->num_rows > 0): ?>
                <?php while($req = $requests->fetch_assoc()): ?>
                    <div class="request-card session-item" data-status="request" style="display:none;">
                        <div class="req-left">
                            <img src="uploads/<?= !empty($req['partner_pic']) ? htmlspecialchars($req['partner_pic']) : 'default.png' ?>" class="req-avatar" onerror="this.src='uploads/default.png'">
                            <div class="req-info">
                                <h3><?= htmlspecialchars($req['partner_name']) ?></h3>
                                <div class="req-skills">
                                    <span class="skill-tag offer">Offers:</span> <?= htmlspecialchars($req['offered_skill_name']) ?> <span style="color:#E5E7EB; margin:0 5px;">|</span>
                                    <span class="skill-tag want">Wants:</span> <?= htmlspecialchars($req['skill_requested_name']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="req-right">
                            <?php if($req['user_b_id'] == $user_id): ?>
                                <button class="btn-req btn-accept" onclick="handleRequest('accept', <?= $req['agreement_id'] ?>)">✓ Accept</button>
                                <button class="btn-req btn-reject" onclick="handleRequest('reject', <?= $req['agreement_id'] ?>)">✕ Reject</button>
                            <?php else: ?>
                                <div class="status-pending">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Waiting Response
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php if ($agreements_result->num_rows > 0): ?>
                <?php while($agg = $agreements_result->fetch_assoc()): ?>
                    <?php
                        $sql_sess = "SELECT * FROM tr_swapsession WHERE agreement_id = " . $agg['agreement_id'];
                        $res_sess = $conn->query($sql_sess);
                        $teach_session = null; $learn_session = null;
                        while($s = $res_sess->fetch_assoc()) {
                            if ($s['teacher_id'] == $user_id) $teach_session = $s; else $learn_session = $s;
                        }

                        $is_user_a = ($agg['user_a_id'] == $user_id);
                        $my_teach_skill = $is_user_a ? $agg['offered_skill_name'] : $agg['skill_requested_name'];
                        $my_learn_skill = $is_user_a ? $agg['skill_requested_name'] : $agg['offered_skill_name'];

                        $card_filter = ($agg['status'] == 'completed') ? 'completed' : 'scheduled';
                        
                        $badge_text = ''; $badge_icon = ''; $badge_class = '';
                        if($agg['status']=='scheduled') { $badge_text = 'SCHEDULED'; $badge_class='st-scheduled';}
                        else if($agg['status']=='half_completed') { $badge_text = 'IN PROGRESS'; $badge_class='st-half';}
                        else { $badge_text = 'COMPLETED'; $badge_class='st-completed';}
                    ?>

                    <div class="agreement-card session-item" data-status="<?= $card_filter ?>">
                        <div class="agreement-header">
                            <div class="partner-info">
                                <img src="uploads/<?= !empty($agg['partner_pic']) ? htmlspecialchars($agg['partner_pic']) : 'default.png' ?>" class="partner-avatar" onerror="this.src='uploads/default.png'">
                                <div class="partner-details">
                                    <h3><?= htmlspecialchars($agg['partner_name']) ?></h3>
                                    <span>Agreement #<?= $agg['agreement_id'] ?> • <?= date('d M Y', strtotime($agg['created_at'])) ?></span>
                                </div>
                            </div>
                            <span class="status-badge <?= $badge_class ?>"><?= $badge_icon ?> <?= $badge_text ?></span>
                        </div>

                        <div class="session-split">
                            <div class="session-column teach">
                                <div class="col-header"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg> You Teach</div>
                                <div class="skill-name"><?= htmlspecialchars($my_teach_skill) ?></div>
                                <?php if ($teach_session): 
                                    $ts_date = !empty($teach_session['scheduled_datetime']) ? date('D, d M • H:i', strtotime($teach_session['scheduled_datetime'])) : 'Date not set';
                                    $teach_json = htmlspecialchars(json_encode(['id'=>$teach_session['session_id'],'ts'=>strtotime($teach_session['scheduled_datetime']??''),'role'=>'teach','skill'=>$my_teach_skill,'link'=>$teach_session['meeting_link'],'status'=>$teach_session['status']]),ENT_QUOTES,'UTF-8');
                                ?>
                                    <div class="session-meta"><svg width="16" height="16" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> <?= $ts_date ?></div>
                                    <div class="action-wrapper">
                                        <?php if ($teach_session['status'] == 'pending_schedule'): ?>
                                            <button class="btn-action btn-schedule" onclick="openScheduleModal(<?= $teach_session['session_id'] ?>)">Set Schedule</button>
                                        <?php elseif ($teach_session['status'] == 'completed'): ?>
                                            <span class="status-text status-finished"><svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> Class Finished</span>
                                        <?php else: ?>
                                            <button class="btn-action btn-join-teach" onclick="openJoinModal(<?= $teach_json ?>)">Open Class Room</button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="session-column learn">
                                <div class="col-header"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg> You Learn</div>
                                <div class="skill-name"><?= htmlspecialchars($my_learn_skill) ?></div>
                                <?php if ($learn_session): 
                                    $ls_date = !empty($learn_session['scheduled_datetime']) ? date('D, d M • H:i', strtotime($learn_session['scheduled_datetime'])) : 'Waiting for partner...';
                                    $learn_json = htmlspecialchars(json_encode(['id'=>$learn_session['session_id'],'ts'=>strtotime($learn_session['scheduled_datetime']??''),'role'=>'learn','skill'=>$my_learn_skill,'link'=>$learn_session['meeting_link'],'status'=>$learn_session['status'],'started_at'=>$learn_session['started_at']]),ENT_QUOTES,'UTF-8');
                                ?>
                                    <div class="session-meta"><svg width="16" height="16" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> <?= $ls_date ?></div>
                                    <div class="action-wrapper">
                                        <?php if ($learn_session['status'] == 'pending_schedule'): ?>
                                            <span class="status-text status-waiting"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Scheduling...</span>
                                        <?php elseif ($learn_session['status'] == 'completed'): ?>
                                            <span class="status-text status-finished"><svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> Class Finished</span>
                                        <?php else: ?>
                                            <button class="btn-action btn-join-learn" onclick="openJoinModal(<?= $learn_json ?>)">Join Class</button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($agg['status'] == 'completed'): ?>
                            <div class="rating-section">
                            <?php if ($agg['has_reviewed'] == 0): ?>
                                <button class="btn-rating-modern" onclick="showRatingPopup(<?= $agg['partner_id'] ?>, <?= $agg['agreement_id'] ?>, '<?= htmlspecialchars($agg['partner_name']) ?>')">⭐ Give Review to <?= htmlspecialchars($agg['partner_name']) ?></button>
                            <?php else: ?>
                                <div class="reviewed-state">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> You have reviewed this swap
                                </div>
                            <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <div id="empty-state" class="empty-state"><p>No items found.</p></div>
        </div>
    </div>

    <div id="scheduleModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="margin:0 0 20px 0; font-size:22px; font-weight:800;">📅 Schedule Session</h3>
            <form action="process_schedule.php" method="POST">
                <input type="hidden" name="session_id" id="modalSessionId">
                <input type="hidden" name="action" value="propose">
                <div style="background:#F3F4F6; padding:15px; border-radius:10px; margin-bottom:20px; font-size:14px; display:flex; gap:10px;">
                    <svg width="20" height="20" fill="none" stroke="#6B7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Session duration is fixed at <strong>60 minutes</strong>.</span>
                </div>
                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:14px;">Select Start Date & Time:</label>
                <input type="datetime-local" name="scheduled_datetime" required style="width:100%; padding:12px; border:1px solid #D1D5DB; border-radius:10px; font-size:16px; margin-bottom:25px; font-family:inherit; box-sizing:border-box;">
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="closeModal('scheduleModal')" style="flex:1; padding:12px; border:none; background:#F3F4F6; color:#4B5563; border-radius:10px; font-weight:600; cursor:pointer;">Cancel</button>
                    <button type="submit" style="flex:1; padding:12px; border:none; background:#FF6B35; color:white; border-radius:10px; font-weight:600; cursor:pointer;">Confirm Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <div id="joinModal" class="modal-overlay">
        <div class="modal-content" style="max-width:500px;">
            <div style="text-align:center; margin-bottom:25px;">
                <h3 style="margin:0; font-size:24px; font-weight:800;">🔴 Live Session</h3>
                <p id="joinSubject" style="font-size:16px; font-weight:600; color:#FF6B35; margin:10px 0;"></p>
                <p id="joinTimeRange" style="color:#6B7280; font-size:14px; display:flex; align-items:center; justify-content:center; gap:5px;"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> <span></span></p>
            </div>
            
            <div style="background:#FAFAFA; border-radius:16px; padding:25px; border:1px solid #F3F4F6;">
                <div id="teacherSection" style="display:none;">
                    <label style="display:block; margin-bottom:10px; font-weight:600; font-size:14px;">🔗 Paste your meeting link (Zoom/GMeet):</label>
                    <div style="display:flex; gap:10px;">
                        <input type="url" id="meetLinkInput" placeholder="https://..." style="flex:1; padding:12px; border:1px solid #D1D5DB; border-radius:10px;">
                        <button onclick="saveMeetingLink()" style="padding:12px 20px; background:#4F46E5; color:white; border:none; border-radius:10px; font-weight:600; cursor:pointer;">Save</button>
                    </div>
                </div>

                <div id="learnerSection" style="display:none; text-align:center;">
                    <div style="margin-bottom:25px;">
                        <span style="font-size:13px; font-weight:600; color:#6B7280; display:block; margin-bottom:5px;">CLASS LINK</span>
                        <a id="learnerLinkText" href="#" target="_blank" style="display:inline-block; padding:10px 20px; background:#EFF6FF; color:#2563EB; border-radius:30px; text-decoration:none; font-weight:600; border:1px solid #DBEAFE;">Wait for teacher...</a>
                    </div>
                    <div id="liveSessionArea" style="display:none;">
                        <div style="width:120px; height:120px; border-radius:50%; border:4px solid #FF6B35; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:28px; font-weight:800; font-family:monospace;">
                            <span id="sessionTimer">60:00</span>
                        </div>
                        <p style="color:#6B7280; font-size:14px; margin-bottom:20px;">Session is in progress. Keep this tab open.</p>
                        <button id="btnEndSession" onclick="endClass()" style="width:100%; padding:14px; background:#DC2626; color:white; border:none; border-radius:12px; font-weight:700; font-size:16px; cursor:pointer; box-shadow:0 4px 12px rgba(220, 38, 38, 0.3);">End Session & Finish</button>
                    </div>
                    <div id="startArea">
                        <p id="timeWarning" style="color:#6B7280; font-size:14px; margin-bottom:20px; background:#F3F4F6; padding:10px; border-radius:10px;">You can start 15 mins before schedule.</p>
                        <button id="btnStartClass" onclick="startClass()" disabled style="width:100%; padding:14px; background:#D1D5DB; color:white; border:none; border-radius:12px; font-weight:700; font-size:16px; cursor:not-allowed;">Start Class</button>
                    </div>
                </div>
            </div>
            <button onclick="closeModal('joinModal')" style="margin-top:20px; width:100%; padding:12px; background:none; border:none; color:#6B7280; font-weight:600; cursor:pointer;">Close Window</button>
        </div>
    </div>

<script>
    let currentJoinId = 0;
    let currentJoinLink = '';
    let timerInterval = null;
    let sessionOverrides = {}; 

    const tabs = document.querySelectorAll('.filter-tabs button');
    const emptyStateDiv = document.getElementById('empty-state');
    const searchInput = document.getElementById('searchInput');

    document.querySelector('.filter-tabs button[data-filter="scheduled"]').classList.add('active');
    filterSessions('scheduled');

    tabs.forEach(btn => {
        btn.addEventListener('click', function() {
            tabs.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterSessions(this.dataset.filter);
        });
    });

    searchInput.addEventListener('input', function() {
        const activeTab = document.querySelector('.filter-tabs button.active');
        filterSessions(activeTab ? activeTab.dataset.filter : 'scheduled');
    });

    function filterSessions(filter) {
        let visibleCount = 0;
        const searchTerm = searchInput.value.toLowerCase();

        document.querySelectorAll('.session-item').forEach(item => {
            const status = item.dataset.status;
            const text = item.innerText.toLowerCase();
            let shouldShow = false;

            if(filter === 'request') {
                shouldShow = (status === 'request');
            } else {
                if (status === 'request') shouldShow = false;
                else if (filter === 'scheduled' && status !== 'completed') shouldShow = true;
                else if (filter === 'completed' && status === 'completed') shouldShow = true;
            }

            if(shouldShow && searchTerm && !text.includes(searchTerm)) shouldShow = false;

            if (shouldShow) {
                item.style.display = item.classList.contains('request-card') ? 'flex' : 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        emptyStateDiv.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    function closeModal(id) { 
        document.getElementById(id).classList.remove('active'); 
        setTimeout(()=>{document.getElementById(id).style.display='none'},300); 
        if(timerInterval) clearInterval(timerInterval); 
    }
    
    function openScheduleModal(sessId) { 
        document.getElementById('modalSessionId').value = sessId; 
        const m=document.getElementById('scheduleModal'); 
        m.style.display='flex'; 
        setTimeout(()=>m.classList.add('active'),10); 
    }
    
    function openJoinModal(data) {
        currentJoinId = data.id;

        if (sessionOverrides[data.id]) {
            data.status = sessionOverrides[data.id].status;
            data.started_at = sessionOverrides[data.id].started_at;
        }

        const m = document.getElementById('joinModal');
        
        document.getElementById('joinSubject').textContent = data.skill;
        const startDate = new Date(data.ts * 1000);
        document.getElementById('joinTimeRange').querySelector('span').textContent = startDate.toLocaleString([], {dateStyle:'medium', timeStyle:'short'});

        const tSec = document.getElementById('teacherSection');
        const lSec = document.getElementById('learnerSection');

        if (data.role === 'teach') {
            tSec.style.display='block'; 
            lSec.style.display='none';
            document.getElementById('meetLinkInput').value = data.link || '';
        } else {
            tSec.style.display='none'; 
            lSec.style.display='block';
            currentJoinLink = data.link;
            
            const linkTxt = document.getElementById('learnerLinkText');
            if(data.link){ 
                linkTxt.textContent = "Join Meeting Link ↗"; 
                linkTxt.href = data.link; 
                linkTxt.style.background="#EFF6FF"; 
                linkTxt.style.color="#2563EB"; 
                linkTxt.style.pointerEvents = "auto";
            } else { 
                linkTxt.textContent = "Wait for teacher to add link..."; 
                linkTxt.removeAttribute('href'); 
                linkTxt.style.background="#F3F4F6"; 
                linkTxt.style.color="#6B7280"; 
                linkTxt.style.pointerEvents = "none";
            }
            
            if(data.status === 'ongoing' && data.started_at) { 
                showTimerMode(data.started_at); 
            } else { 
                showStartButtonMode(data.ts, data.link); 
            }
        }
        m.style.display='flex'; 
        setTimeout(()=>m.classList.add('active'),10);
    }

    function saveMeetingLink() { 
        const link=document.getElementById('meetLinkInput').value; 
        const fd=new FormData(); 
        fd.append('session_id', currentJoinId); 
        fd.append('meeting_link', link); 
        
        fetch('save_link.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{ 
            if(d.status==='success') Swal.fire('Saved!', 'Link updated.', 'success').then(()=>location.reload()); 
            else Swal.fire('Error', 'Failed to save link', 'error');
        }); 
    }

    function startClass() { 
        const btn = document.getElementById('btnStartClass');
        btn.disabled = true;
        btn.innerText = "Starting..."; 
        
        const fd=new FormData(); 
        fd.append('session_id', currentJoinId); 
        
        fetch('start_session.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{ 
            if(d.status==='success'){ 
                if(currentJoinLink) window.open(currentJoinLink,'_blank'); 
                
                const now = new Date().toISOString(); 
                
                sessionOverrides[currentJoinId] = {
                    status: 'ongoing',
                    started_at: now
                };

                showTimerMode(now); 
            } else {
                Swal.fire('Error', 'Failed to start session', 'error');
                btn.disabled = false;
                btn.innerText = "Start Class";
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerText = "Start Class";
        }); 
    }

    function endClass() { 
        const btn = document.getElementById('btnEndSession'); 
        btn.innerText="Finishing..."; 
        btn.disabled = true;

        const fd=new FormData(); 
        fd.append('session_id', currentJoinId); 
        
        fetch('end_session.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{ 
            if(d.status==='success'){ 
                if(d.type==='half') Swal.fire('Session Finished!', 'Wait for partner to finish.', 'info').then(()=>location.reload()); 
                else Swal.fire('Agreement Completed!', `You earned ${d.earned} credits!`, 'success').then(()=>location.reload()); 
            } else {
                Swal.fire('Error', d.message, 'error');
                btn.disabled = false;
                btn.innerText = "End Session & Finish";
            }
        }); 
    }

    function showTimerMode(startedAt) {
        document.getElementById('startArea').style.display = 'none';
        document.getElementById('liveSessionArea').style.display = 'block';

        const btnEnd = document.getElementById('btnEndSession');
        const timerDisplay = document.getElementById('sessionTimer');

        const endT = new Date(startedAt).getTime() + 60 * 60 * 1000;

        if (timerInterval) clearInterval(timerInterval);
        
        updateTimer(); 

        timerInterval = setInterval(updateTimer, 1000);

        function updateTimer() {
            const now = new Date().getTime();
            const dist = endT - now;

            if (dist < 0) {
                clearInterval(timerInterval);
                timerDisplay.innerText = "00:00";
                timerDisplay.style.color = "#DC2626"; 

                btnEnd.disabled = false;
                btnEnd.style.background = "#DC2626";
                btnEnd.style.cursor = "pointer";
                btnEnd.innerText = "End Session & Finish";
                
            } else {
                btnEnd.disabled = true;
                btnEnd.style.background = "#D1D5DB";
                btnEnd.style.cursor = "not-allowed";
                btnEnd.innerText = "Session in Progress...";

                const m = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((dist % (1000 * 60)) / 1000);
                
                timerDisplay.innerText = (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);
                timerDisplay.style.color = "#1F2937";
            }
        }
    }

    function showStartButtonMode(ts, link){ 
        document.getElementById('liveSessionArea').style.display='none'; 
        document.getElementById('startArea').style.display='block'; 
        
        const btn=document.getElementById('btnStartClass'); 
        const nowSec = Math.floor(Date.now()/1000);
        const diffMins = Math.floor((ts - nowSec)/60); 
        
        if(link && diffMins <= 15){ 
            btn.disabled=false; 
            btn.style.background="#28a745"; 
            btn.style.cursor="pointer"; 
            btn.innerText = "Start Class";
            document.getElementById('timeWarning').style.display = 'none';
        } else { 
            btn.disabled=true; 
            btn.style.background="#D1D5DB"; 
            btn.style.cursor="not-allowed"; 
            document.getElementById('timeWarning').style.display = 'block';
            if(!link) document.getElementById('timeWarning').innerText = "Waiting for teacher link...";
            else document.getElementById('timeWarning').innerText = `Class starts in ${diffMins} mins`;
        } 
    }

    function handleRequest(act, id){ 
        Swal.fire({
            title: act.charAt(0).toUpperCase() + act.slice(1) + ' Request?',
            text: `Are you sure you want to ${act} this request?`,
            icon: 'question',
            showCancelButton:true,
            confirmButtonColor: act === 'accept' ? '#28a745' : '#dc3545',
            confirmButtonText: 'Yes, do it!'
        }).then(r=>{
            if(r.isConfirmed) {
                Swal.fire({ title: 'Processing...', didOpen: () => Swal.showLoading() });
                fetch(`process_agreement.php?action=${act}&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire('Success!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Something went wrong', 'error');
                });
            }
        }); 
    }

    function showRatingPopup(partnerId, agreementId, partnerName) {
        Swal.fire({
            title: 'Rate Swap with ' + partnerName,
            html: `
                <p style="font-size:14px;color:#666;margin-bottom:15px;">How was your experience?</p>
                <div id="star-container" style="display:flex;justify-content:center;gap:5px;margin-bottom:15px;font-size:24px;">
                    <span class="star" data-value="1" style="cursor:pointer;opacity:0.3;transition:0.2s">⭐</span>
                    <span class="star" data-value="2" style="cursor:pointer;opacity:0.3;transition:0.2s">⭐</span>
                    <span class="star" data-value="3" style="cursor:pointer;opacity:0.3;transition:0.2s">⭐</span>
                    <span class="star" data-value="4" style="cursor:pointer;opacity:0.3;transition:0.2s">⭐</span>
                    <span class="star" data-value="5" style="cursor:pointer;opacity:0.3;transition:0.2s">⭐</span>
                </div>
                <input type="hidden" id="swal-rating-input" value="0">
                <textarea id="swal-review-input" class="swal2-textarea" placeholder="Write a review..." style="margin:0;"></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'Submit Review',
            confirmButtonColor: '#FF6B35',
            didOpen: () => {
                const container = Swal.getHtmlContainer();
                const stars = container.querySelectorAll('.star');
                const ratingInput = container.querySelector('#swal-rating-input');

                stars.forEach((star, index) => {
                    star.addEventListener('mouseenter', () => stars.forEach((s, i) => s.style.opacity = i <= index ? '1' : '0.3'));
                    star.addEventListener('mouseleave', () => {
                        const currentVal = parseInt(ratingInput.value);
                        stars.forEach((s, i) => s.style.opacity = i < currentVal ? '1' : '0.3');
                    });
                    star.addEventListener('click', () => {
                        const val = parseInt(star.getAttribute('data-value'));
                        ratingInput.value = val;
                        stars.forEach((s, i) => s.style.opacity = i < val ? '1' : '0.3');
                    });
                });
            },
            preConfirm: () => {
                const container = Swal.getHtmlContainer();
                const ratingVal = container.querySelector('#swal-rating-input').value;
                const reviewVal = container.querySelector('#swal-review-input').value;
                if (ratingVal == "0") { Swal.showValidationMessage('Please select a star rating'); return false; }
                if (!reviewVal) { Swal.showValidationMessage('Please write a review'); return false; }
                return { rating: ratingVal, review: reviewVal };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Submitting...', didOpen: () => Swal.showLoading() });
                const fd = new FormData();
                fd.append('reviewee_id', partnerId);
                fd.append('agreement_id', agreementId);
                fd.append('rating', result.value.rating);
                fd.append('review', result.value.review);

                fetch('process_review.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') Swal.fire('Thank You!', 'Review submitted successfully.', 'success').then(() => location.reload());
                    else Swal.fire('Error', d.message, 'error');
                });
            }
        });
    }
</script>
</body>
</html>