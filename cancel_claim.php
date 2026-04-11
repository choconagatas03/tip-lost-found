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
    // Corrected: only claimant_user_id (no OR with user_id)
    $stmt = $pdo->prepare("SELECT * FROM claims WHERE claim_id = ? AND claimant_user_id = ? AND status = 'pending'");
    $stmt->execute([$claim_id, $user_id]);
    $claim = $stmt->fetch();

    if (!$claim) {
        header('Location: claimed_items.php?msg=NoChange');
        exit;
    }

    $pdo->beginTransaction();

    // Remove the claim
    $delete = $pdo->prepare("DELETE FROM claims WHERE claim_id = ?");
    $delete->execute([$claim_id]);

    // Reset the item status to 'lost'
    $update = $pdo->prepare("UPDATE lost_items SET status = 'lost' WHERE id = ?");
    $update->execute([$item_id]);

    $pdo->commit();
    header('Location: claimed_items.php?msg=Cancelled');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Cancellation failed: " . $e->getMessage());
}
?>