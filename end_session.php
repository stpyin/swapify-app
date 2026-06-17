<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = intval($_POST['session_id']);

$query = "SELECT s.session_id, s.agreement_id, s.learner_id, a.user_a_id, a.user_b_id 
          FROM tr_swapsession s
          JOIN tr_swapagreement a ON s.agreement_id = a.agreement_id
          WHERE s.session_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session_data = $stmt->get_result()->fetch_assoc();

if (!$session_data) {
    echo json_encode(['status' => 'error', 'message' => 'Session not found']);
    exit();
}

if ($session_data['learner_id'] != $user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Only the learner can end this session']);
    exit();
}

$agreement_id = $session_data['agreement_id'];

$conn->begin_transaction();

try {
    $upd_sess = $conn->prepare("UPDATE tr_swapsession SET status = 'completed', completed_at = NOW() WHERE session_id = ?");    
    $upd_sess->bind_param("i", $session_id);
    $upd_sess->execute();

    $chk_sql = "SELECT COUNT(*) as unfinished FROM tr_swapsession WHERE agreement_id = ? AND status != 'completed'";
    $stmt_chk = $conn->prepare($chk_sql);
    $stmt_chk->bind_param("i", $agreement_id);
    $stmt_chk->execute();
    $result_chk = $stmt_chk->get_result()->fetch_assoc();
    
    $unfinished_count = $result_chk['unfinished'];

    $response_data = [];

    if ($unfinished_count > 0) {
        $conn->query("UPDATE tr_swapagreement SET status = 'half_completed' WHERE agreement_id = $agreement_id");
        
        $response_data = [
            'status' => 'success',
            'type'   => 'half',
            'message' => 'Session finished! Waiting for the other session to complete to earn credits.'
        ];

    } else {
        $conn->query("UPDATE tr_swapagreement SET status = 'completed' WHERE agreement_id = $agreement_id");

        $user_a = $session_data['user_a_id'];
        $user_b = $session_data['user_b_id'];

        $credit_a = calculateAndGiveCredit($conn, $user_a);
        
        $credit_b = calculateAndGiveCredit($conn, $user_b);

        $my_earned = ($user_id == $user_a) ? $credit_a : $credit_b;

        $response_data = [
            'status' => 'success',
            'type'   => 'full',
            'message' => "Agreement Completed! Both users rewarded.",
            'earned'  => $my_earned
        ];
    }

    $conn->commit();
    echo json_encode($response_data);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

function calculateAndGiveCredit($conn, $uid) {
    $base_credit = 5;
    $multiplier = 1.0;

    $boost_sql = "SELECT b.multiplier 
                  FROM tr_userbooster ub
                  JOIN ms_boosters b ON ub.booster_id = b.package_id
                  WHERE ub.user_id = ? 
                  AND ub.purchased_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  ORDER BY ub.purchased_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($boost_sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $multiplier = floatval($row['multiplier']);
    }

    $final_credit = ceil($base_credit * $multiplier);

    $chk = $conn->query("SELECT user_id FROM ms_usercredits WHERE user_id = $uid");
    if ($chk->num_rows == 0) {
        $ins = $conn->prepare("INSERT INTO ms_usercredits (user_id, balance) VALUES (?, ?)");
        $ins->bind_param("ii", $uid, $final_credit);
        $ins->execute();
    } else {
        $upd = $conn->prepare("UPDATE ms_usercredits SET balance = balance + ? WHERE user_id = ?");
        $upd->bind_param("ii", $final_credit, $uid);
        $upd->execute();
    }

    $desc = "Reward for completing agreement";
    if($multiplier > 1.0) {
        $desc .= " (Booster {$multiplier}x Active)";
    }

    $ins_hist = $conn->prepare("INSERT INTO tr_credithistory (user_id, amount, transaction_type, description, created_at) VALUES (?, ?, 'earning', ?, NOW())");
    $ins_hist->bind_param("iis", $uid, $final_credit, $desc);
    $ins_hist->execute();

    return $final_credit;
}
?>