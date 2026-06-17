<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit();
}

$reviewer_id  = $_SESSION['user_id'];
$reviewee_id  = intval($_POST['reviewee_id']);
$agreement_id = intval($_POST['agreement_id']);
$rating       = intval($_POST['rating']);
$review       = trim($_POST['review']);

if ($rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid rating']);
    exit();
}

if (empty($review)) {
    echo json_encode(['status' => 'error', 'message' => 'Review cannot be empty']);
    exit();
}

$query = "INSERT INTO tr_userreviews (reviewer_id, reviewee_id, agreement_id, rating, review, created_at) 
          VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiiis", $reviewer_id, $reviewee_id, $agreement_id, $rating, $review);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}
?>