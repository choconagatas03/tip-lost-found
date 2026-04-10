<?php
include 'includes/header.php';
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: signin.php");
  exit;
}

$report_type     = $_GET['report_type'] ?? 'all_items';
$status_filter   = $_GET['status'] ?? '';
$date_from       = $_GET['date_from'] ?? '';
$date_to         = $_GET['date_to'] ?? '';
$item_type_filter = $_GET['item_type'] ?? '';

$where = "WHERE 1=1";

if ($report_type === 'lost_items') {
  $where .= " AND lost_items.status = 'lost'";
} elseif ($report_type === 'claimed_items') {
  $where .= " AND lost_items.status IN ('claimed', 'returned')";
}

if ($status_filter) {
  $safe_status = mysqli_real_escape_string($conn, $status_filter);
  $where .= " AND lost_items.status = '$safe_status'";
}
if ($date_from) {
  $safe_from = mysqli_real_escape_string($conn, $date_from);
  $where .= " AND lost_items.date_lost >= '$safe_from'";
}
if ($date_to) {
  $safe_to = mysqli_real_escape_string($conn, $date_to);
  $where .= " AND lost_items.date_lost <= '$safe_to'";
}
if ($item_type_filter) {
  $safe_type = mysqli_real_escape_string($conn, $item_type_filter);
  $where .= " AND lost_items.item_type LIKE '%$safe_type%'";
}

$sql = "SELECT lost_items.*,
        m1.fullname AS posted_by_name,
        m2.fullname AS claimer_name
        FROM lost_items
        LEFT JOIN members m1 ON lost_items.user_id = m1.id
        LEFT JOIN members m2 ON lost_items.claimed_by = m2.id
        $where
        ORDER BY lost_items.created_at DESC";

$result = mysqli_query($conn, $sql);
$items  = [];
if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
  }
}

$count_items   = count($items);
$count_lost    = 0;
$count_claimed = 0;
foreach ($items as $item) {
  if ($item['status'] === 'lost') $count_lost++;
  if (in_array($item['status'], ['claimed', 'returned'])) $count_claimed++;
}
?>

<!-- Filters -->
<div class="form-section" style="margin-bottom:22px;">
  <h3><i class="fas fa-sliders"></i> Filter Report</h3>

  <form method="GET">
    <div class="form-grid">

      <div class="form-group">
        <label>Report Type</label>
        <select name="report_type">
          <option value="all_items" <?php echo $report_type === 'all_items'    ? 'selected' : ''; ?>>All Items</option>
          <option value="lost_items" <?php echo $report_type === 'lost_items'   ? 'selected' : ''; ?>>Lost Items Only</option>
          <option value="claimed_items" <?php echo $report_type === 'claimed_items' ? 'selected' : ''; ?>>Claimed / Returned</option>
        </select>
      </div>

      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="">All Statuses</option>
          <option value="lost" <?php echo $status_filter === 'lost'     ? 'selected' : ''; ?>>Lost</option>
          <option value="claimed" <?php echo $status_filter === 'claimed'  ? 'selected' : ''; ?>>Claimed</option>
          <option value="returned" <?php echo $status_filter === 'returned' ? 'selected' : ''; ?>>Returned</option>
        </select>
      </div>

      <div class="form-group">
        <label>Item Type</label>
        <input type="text" name="item_type" value="<?php echo htmlspecialchars($item_type_filter); ?>" placeholder="e.g. Phone, Wallet">
      </div>

      <div class="form-group">
        <label>Date From</label>
        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
      </div>

      <div class="form-group">
        <label>Date To</label>
        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
      </div>

    </div>

    <div style="display:flex;gap:10px;margin-top:16px;">
      <button type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
      <a href="reports.php" class="btn btn-secondary"><i class="fas fa-xmark"></i> Clear</a>
    </div>
  </form>
</div>

<!-- Summary -->
<div class="summary-box" style="margin-bottom:22px;">
  <div class="summary-item">
    <strong><?php echo $count_items; ?></strong>
    <span>Total Items</span>
  </div>
  <div class="summary-item">
    <strong><?php echo $count_lost; ?></strong>
    <span>Lost</span>
  </div>
  <div class="summary-item">
    <strong><?php echo $count_claimed; ?></strong>
    <span>Claimed / Returned</span>
  </div>
</div>

<!-- Export Actions -->
<div class="export-bar">
  <form action="export_pdf.php" method="POST" target="_blank" style="display:contents;">
    <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
    <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
    <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
    <input type="hidden" name="item_type" value="<?php echo htmlspecialchars($item_type_filter); ?>">
    <button type="submit" class="btn-danger">
      <i class="fas fa-file-pdf"></i> Export PDF
    </button>
  </form>

  <button onclick="copyToClipboard()" class="btn-info">
    <i class="fas fa-clipboard"></i> Copy to Clipboard
  </button>

  <button onclick="window.print()" class="btn-success">
    <i class="fas fa-print"></i> Print
  </button>
</div>

<!-- Report Table -->
<div class="card" id="reportData">
  <div class="card-header">
    <h3><i class="fas fa-table-list" style="color:var(--tip-gold-deep);margin-right:8px;"></i>Report Data</h3>
    <span style="font-size:13px;color:var(--gray);"><?php echo $count_items; ?> records</span>
  </div>

  <?php if ($count_items > 0): ?>
    <div class="table-wrapper" style="border:none;border-radius:0;box-shadow:none;">
      <table id="dataTable">
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
              <td style="font-weight:700;color:var(--gray-light);font-size:12px;">#<?php echo (int)$item['id']; ?></td>
              <td style="max-width:200px;font-size:13px;">
                <?php echo htmlspecialchars(mb_strimwidth($item['description'], 0, 80, '…')); ?>
              </td>
              <td style="font-weight:600;"><?php echo htmlspecialchars($item['item_type']); ?></td>
              <td style="font-size:13px;color:var(--gray);"><?php echo htmlspecialchars($item['date_lost'] ?? '—'); ?></td>
              <td>
                <?php
                $s   = strtolower($item['status']);
                $cls = match ($s) {
                  'lost' => 'badge-lost',
                  'claimed' => 'badge-claimed',
                  'returned' => 'badge-returned',
                  default => ''
                };
                ?>
                <span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars(ucfirst($item['status'])); ?></span>
              </td>
              <td style="font-size:13px;"><?php echo htmlspecialchars($item['posted_by_name'] ?? '—'); ?></td>
              <td style="font-size:13px;"><?php echo htmlspecialchars($item['claimer_name'] ?? '—'); ?></td>
              <td style="font-size:13px;color:var(--gray);"><?php echo htmlspecialchars($item['claim_date'] ?? '—'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fas fa-file-circle-xmark"></i></div>
      <h4>No data found</h4>
      <p>No items match your selected filters. Try adjusting or clearing them.</p>
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
    text += 'Total Items: <?php echo $count_items; ?>\n';
    text += 'Lost: <?php echo $count_lost; ?>\n';
    text += 'Claimed/Returned: <?php echo $count_claimed; ?>\n\n---\n\n';

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