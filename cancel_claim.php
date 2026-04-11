<?php
session_start();
require_once 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$claim_id = (int)($_GET['claim_id'] ?? 0);
$item_id  = (int)($_GET['item_id'] ?? 0);
$user_id  = $_SESSION['user_id'];

try {
    // 1. Check for the claim using BOTH possible column names to be safe
    // We check if it's 'pending' because you can't cancel an already approved item.
    $stmt = $pdo->prepare("SELECT * FROM claims WHERE claim_id = ? AND (user_id = ? OR claimant_user_id = ?) AND status = 'pending'");
    $stmt->execute([$claim_id, $user_id, $user_id]);
    $claim = $stmt->fetch();

    if (!$claim) {
        // If it reaches here, the claim might already be deleted or approved.
        header('Location: claimed_items.php?msg=NoChange');
        exit;
    }

    $pdo->beginTransaction();

    // 2. Remove the claim
    $delete = $pdo->prepare("DELETE FROM claims WHERE claim_id = ?");
    $delete->execute([$claim_id]);

    // 3. Reset the item status to 'lost' in the correct table 'lost_items'
    // Based on your Reports page, the table is lost_items and the primary key is 'id' or 'item_id'
    $update = $pdo->prepare("UPDATE lost_items SET status = 'lost' WHERE id = ?");
    $update->execute([$item_id]);

    $pdo->commit();
    header('Location: claimed_items.php?msg=Cancelled');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Cancellation failed: " . $e->getMessage());
}