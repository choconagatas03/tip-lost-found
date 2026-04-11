<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'])) {
    header('Location: signin.php');
    exit;
}

// Handle Mark as Retrieved
if (isset($_GET['retrieve']) && isset($_GET['claim_id'])) {
    $claim_id = (int)$_GET['claim_id'];
    $stmt = $pdo->prepare("SELECT item_id FROM claims WHERE claim_id = ?");
    $stmt->execute([$claim_id]);
    $item_id = $stmt->fetchColumn();

    if ($item_id) {
        $pdo->prepare("UPDATE claims SET status = 'retrieved', retrieved_date = CURRENT_DATE WHERE claim_id = ?")->execute([$claim_id]);
        $pdo->prepare("UPDATE lost_items SET status = 'resolved' WHERE id = ?")->execute([$item_id]);
        header('Location: admin_claims.php?msg=Item marked as retrieved.');
        exit;
    }
}

// Fetch claims with the new description field
$sql = "SELECT c.*, li.item_type as item_name, u.full_name AS claimant_name
        FROM claims c
        LEFT JOIN lost_items li ON c.item_id = li.id
        LEFT JOIN users u ON c.claimant_user_id = u.user_id
        ORDER BY c.claim_date DESC";
$claims = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-tasks"></i> Manage Claims</h1>
</div>

<div class="card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 2px solid #eee;">
                <th style="padding: 12px;">Item</th>
                <th style="padding: 12px;">Claimant</th>
                <th style="padding: 12px; width: 300px;">Proof / Appeal Review</th>
                <th style="padding: 12px;">Status</th>
                <th style="padding: 12px; text-align:center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($claims as $row): ?>
            <tr style="border-bottom: 1px solid #f9f9f9;">
                <td style="padding: 12px;"><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                <td style="padding: 12px;"><?php echo htmlspecialchars($row['claimant_name']); ?></td>
                
                <td style="padding: 12px;">
                    <div style="font-size: 13px; background: #f8fafc; padding: 10px; border-radius: 6px; border-left: 4px solid #3182ce; color: #4a5568;">
                        <?php echo !empty($row['claim_description']) ? nl2br(htmlspecialchars($row['claim_description'])) : '<em>No description</em>'; ?>
                    </div>
                </td>

                <td style="padding: 12px;">
                    <span class="badge badge-<?php echo ($row['status']=='pending')?'warning':'success'; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </span>
                </td>
                <td style="padding: 12px; text-align:center;">
                    <?php if ($row['status'] === 'pending'): ?>
                        <a href="approve_claim.php?claim_id=<?php echo $row['claim_id']; ?>" class="btn btn-success btn-sm">Approve</a>
                        <a href="reject_claim.php?claim_id=<?php echo $row['claim_id']; ?>" class="btn btn-danger btn-sm">Reject</a>
                    <?php elseif ($row['status'] === 'approved'): ?>
                        <a href="admin_claims.php?retrieve=1&claim_id=<?php echo $row['claim_id']; ?>" 
                           class="btn btn-primary btn-sm" onclick="return confirm('Complete retrieval?')">Complete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>