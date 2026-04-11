<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$item_id = (int)$_GET['item_id'];
$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

// Check if item exists
$stmt = $pdo->prepare("SELECT * FROM lost_items WHERE id = ? AND status IN ('lost', 'found')");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    $error = "Item not found or already claimed.";
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $description = trim($_POST['claim_description']);
    
    if (empty($description)) {
        $error = "Please provide details/proof of ownership to help us verify your claim.";
    } else {
        // Check for existing claim
        $stmt2 = $pdo->prepare("SELECT * FROM claims WHERE item_id = ? AND claimant_user_id = ? AND status IN ('pending', 'approved')");
        $stmt2->execute([$item_id, $user_id]);
        if ($stmt2->fetch()) {
            $error = "You already have a pending claim for this item.";
        } else {
            // Insert with description
            $insert = "INSERT INTO claims (item_id, claimant_user_id, claim_date, status, claim_description) 
                       VALUES (?, ?, CURRENT_DATE, 'pending', ?)";
            $stmt3 = $pdo->prepare($insert);
            if ($stmt3->execute([$item_id, $user_id, $description])) {
                $pdo->prepare("UPDATE lost_items SET status = 'claimed' WHERE id = ?")->execute([$item_id]);
                $success = "Claim submitted successfully! The staff will review your proof.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-hand-holding-heart"></i> Claim Item: <?php echo htmlspecialchars($item['item_type'] ?? 'Item'); ?></h1>
</div>

<div class="auth-card" style="max-width:600px; margin:40px auto; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
    <div class="auth-form-area" style="padding:40px;">
        <?php if ($success): ?>
            <div class="alert alert-success" style="text-align:center;">
                <i class="fas fa-check-circle" style="font-size: 40px; display:block; margin-bottom:10px;"></i>
                <?php echo $success; ?>
            </div>
            <div style="text-align:center; margin-top:20px;">
                <a href="index.php" class="btn btn-primary">Back to Home</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; display:block; margin-bottom: 8px;">Proof of Ownership / Appeal</label>
                    <p style="font-size: 13px; color: #666; margin-bottom: 10px;">
                        Describe unique marks, serial numbers, or the exact contents of the item to prove it is yours.
                    </p>
                    <textarea name="claim_description" required 
                        style="width: 100%; height: 150px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;"
                        placeholder="e.g. The wallet has a family photo inside and a total of 500 pesos..."></textarea>
                </div>
                <button type="submit" class="btn btn-warning" style="width:100%; font-weight:bold;">
                    Submit Claim Request
                </button>
                <a href="index.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>