<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: signin.php");
    exit;
}

// Get filter values
$status_filter   = $_GET['status'] ?? '';
$item_type_filter = $_GET['item_type'] ?? '';
$date_from       = $_GET['date_from'] ?? '';
$date_to         = $_GET['date_to'] ?? '';

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];

if ($status_filter !== '') {
    $where .= " AND li.status = :status";
    $params[':status'] = $status_filter;
}
if ($item_type_filter !== '') {
    $where .= " AND li.item_type ILIKE :item_type";
    $params[':item_type'] = "%$item_type_filter%";
}
if ($date_from !== '') {
    $where .= " AND li.date_lost >= :date_from";
    $params[':date_from'] = $date_from;
}
if ($date_to !== '') {
    $where .= " AND li.date_lost <= :date_to";
    $params[':date_to'] = $date_to;
}

// Main query – with claim info (only approved/retrieved claims)
$sql = "SELECT li.*,
               u1.full_name AS posted_by_name,
               c.claim_date,
               u2.full_name AS claimer_name
        FROM lost_items li
        LEFT JOIN users u1 ON li.user_id = u1.user_id
        LEFT JOIN claims c ON li.id = c.item_id AND c.status IN ('approved', 'retrieved')
        LEFT JOIN users u2 ON c.claimant_user_id = u2.user_id
        $where
        ORDER BY li.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$total_items = $pdo->query("SELECT COUNT(*) FROM lost_items")->fetchColumn();
$lost_count  = $pdo->query("SELECT COUNT(*) FROM lost_items WHERE status = 'lost'")->fetchColumn();
$claimed_returned_count = $pdo->query("SELECT COUNT(*) FROM lost_items WHERE status IN ('claimed', 'returned')")->fetchColumn();

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line"></i> Reports & Analytics</h1>
    <p>View and export lost & found item reports</p>
</div>

<!-- Summary Cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-boxes"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $total_items; ?></div>
            <div class="stat-label">Total Items</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-search"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $lost_count; ?></div>
            <div class="stat-label">Lost</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-hand-holding-heart"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $claimed_returned_count; ?></div>
            <div class="stat-label">Claimed / Returned</div>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="filters-bar">
    <form method="GET" style="display:contents;">
        <div class="filter-group">
            <label>Status</label>
            <select name="status">
                <option value="">All Statuses</option>
                <option value="lost" <?php echo $status_filter == 'lost' ? 'selected' : ''; ?>>Lost</option>
                <option value="claimed" <?php echo $status_filter == 'claimed' ? 'selected' : ''; ?>>Claimed</option>
                <option value="returned" <?php echo $status_filter == 'returned' ? 'selected' : ''; ?>>Returned</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Item Type</label>
            <input type="text" name="item_type" value="<?php echo htmlspecialchars($item_type_filter); ?>" placeholder="e.g. Phone, Wallet">
        </div>
        <div class="filter-group">
            <label>Date From</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
        </div>
        <div class="filter-group">
            <label>Date To</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
        </div>
        <div class="filter-actions">
            <button type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
            <a href="reports.php" class="btn-secondary"><i class="fas fa-undo"></i> Clear</a>
        </div>
    </form>
</div>

<!-- Export Buttons -->
<div class="export-bar">
    <button onclick="copyToClipboard()" class="btn btn-info"><i class="fas fa-clipboard"></i> Copy to Clipboard</button>
    <button onclick="window.print()" class="btn btn-success"><i class="fas fa-print"></i> Print</button>
</div>

<!-- Report Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-table-list"></i> Item Report</h3>
        <span><?php echo count($items); ?> records</span>
    </div>
    <?php if (count($items) > 0): ?>
        <div class="table-wrapper">
            <table class="table" id="dataTable">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Description</th>
                        <th>Item Type</th>
                        <th>Date Lost</th>
                        <th>Status</th>
                        <th>Posted By</th>
                        <th>Claimed By</th>
                        <th>Claim Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>#<?php echo (int)$item['id']; ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_type']); ?></td>
                            <td><?php echo $item['date_lost'] ? date('M d, Y', strtotime($item['date_lost'])) : '—'; ?></td>
                            <td>
                                <?php
                                $status = $item['status'];
                                $badge = match($status) {
                                    'lost' => 'badge-lost',
                                    'claimed' => 'badge-claimed',
                                    'returned' => 'badge-returned',
                                    default => ''
                                };
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($item['posted_by_name'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($item['claimer_name'] ?? '—'); ?></td>
                            <td><?php echo $item['claim_date'] ? date('M d, Y', strtotime($item['claim_date'])) : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-chart-simple"></i></div>
            <h4>No data found</h4>
            <p>Try adjusting your filters.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function copyToClipboard() {
    const table = document.getElementById('dataTable');
    if (!table) {
        alert('No data to copy!');
        return;
    }
    let text = 'TIP LOST & FOUND SYSTEM — REPORT\n';
    text += 'Generated: ' + new Date().toLocaleString() + '\n\n';
    text += 'Total Items: <?php echo $total_items; ?>\n';
    text += 'Lost: <?php echo $lost_count; ?>\n';
    text += 'Claimed/Returned: <?php echo $claimed_returned_count; ?>\n\n---\n\n';
    table.querySelectorAll('tr').forEach(row => {
        const cells = Array.from(row.querySelectorAll('th, td')).map(c => c.textContent.trim()).join('\t');
        text += cells + '\n';
    });
    navigator.clipboard.writeText(text)
        .then(() => alert('Report copied to clipboard!'))
        .catch(err => alert('Failed to copy: ' + err));
}
</script>

<?php include 'includes/footer.php'; ?>