<?php
session_start();
require_once 'db.php'; // Ensure this points to your actual PDO connection file

// Check if user is admin/staff
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    die("Unauthorized access.");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        // 1. Get image filename first to delete the physical file
        $stmt = $pdo->prepare("SELECT image FROM lost_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if ($item && !empty($item['image'])) {
            $imagePath = 'uploads/' . $item['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath); // Delete the actual photo from the folder
            }
        }

        // 2. Delete the record from the database
        $delete = $pdo->prepare("DELETE FROM lost_items WHERE id = ?");
        $delete->execute([$id]);

        header("Location: manage_items.php?msg=deleted");
        exit;

    } catch (Exception $e) {
        die("Error deleting item: " . $e->getMessage());
    }
} else {
    header("Location: manage_items.php");
    exit;
}
?>