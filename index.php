<?php
session_start();
include 'includes/header.php';
require_once 'db.php'; 

$type = trim($_GET['item_type'] ?? '');
$date_lost = trim($_GET['date_lost'] ?? '');
$q = trim($_GET['q'] ?? '');

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

$where_sql = "WHERE " . implode(' AND ', $where_clauses);

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
            <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Keyword...">
        </div>
        <div class="filter-group">
            <label>Item Type</label>
            <input type="text" name="item_type" value="<?php echo htmlspecialchars($type); ?>" placeholder="e.g. Phone">
        </div>
        <div class="filter-group">
            <label>Date Lost</label>
            <input type="date" name="date_lost" value="<?php echo htmlspecialchars($date_lost); ?>">
        </div>
        <div class="filter-actions">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
            <a href="index.php" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="gallery">
    <?php foreach ($items as $item):
        $posted_name = $item['posted_by'] ?? 'Unknown User';
        $initial = strtoupper(substr($posted_name, 0, 1));
        $item_id = $item['item_id'] ?? $item['id'] ?? 0;
    ?>
        <div class="item-card">
            <?php if (!empty($item['image'])): ?>
                <img class="item-card-img" src="uploads/<?php echo htmlspecialchars($item['image']); ?>">
            <?php else: ?>
                <div class="item-card-img-placeholder"><i class="fas fa-image"></i></div>
            <?php endif; ?>

            <div class="item-card-body">
                <div class="item-card-id">Item #<?php echo (int)$item_id; ?></div>
                <div class="item-card-type"><?php echo htmlspecialchars($item['item_type']); ?></div>
                
                <div class="item-card-meta">
                    <div class="item-card-poster">
                        <div class="item-card-poster-avatar"><?php echo $initial; ?></div>
                        <span><?php echo htmlspecialchars($posted_name); ?></span>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="margin-top:12px;">
                        <a href="claim_item.php?item_id=<?php echo $item_id; ?>" class="btn btn-primary btn-sm" style="width:100%; display:block; text-align:center;">
                            Claim This Item
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include 'includes/footer.php'; ?>