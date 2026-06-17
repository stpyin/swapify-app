<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/includes/db_connect.php';

function sendResponse($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit();
}

if (!isset($_SESSION['user_id']) || !isset($_GET['action']) || !isset($_GET['id'])) {
    sendResponse('error', 'Invalid Request or Unauthorized');
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'];
$agreement_id = intval($_GET['id']);

$sql = "SELECT * FROM tr_swapagreement WHERE agreement_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agreement_id);
$stmt->execute();
$agreement = $stmt->get_result()->fetch_assoc();

if (!$agreement) {
    sendResponse('error', 'Agreement not found.');
}

if ($agreement['user_a_id'] != $user_id && $agreement['user_b_id'] != $user_id) {
    sendResponse('error', 'Unauthorized Access.');
}

$conn->begin_transaction();

try {
    if ($action === 'accept') {
        if ($agreement['user_b_id'] != $user_id) {
            throw new Exception("Only receiver can accept.");
        }

        $upd = $conn->prepare("UPDATE tr_swapagreement SET status = 'scheduled' WHERE agreement_id = ?");
        $upd->bind_param("i", $agreement_id);
        $upd->execute();

        $ins1 = $conn->prepare("INSERT INTO tr_swapsession (agreement_id, teacher_id, learner_id, skill_id, status) VALUES (?, ?, ?, ?, 'pending_schedule')");
        $ins1->bind_param("iiii", $agreement_id, $agreement['user_a_id'], $agreement['user_b_id'], $agreement['skill_offered_id']);
        $ins1->execute();

        $skill_name_needed = $agreement['skill_requested_name'];
        $user_b_id = $agreement['user_b_id'];

        $q_skill = $conn->prepare("SELECT skill_id FROM ms_userskills WHERE user_id = ? AND skill_name = ? LIMIT 1");
        $q_skill->bind_param("is", $user_b_id, $skill_name_needed);
        $q_skill->execute();
        $res_skill = $q_skill->get_result();

        $skill_b_id = 0;
        if ($res_skill->num_rows > 0) {
            $skill_b_data = $res_skill->fetch_assoc();
            $skill_b_id = $skill_b_data['skill_id'];
        }

        $ins2 = $conn->prepare("INSERT INTO tr_swapsession (agreement_id, teacher_id, learner_id, skill_id, status) VALUES (?, ?, ?, ?, 'pending_schedule')");
        $ins2->bind_param("iiii", $agreement_id, $agreement['user_b_id'], $agreement['user_a_id'], $skill_b_id);
        $ins2->execute();

        $conn->commit();
        sendResponse('success', 'Request accepted! Sessions created.');

    } elseif ($action === 'reject') {
        $upd = $conn->prepare("UPDATE tr_swapagreement SET status = 'cancelled' WHERE agreement_id = ?");
        $upd->bind_param("i", $agreement_id);
        $upd->execute();
        
        $conn->commit();
        sendResponse('success', 'Request rejected.');

    } elseif ($action === 'cancel') {
        $upd = $conn->prepare("UPDATE tr_swapagreement SET status = 'cancelled' WHERE agreement_id = ?");
        $upd->bind_param("i", $agreement_id);
        $upd->execute();
        
        $conn->commit();
        sendResponse('success', 'Request cancelled.');
    } else {
        throw new Exception("Unknown action.");
    }

} catch (Exception $e) {
    $conn->rollback();
    sendResponse('error', 'Database Error: ' . $e->getMessage());
}
?>