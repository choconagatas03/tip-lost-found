<?php
session_start();
require_once 'db.php'; // PDO connection

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch only current user's claims – using lost_items table
$sql = "SELECT c.*, 
               li.item_type AS item_name, 
               li.description, 
               li.image AS image_path, 
               li.id AS item_id, 
               li.status AS item_status
        FROM claims c
        LEFT JOIN lost_items li ON c.item_id = li.id
        WHERE c.claimant_user_id = :user_id
        ORDER BY c.claim_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-clipboard-list"></i> My Claims</h1>
    <p>Track the status of your item claims</p>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'Cancelled'): ?>
    <div class="alert alert-success">Claim cancelled successfully. The item is now available again.</div>
<?php endif; ?>

<?php if (count($claims) > 0): ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Claim Date</th>
                    <th>Status</th>
                    <th>Admin Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($claims as $claim): ?>
                    <?php
                    $status = $claim['status'];
                    $badge_class = '';
                    $status_text = '';
                    switch ($status) {
                        case 'pending':
                            $badge_class = 'badge-warning';
                            $status_text = ' Pending ';
                            break;
                        case 'approved':
                            $badge_class = 'badge-success';
                            $status_text = ' Approved ';
                            break;
                        case 'retrieved':
                            $badge_class = 'badge-info';
                            $status_text = ' Retrieved';
                            break;
                        case 'rejected':
                            $badge_class = 'badge-danger';
                            $status_text = ' Rejected';
                            break;
                        default:
                            $badge_class = 'badge-secondary';
                            $status_text = ucfirst($status);
                    }
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($claim['image_path'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($claim['image_path']); ?>" style="width:40px; height:40px; object-fit:cover; border-radius:4px; margin-right:10px;">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($claim['item_name'] ?? 'Item Deleted'); ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($claim['claim_date'])); ?></td>
                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span></td>
                        <td><?php echo nl2br(htmlspecialchars($claim['admin_notes'] ?? '—')); ?></td>
                        <td>
                            <?php if ($status === 'pending' && !empty($claim['item_id'])): ?>
                                <a href="cancel_claim.php?claim_id=<?php echo $claim['claim_id']; ?>&item_id=<?php echo $claim['item_id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Cancel this claim? The item will become available again.')">
                                    <i class="fas fa-times"></i> Cancel Claim
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-clipboard-list"></i></div>
        <h4>No claims found</h4>
        <p>You haven't claimed any items yet. Visit <a href="index.php">Lost Items</a> to claim.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>