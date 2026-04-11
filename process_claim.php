<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $item_id = (int)$_POST['item_id'];
    $user_id = $_SESSION['user_id'];
    $description = trim($_POST['claim_description'] ?? '');

    try {
        $pdo->beginTransaction();

        // 1. Insert into claims table with the new description
        $stmt = $pdo->prepare("INSERT INTO claims (item_id, user_id, claim_description, status, claim_date) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([$item_id, $user_id, $description]);

        // 2. Update item status to 'pending'
        $update = $pdo->prepare("UPDATE lost_items SET status = 'pending' WHERE id = ?");
        $update->execute([$item_id]);

        $pdo->commit();
        header("Location: my_claims.php?msg=success");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error processing claim: " . $e->getMessage());
    }
}