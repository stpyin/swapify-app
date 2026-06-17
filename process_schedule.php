<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: sessions.php"); exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'];
$session_id = intval($_POST['session_id']);

$sql = "SELECT * FROM tr_swapsession WHERE session_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) { header("Location: sessions.php?err=nosession"); exit(); }

if ($session['teacher_id'] != $user_id && $session['learner_id'] != $user_id) {
    header("Location: sessions.php?err=auth"); exit();
}

if ($action === 'propose') {
    $date = $_POST['scheduled_datetime'];
    
    $upd = "UPDATE tr_swapsession SET scheduled_datetime = ?, status = 'proposed', proposed_by = ? WHERE session_id = ?";
    $stmt_up = $conn->prepare($upd);
    $stmt_up->bind_param("sii", $date, $user_id, $session_id);
    $stmt_up->execute();
    
} elseif ($action === 'accept') {
    $upd = "UPDATE tr_swapsession SET status = 'scheduled' WHERE session_id = ?";
    $stmt_up = $conn->prepare($upd);
    $stmt_up->bind_param("i", $session_id);
    $stmt_up->execute();

} elseif ($action === 'reject') {
    $upd = "UPDATE tr_swapsession SET status = 'pending_schedule', scheduled_datetime = NULL, proposed_by = NULL WHERE session_id = ?";
    $stmt_up = $conn->prepare($upd);
    $stmt_up->bind_param("i", $session_id);
    $stmt_up->execute();
}

header("Location: sessions.php");
?>