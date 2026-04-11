<?php
session_start();
include 'includes/header.php';
require_once 'db.php'; // Ensure this returns a PDO object named $pdo

// Enable error reporting temporarily
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$type = trim($_GET['item_type'] ?? '');
$date_lost = trim($_GET['date_lost'] ?? '');
$q = trim($_GET['q'] ?? '');

// 1. Initialize logic for PostgreSQL
$where_clauses = ["li.status ILIKE 'lost'"]; 
$params = [];

if ($type !== '') {
    $where_clauses[] = "li.item_type ILIKE :type";
    $params[':type'] = "%$type%";
}
if ($date_lost !== '') {
    $where_clauses[] = "li.date_lost = :date_lost";
    $params[':date_lost'] = $date_lost;
}
if ($q !== '') {
    $where_clauses[] = "(li.description ILIKE :q OR li.item_type ILIKE :q)";
    $params[':q'] = "%$q%";
}

// Construct the final WHERE string
$where_sql = "WHERE " . implode(' AND ', $where_clauses);

// 2. Corrected Query
$sql = "SELECT li.*, u.full_name AS posted_by
        FROM lost_items li
        LEFT JOIN users u ON li.user_id = u.user_id
        $where_sql
        ORDER BY li.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($items);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$user_role = $_SESSION['role'] ?? 'user';
$is_staff_admin = ($user_role === 'admin' || $user_role === 'staff');
?>

<div class="filters-bar">
    <form method="GET" style="display:contents;">
        <div class="filter-group" style="flex:2;min-width:180px;">
            <label>Search</label>
            <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Keyword — description or type">
        </div>
        <div class="filter-group">
            <label>Item Type</label>
            <input type="text" name="item_type" value="<?php echo htmlspecialchars($type); ?>" placeholder="e.g. Phone, Wallet">
        </div>
        <div class="filter-group">
            <label>Date Lost</label>
            <input type="date" name="date_lost" value="<?php echo htmlspecialchars($date_lost); ?>">
        </div>
        <div class="filter-actions">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-xmark"></i> Clear</a>
        </div>
    </form>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <p style="font-size:14px;color:var(--gray);margin:0;">
        <?php if ($count > 0): ?>
            Showing <strong><?php echo $count; ?></strong> lost item<?php echo $count !== 1 ? 's' : ''; ?>
        <?php else: ?>
            No items found
        <?php endif; ?>
    </p>
</div>

<?php if ($count > 0): ?>
    <div class="gallery">
        <?php foreach ($items as $item):
            $posted_name = $item['posted_by'] ?? 'Unknown User';
            $initial = strtoupper(substr($posted_name, 0, 1));
            // Case-insensitive status check
            $status_check = strtolower($item['status'] ?? '');
        ?>
            <div class="item-card">
                <?php if (!empty($item['image'])): ?>
                    <img class="item-card-img" src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['item_type'] ?? 'Item'); ?>">
                <?php else: ?>
                    <div class="item-card-img-placeholder">
                        <i class="fas fa-image"></i>
                        <span>No Photo</span>
                    </div>
                <?php endif; ?>

                <div class="item-card-body">
                    <div class="item-card-id">Item #<?php echo (int)($item['id'] ?? $item['item_id'] ?? 0); ?></div>
                    <div class="item-card-type"><?php echo htmlspecialchars($item['item_type'] ?? 'Unknown'); ?></div>

                    <?php if ($is_staff_admin && !empty($item['description'])): ?>
                        <div class="item-card-desc">
                            <?php echo htmlspecialchars(mb_strimwidth($item['description'] ?? '', 0, 90, '…')); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_staff_admin && !empty($item['date_lost'])): ?>
                        <div style="font-size:12px;color:var(--gray);display:flex;align-items:center;gap:5px;margin-top:4px;">
                            <i class="fas fa-calendar-days" style="color:var(--tip-gold-deep);"></i>
                            Lost on <?php echo date('M j, Y', strtotime($item['date_lost'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="item-card-meta">
                        <div class="item-card-poster">
                            <div class="item-card-poster-avatar"><?php echo $initial; ?></div>
                            <span><?php echo htmlspecialchars($posted_name); ?></span>
                        </div>
                        <span class="badge badge-lost">Lost</span>
                    </div>

                    <?php if (isset($_SESSION['user_id']) && $status_check === 'lost'): ?>
                        <div style="margin-top:12px;">
                            <a href="claim_item.php?item_id=<?php echo $item['id'] ?? $item['item_id'] ?? 0; ?>" class="btn btn-primary btn-sm" style="width:100%;">
                                <i class="fas fa-hand-holding-heart"></i> Claim This Item
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-search"></i></div>
        <h4>No lost items found</h4>
        <p><?php echo ($q || $type || $date_lost) ? 'Try adjusting your filters or clearing the search.' : 'No lost items have been reported yet.'; ?></p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>