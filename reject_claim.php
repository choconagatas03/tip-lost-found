<?php
session_start();
require_once 'db.php'; 

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: signin.php');
    exit;
}

$claim_id = (int)($_GET['claim_id'] ?? 0);
$error = '';

try {
    $sql = "SELECT c.*, i.item_type, i.id as item_id 
            FROM claims c 
            LEFT JOIN lost_items i ON c.item_id = i.id 
            WHERE c.claim_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$claim_id]);
    $claim = $stmt->fetch();

    if (!$claim) {
        die("Claim not found.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        $pdo->beginTransaction();

        $update = $pdo->prepare("UPDATE claims SET status = 'rejected', admin_notes = ? WHERE claim_id = ?");
        $update->execute([$admin_notes, $claim_id]);

        $update_item = $pdo->prepare("UPDATE lost_items SET status = 'lost' WHERE id = ?");
        $update_item->execute([$claim['item_id']]);

        $pdo->commit();
        header('Location: admin_claims.php?msg=Claim+rejected');
        exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = "Error: " . $e->getMessage();
}

include 'includes/header.php'; 
?>

<style>
    /* UI Improvement Styles */
    .reject-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
        font-family: 'Inter', -apple-system, sans-serif;
    }

    .reject-header {
        margin-bottom: 30px;
        text-align: left;
    }

    .reject-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 8px;
    }

    .item-badge {
        display: inline-block;
        background: #f0f2f5;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        color: #4b5563;
    }

    .card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        padding: 40px;
        border: 1px solid #edf2f7;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .textarea-custom {
        width: 100%;
        min-height: 150px;
        padding: 15px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 16px;
        transition: border-color 0.2s, box-shadow 0.2s;
        resize: vertical;
        box-sizing: border-box;
    }

    .textarea-custom:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-confirm {
        background: #ef4444;
        color: white;
        border: none;
        padding: 14px 28px;
        border-radius: 10px;
        font-weight: 700;
        cursor: pointer;
        flex: 2;
        transition: background 0.2s;
    }

    .btn-confirm:hover {
        background: #dc2626;
    }

    .btn-cancel {
        background: white;
        color: #6b7280;
        border: 2px solid #e5e7eb;
        padding: 14px 28px;
        border-radius: 10px;
        font-weight: 700;
        text-decoration: none;
        text-align: center;
        flex: 1;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .alert-box {
        background: #fee2e2;
        color: #b91c1c;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }
</style>

<div class="reject-container">
    <div class="reject-header">
        <h1>Reject Claim</h1>
        <div class="item-badge">
            Item: <?php echo htmlspecialchars($claim['item_type'] ?? 'Unknown Item'); ?>
        </div>
    </div>

    <div class="card">
        <?php if ($error): ?>
            <div class="alert-box"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Reason for Rejection</label>
                <textarea 
                    name="admin_notes" 
                    class="textarea-custom" 
                    placeholder="Provide a clear explanation for the student (e.g., 'Verification details did not match' or 'Ownership could not be confirmed')..." 
                    required></textarea>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-confirm">Confirm Rejection</button>
                <a href="admin_claims.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php // Add closing tags if not in footer ?>
</body>
</html>