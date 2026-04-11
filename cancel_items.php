<?php
session_start();
require_once 'db.php'; // PDO connection

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$claim_id = (int)$_GET['claim_id'];
$item_id  = (int)$_GET['item_id'];
$user_id  = $_SESSION['user_id'];

// Verify that this claim belongs to the current user and is still pending
$check = "SELECT c.*, li.status as item_status
          FROM claims c
          LEFT JOIN lost_items li ON c.item_id = li.id
          WHERE c.claim_id = :claim_id AND c.claimant_user_id = :user_id AND c.status = 'pending'";
$stmt = $pdo->prepare($check);
$stmt->execute([':claim_id' => $claim_id, ':user_id' => $user_id]);
$claim = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$claim) {
    die("Invalid claim or you cannot cancel this claim.");
}

// Delete the claim record
$delete = "DELETE FROM claims WHERE claim_id = :claim_id";
$del_stmt = $pdo->prepare($delete);
if ($del_stmt->execute([':claim_id' => $claim_id])) {
    // Revert item status to 'lost' so it becomes available again
    $update_item = "UPDATE lost_items SET status = 'lost' WHERE id = :item_id";
    $up_stmt = $pdo->prepare($update_item);
    $up_stmt->execute([':item_id' => $item_id]);
    
    header('Location: claimed_items.php?msg=Cancelled');
    exit;
} else {
    echo "Error cancelling claim. Please try again.";
}
?>