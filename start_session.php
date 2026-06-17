<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$session_id = $_POST['session_id'];

$check = $conn->query("SELECT session_id FROM tr_swapsession WHERE session_id = $session_id");
if ($check->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Session ID not found']);
    exit();
}

$query = "UPDATE tr_swapsession SET status = 'ongoing', started_at = NOW() WHERE session_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $session_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}
?>