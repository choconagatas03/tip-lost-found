<?php
include 'db.php';
session_start();

// Security Check: Only admins allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: signin.php");
    exit;
}

$action = $_POST['action'] ?? '';
$upload_dir = __DIR__ . '/uploads/';

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

function handle_image_upload($file_field, $existing_filename = null) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $upload_dir = __DIR__ . '/uploads/';

    if (!isset($_FILES[$file_field]) || $_FILES[$file_field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing_filename;
    }

    $file = $_FILES[$file_field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $existing_filename; // Or handle error appropriately
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed_types)) {
        die("Invalid image type. Allowed: jpeg, png, gif.");
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_name = uniqid('img_') . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
        if ($existing_filename && file_exists($upload_dir . $existing_filename)) {
            @unlink($upload_dir . $existing_filename);
        }
        return $new_name;
    }
    return $existing_filename;
}

if ($action === 'add') {
    $image_name = handle_image_upload('image', null);
    
    // Get date_lost from form (if provided, otherwise NULL)
    $date_lost = !empty($_POST['date_lost']) ? $_POST['date_lost'] : null;
    
    try {
        $sql = "INSERT INTO lost_items (item_type, description, status, user_id, image, created_at, date_lost) 
                VALUES (:type, :desc, :status, :user, :img, NOW(), :date_lost)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'type'      => $_POST['item_type'],
            'desc'      => $_POST['description'],
            'status'    => $_POST['status'],
            'user'      => $_SESSION['user_id'],
            'img'       => $image_name,
            'date_lost' => $date_lost
        ]);

        header("Location: manage_items.php?msg=Item added successfully");
        exit;
    } catch (PDOException $e) {
        die("Error adding item: " . $e->getMessage());
    }
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    
    try {
        // 1. Get current image to potentially delete it
        $stmt = $pdo->prepare("SELECT image FROM lost_items WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $existing_image = $row['image'] ?? null;

        // 2. Handle fields
        $description = $_POST['description'] ?? '';
        $date_lost   = !empty($_POST['date_lost']) ? $_POST['date_lost'] : null;
        $item_type   = $_POST['item_type'] ?? '';
        $status      = $_POST['status'] ?? 'lost';
        $image_name  = handle_image_upload('image', $existing_image);

        // 3. Update PostgreSQL
        $sql = "UPDATE lost_items 
                SET description = :desc, 
                    date_lost = :date, 
                    item_type = :type, 
                    status = :status, 
                    image = :img 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'desc'   => $description,
            'date'   => $date_lost,
            'type'   => $item_type,
            'status' => $status,
            'img'    => $image_name,
            'id'     => $id
        ]);

        header("Location: manage_items.php?msg=Item updated successfully");
        exit;
    } catch (PDOException $e) {
        die("Error updating item: " . $e->getMessage());
    }
}

header("Location: manage_items.php");
exit;