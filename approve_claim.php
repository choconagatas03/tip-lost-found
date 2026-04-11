<?php
session_start();
require_once 'db.php';

// Access Control
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'])) {
    header('Location: signin.php');
    exit;
}

$claim_id = (int)$_GET['claim_id'];
$success = false;

// Fetch claim and item details for the confirmation card
$stmt = $pdo->prepare("SELECT c.*, li.item_type, u.full_name 
                       FROM claims c 
                       JOIN lost_items li ON c.item_id = li.id 
                       JOIN users u ON c.claimant_user_id = u.user_id 
                       WHERE c.claim_id = ?");
$stmt->execute([$claim_id]);
$claim = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$claim) {
    die("Claim not found.");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = trim($_POST['admin_notes']);
    
    $update = $pdo->prepare("UPDATE claims SET status = 'approved', admin_notes = ? WHERE claim_id = ?");
    if ($update->execute([$notes, $claim_id])) {
        $success = true;
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 600px; margin: 50px auto; padding: 0 20px;">
    <?php if ($success): ?>
        <div class="card" style="text-align: center; padding: 40px; background: white; border-radius: 12px; shadow: 0 4px 20px rgba(0,0,0,0.1);">
            <div style="color: #38a169; font-size: 50px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 style="color: #2d3748; margin-bottom: 10px;">Claim Approved!</h2>
            <p style="color: #718096; margin-bottom: 30px;">The student has been notified to pick up their item.</p>
            <a href="admin_claims.php" class="btn" style="background: #3182ce; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold;">Return to Dashboard</a>
        </div>
    <?php else: ?>
        <div class="card" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #edf2f7;">
            <div style="background: #f8fafc; padding: 20px; border-bottom: 1px solid #edf2f7; text-align: center;">
                <h3 style="margin: 0; color: #2d3748;"><i class="fas fa-thumbs-up" style="color: #38a169;"></i> Confirm Approval</h3>
            </div>
            
            <form method="POST" style="padding: 30px;">
                <div style="background: #ebf8ff; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #bee3f8;">
                    <p style="margin: 0; color: #2c5282; font-size: 14px;">
                        Approving claim for <strong><?php echo htmlspecialchars($claim['item_type']); ?></strong> 
                        requested by <strong><?php echo htmlspecialchars($claim['full_name']); ?></strong>.
                    </p>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #4a5568;">Admin Notes / Instructions:</label>
                    <textarea name="admin_notes" required
                        placeholder="e.g. Please pick up your item at the OSA office during office hours."
                        style="width: 100%; height: 120px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; resize: vertical;"></textarea>
                    <small style="color: #a0aec0;">These notes will be visible to the student.</small>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="submit" style="flex: 2; background: #38a169; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#2f855a'" onmouseout="this.style.background='#38a169'">
                        Confirm & Approve
                    </button>
                    <a href="admin_claims.php" style="flex: 1; text-align: center; background: #edf2f7; color: #4a5568; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#edf2f7'">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>