<?php
session_start();
include "includes/db_connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $course_id = (int)$_POST['course_id'];

    $check_stmt = $conn->prepare("SELECT 1 FROM tr_coursepurchase WHERE user_id = ? AND course_id = ?");
    $check_stmt->bind_param("ii", $user_id, $course_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You already own this course!']);
        exit;
    }

    $stmt = $conn->prepare("SELECT credits_price, user_id as owner_id FROM ms_courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();

    if (!$course) {
        echo json_encode(['status' => 'error', 'message' => 'Course not found.']);
        exit;
    }

    $price    = $course['credits_price'];
    $owner_id = $course['owner_id'];

    $stmt_credit = $conn->prepare("SELECT balance FROM ms_usercredits WHERE user_id = ?");
    $stmt_credit->bind_param("i", $user_id);
    $stmt_credit->execute();
    $user_credit = $stmt_credit->get_result()->fetch_assoc();

    if (!$user_credit || $user_credit['balance'] < $price) {
        echo json_encode(['status' => 'error', 'message' => 'Insufficient credits!']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("UPDATE ms_usercredits SET balance = balance - ? WHERE user_id = ?");
        $upd->bind_param("ii", $price, $user_id);
        $upd->execute();

        $upd_owner = $conn->prepare("UPDATE ms_usercredits SET balance = balance + ? WHERE user_id = ?");
        $upd_owner->bind_param("ii", $price, $owner_id);
        $upd_owner->execute();

        $ins = $conn->prepare("INSERT INTO tr_coursepurchase (user_id, course_id, created_at) VALUES (?, ?, NOW())");
        $ins->bind_param("ii", $user_id, $course_id);
        $ins->execute();

        $conn->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'System error. Transaction failed.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request or user not logged in.']);
}