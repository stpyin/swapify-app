<?php
session_start();
include "includes/db_connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$action = $_POST['action'] ?? '';
$upload_dir = 'uploads/booster/';

function handleFileUpload($upload_dir, $existing_icon = null) {
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['icon']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = uniqid('boost_') . '.' . $ext;
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES['icon']['tmp_name'], $upload_dir . $new_filename)) {
                if ($existing_icon && file_exists($upload_dir . $existing_icon)) {
                    unlink($upload_dir . $existing_icon);
                }
                return $new_filename;
            }
        }
    }
    return $existing_icon; 
}

if ($action === 'add') {
    $package_name = trim($_POST['package_name']);
    $multiplier = floatval($_POST['multiplier']);
    $package_price = intval($_POST['package_price']);

    if (empty($package_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Booster name is required']);
        exit();
    }

    $image_url = handleFileUpload($upload_dir);

    $check_query = "SELECT package_id FROM ms_boosters WHERE package_name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $package_name);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'A booster with this name already exists']);
        exit();
    }

    $insert_query = "INSERT INTO ms_boosters (package_name, image_url, multiplier, package_price, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssdi", $package_name, $image_url, $multiplier, $package_price);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Booster added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add booster: ' . $conn->error]);
    }
}

elseif ($action === 'edit') {
    $package_id = intval($_POST['package_id']);
    $package_name = trim($_POST['package_name']);
    $multiplier = floatval($_POST['multiplier']);
    $package_price = intval($_POST['package_price']);

    $stmt = $conn->prepare("SELECT image_url FROM ms_boosters WHERE package_id = ?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();

    if (!$current) {
        echo json_encode(['status' => 'error', 'message' => 'Booster not found']);
        exit();
    }

    $image_url = handleFileUpload($upload_dir, $current['image_url']);

    $check_name = "SELECT package_id FROM ms_boosters WHERE package_name = ? AND package_id != ?";
    $stmt = $conn->prepare($check_name);
    $stmt->bind_param("si", $package_name, $package_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Another booster already uses this name']);
        exit();
    }

    $update_query = "UPDATE ms_boosters SET package_name = ?, image_url = ?, multiplier = ?, package_price = ? WHERE package_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssdii", $package_name, $image_url, $multiplier, $package_price, $package_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Booster updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update booster: ' . $conn->error]);
    }
}

elseif ($action === 'delete') {
    $package_id = intval($_POST['package_id']);

    $stmt = $conn->prepare("SELECT image_url FROM ms_boosters WHERE package_id = ?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $booster = $stmt->get_result()->fetch_assoc();

    if (!$booster) {
        echo json_encode(['status' => 'error', 'message' => 'Booster not found']);
        exit();
    }

    $delete_query = "DELETE FROM ms_boosters WHERE package_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $package_id);

    if ($stmt->execute()) {
        if ($booster['image_url'] && file_exists($upload_dir . $booster['image_url'])) {
            unlink($upload_dir . $booster['image_url']);
        }
        echo json_encode(['status' => 'success', 'message' => 'Booster deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete: ' . $conn->error]);
    }
}

else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

$conn->close();
?>