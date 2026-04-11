<?php
session_start();
include 'includes/header.php';
require_once 'db.php'; 

// 1. Setup Filters
$type      = trim($_GET['item_type'] ?? '');
$date_lost = trim($_GET['date_lost'] ?? '');
$q         = trim($_GET['q'] ?? '');

// Initialize query components
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

// 2. Database Execution
try {
    $sql = "SELECT li.*, u.full_name AS posted_by
            FROM lost_items li
            LEFT JOIN users u ON li.user_id = u.user_id
            $where_sql
            ORDER BY li.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($items);
} catch (PDOException $e) {
    // Log the error and show a safe message
    error_log("Query Error: " . $e->getMessage());
    $items = [];
    $count = 0;
}

$user_role = $_SESSION['role'] ?? 'user';
$is_staff_admin = ($user_role === 'admin' || $user_role === 'staff');
?>

<div class="filters-bar" style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #eee;">
    <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
        <div class="filter-group">
            <label style="display:block; font-size:12px; margin-bottom:5px; font-weight:bold;">Search</label>
            <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Keyword..." style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div class="filter-group">
            <label style="display:block; font-size:12px; margin-bottom:5px; font-weight:bold;">Item Type</label>
            <input type="text" name="item_type" value="<?php echo htmlspecialchars($type); ?>" placeholder="e.g. Phone" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div class="filter-group">
            <label style="display:block; font-size:12px; margin-bottom:5px; font-weight:bold;">Date Lost</label>
            <input type="date" name="date_lost" value="<?php echo htmlspecialchars($date_lost); ?>" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div class="filter-actions" style="display: flex; gap: 10px;">
            <button type="submit" style="background: #d4af37; color: #fff; border: none; padding: 9px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                <i class="fas fa-search"></i> Search
            </button>
            <a href="index.php" class="btn btn-secondary" style="text-decoration: none; background: #f5f5f5; color: #666; padding: 9px 20px; border-radius: 4px; font-size: 14px;">Clear</a>
        </div>
    </form>
</div>

<div style="margin-bottom: 15px;">
    <p style="font-size: 14px; color: #666;">
        Showing <strong><?php echo $count; ?></strong> lost items.
    </p>
</div>

<?php if ($count > 0): ?>
    <div class="gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        <?php foreach ($items as $item):
            $posted_name = $item['posted_by'] ?? 'Unknown User';
            $initial = strtoupper(substr($posted_name, 0, 1));
            // Postgres column name fallback
            $item_id = $item['item_id'] ?? $item['id'] ?? 0;
        ?>
            <div class="item-card" style="background: #fff; border: 1px solid #eee; border-radius: 10px; overflow: hidden; transition: transform 0.2s;">
                <?php if (!empty($item['image'])): ?>
                    <img class="item-card-img" src="uploads/<?php echo htmlspecialchars($item['image']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
                <?php else: ?>
                    <div class="item-card-img-placeholder" style="width: 100%; height: 200px; background: #f9f9f9; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ccc;">
                        <i class="fas fa-image" style="font-size: 40px; margin-bottom: 10px;"></i>
                        <span>No Photo</span>
                    </div>
                <?php endif; ?>

                <div class="item-card-body" style="padding: 15px;">
                    <div class="item-card-id" style="font-size: 10px; color: #999; font-weight: bold; text-transform: uppercase;">Item #<?php echo (int)$item_id; ?></div>
                    <div class="item-card-type" style="font-size: 18px; font-weight: bold; color: #333; margin: 5px 0;"><?php echo htmlspecialchars($item['item_type']); ?></div>
                    
                    <div class="item-card-meta" style="display: flex; align-items: center; gap: 10px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f5f5f5;">
                        <div class="item-card-poster" style="display: flex; align-items: center; gap: 8px;">
                            <div class="item-card-poster-avatar" style="width: 24px; height: 24px; background: #d4af37; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                                <?php echo $initial; ?>
                            </div>
                            <span style="font-size: 13px; color: #555;"><?php echo htmlspecialchars($posted_name); ?></span>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="margin-top: 15px;">
                            <a href="claim_item.php?item_id=<?php echo $item_id; ?>" class="btn btn-primary btn-sm" style="display: block; width: 100%; text-align: center; background: #d4af37; color: #fff; text-decoration: none; padding: 10px; border-radius: 5px; font-weight: bold; font-size: 13px;">
                                <i class="fas fa-hand-holding-heart"></i> Claim This Item
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state" style="text-align: center; padding: 50px; background: #fff; border-radius: 8px; border: 1px dashed #ccc;">
        <div class="empty-state-icon" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"><i class="fas fa-search"></i></div>
        <h4 style="color: #999;">No lost items found</h4>
        <p style="color: #bbb;">Try adjusting your filters or search keywords.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>