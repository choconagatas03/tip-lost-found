<?php
include 'includes/header.php';
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: signin.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: manage_items.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT image FROM lost_items WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if ($row['image']) {
    $file = __DIR__ . '/uploads/' . $row['image'];
    if (file_exists($file)) @unlink($file);
}

$stmt = mysqli_prepare($conn, "DELETE FROM lost_items WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: manage_items.php?msg=Item deleted");
exit;
