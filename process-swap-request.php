<?php
session_start();

require_once __DIR__ . '/includes/db_connect.php';

if (!isset($conn)) {
    die("Koneksi Database Gagal. Cek file includes/db_connect.php");
}

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$user_a_id = $_SESSION['user_id'];
$user_b_id = $_POST['target_user_id'];

$skill_offered_id = $_POST['skill_to_teach'];
$skill_requested_name = $_POST['skill_to_learn'];
$raw_message = trim($_POST['message']);

if (empty($user_b_id) || empty($skill_offered_id) || empty($skill_requested_name) || empty($raw_message)) {
    header("Location: dashboard.php?status=error&msg=missing_fields");
    exit();
}

$query_check_skill = "SELECT skill_id FROM ms_userskills WHERE skill_id = ? AND user_id = ?";
$stmt_check = $conn->prepare($query_check_skill);
$stmt_check->bind_param("ii", $skill_offered_id, $user_a_id);
$stmt_check->execute();

if ($stmt_check->get_result()->num_rows === 0) {
    header("Location: dashboard.php?status=error&msg=invalid_skill_ownership");
    exit();
}

$check_duplicate = "SELECT agreement_id FROM tr_swapagreement 
                    WHERE user_a_id = ? AND user_b_id = ? AND status = 'pending'";
$stmt_dup = $conn->prepare($check_duplicate);
$stmt_dup->bind_param("ii", $user_a_id, $user_b_id);
$stmt_dup->execute();

if ($stmt_dup->get_result()->num_rows > 0) {
    header("Location: dashboard.php?status=error&msg=duplicate_request");
    exit();
}

$sql = "INSERT INTO tr_swapagreement 
        (user_a_id, user_b_id, skill_offered_id, skill_requested_name, status, text, created_at) 
        VALUES (?, ?, ?, ?, 'pending', ?, NOW())";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("iiiss", $user_a_id, $user_b_id, $skill_offered_id, $skill_requested_name, $raw_message);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?status=success");
    } else {
        header("Location: dashboard.php?status=error&msg=db_exec_error");
    }
    $stmt->close();
} else {
    header("Location: dashboard.php?status=error&msg=sql_prepare_error");
}

$conn->close();
?>