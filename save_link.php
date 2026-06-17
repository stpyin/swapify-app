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
$link = $_POST['meeting_link'];

if (empty($link)) {
    echo json_encode(['status' => 'error', 'message' => 'Link cannot be empty']);
    exit();
}

$query = "UPDATE tr_swapsession SET meeting_link = ? WHERE session_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $link, $session_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>