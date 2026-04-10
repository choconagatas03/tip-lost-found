<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: signin.php");
    exit;
}

$action = $_POST['action'] ?? '';
$admin_user_id = (int)$_SESSION['user_id'];

$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$upload_dir = __DIR__ . '/uploads/';

function handle_image_upload($file_field, $existing_filename = null) {
    global $allowed_types, $upload_dir;
    if (!isset($_FILES[$file_field]) || $_FILES[$file_field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing_filename;
    }
    $file = $_FILES[$file_field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("File upload error.");
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed_types)) {
        die("Invalid image type. Allowed: jpeg, png, gif.");
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        die("Image too large. Max 4MB.");
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_name = uniqid('img_') . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
        die("Failed to move uploaded file.");
    }
    if ($existing_filename && file_exists($upload_dir . $existing_filename)) {
        @unlink($upload_dir . $existing_filename);
    }
    return $new_name;
}

if ($action === 'add') {
    $description = $_POST['description'] ?? '';
    $date_lost = $_POST['date_lost'] ?: null;
    $item_type = $_POST['item_type'] ?? '';
    $status = $_POST['status'] ?? 'lost';

    $image_name = handle_image_upload('image', null);

    $sql = "INSERT INTO lost_items (user_id, description, date_lost, item_type, status, image) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssss", $admin_user_id, $description, $date_lost, $item_type, $status, $image_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: manage_items.php?msg=Item added");
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = mysqli_prepare($conn, "SELECT image FROM lost_items WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    $existing_image = $row['image'] ?? null;

    $description = $_POST['description'] ?? '';
    $date_lost = $_POST['date_lost'] ?: null;
    $item_type = $_POST['item_type'] ?? '';
    $status = $_POST['status'] ?? 'lost';

    $image_name = handle_image_upload('image', $existing_image);

    $sql = "UPDATE lost_items SET description=?, date_lost=?, item_type=?, status=?, image=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssi", $description, $date_lost, $item_type, $status, $image_name, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: manage_items.php?msg=Item updated");
    exit;
}

header("Location: manage_items.php");
exit;
