<?php
require_once __DIR__ . '/../config/db.php';
require_auth(['Parent']);

$user = current_user();
$user_id = $user['id'];
$user_name = $user['name'];

$page_title = "Vaccination History";
$header_action = '<button type="button" class="r-btn r-btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Records</button>';

$from_date = $_GET['from_date'] ?? date('Y-01-01');
$to_date   = $_GET['to_date'] ?? date('Y-12-31');
$status    = $_GET['status'] ?? 'all';

$sql = "SELECT b.id as booking_id, b.booking_date, b.status as booking_status,
               c.id as child_id, c.name as child_name, c.dob, c.status as child_status, c.certificate_sent,
               v.name as vaccine_name, v.price as vaccine_price,
               h.name as hospital_name
        FROM bookings b
        JOIN children c ON b.child_id = c.id
        JOIN vaccines v ON b.vaccine_id = v.id
        JOIN hospitals h ON b.hospital_id = h.id
        WHERE c.parent_id = ? AND b.booking_date BETWEEN ? AND ?";

$params = [$user_id, $from_date, $to_date];
$types  = "iss";

if ($status !== 'all') {
    $sql .= " AND b.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$total_records = $result->num_rows;
$vaccinated_count = 0;
$pending_count = 0;
$rows = [];
while ($row = $result->fetch_assoc()) {
    if ($row['child_status'] === 'Vaccinated' || $row['booking_status'] === 'Completed') {
        $vaccinated_count++;
    } else {
        $pending_count++;
    }
    $rows[] = $row;
}
$completion_rate = ($total_records > 0) ? round(($vaccinated_count / $total_records) * 100) : 0;

include __DIR__ . '/../includes/header.php';
?>

<!-- Filter Card -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Filter History</span>
  </div>
  <div class="r-card-body">
    <form method="GET" action="reports.php" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
      <div class="r-field" style="flex: 1; min-width: 140px; margin: 0;">
        <label for="from_date">From Date</label>
        <input type="date" id="from_date" name="from_date" class="r-input-block" value="<?php echo htmlspecialchars($from_date); ?>" required>
      </div>

      <div class="r-field" style="flex: 1; min-width: 140px; margin: 0;">
        <label for="to_date">To Date</label>
        <input type="date" id="to_date" name="to_date" class="r-input-block" value="<?php echo htmlspecialchars($to_date); ?>" required>
      </div>

      <div class="r-field" style="flex: 1; min-width: 140px; margin: 0;">
        <label for="status">Status</label>
        <select id="status" name="status" class="r-input-block">
          <option value="all" <?php echo ($status === 'all') ? 'selected' : ''; ?>>All</option>
          <option value="Completed" <?php echo ($status === 'Completed') ? 'selected' : ''; ?>>Completed</option>
          <option value="Approved" <?php echo ($status === 'Approved') ? 'selected' : ''; ?>>Approved</option>
          <option value="Pending" <?php echo ($status === 'Pending') ? 'selected' : ''; ?>>Pending</option>
        </select>
      </div>

      <div style="margin: 0; display: flex; gap: 8px; align-items: center;">
        <button type="submit" class="r-btn r-btn-primary" style="padding: 7px 14px;">
          <i class="fa-solid fa-filter"></i> Filter
        </button>
        <a href="reports.php" class="r-link-reset">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Summary Metrics Grid -->
<div class="r-grid-4">
  <div class="r-metric-card">
    <div class="r-metric-head">
      <span class="r-metric-title">Total Records</span>
      <div class="r-metric-icon"><i class="fa-solid fa-list"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $total_records; ?></div>
    <div class="r-metric-foot">
      <span>In date range</span>
    </div>
  </div>

  <div class="r-metric-card accent-success">
    <div class="r-metric-head">
      <span class="r-metric-title">Vaccinated</span>
      <div class="r-metric-icon"><i class="fa-solid fa-check"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $vaccinated_count; ?></div>
    <div class="r-metric-foot">
      <span>Completed doses</span>
    </div>
  </div>

  <div class="r-metric-card accent-warning">
    <div class="r-metric-head">
      <span class="r-metric-title">Upcoming / Pending</span>
      <div class="r-metric-icon"><i class="fa-solid fa-clock"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $pending_count; ?></div>
    <div class="r-metric-foot">
      <span>Scheduled visits</span>
    </div>
  </div>

  <div class="r-metric-card accent-info">
    <div class="r-metric-head">
      <span class="r-metric-title">Completion Rate</span>
      <div class="r-metric-icon"><i class="fa-solid fa-chart-pie"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $completion_rate; ?>%</div>
    <div class="r-metric-foot">
      <span>Protection rate</span>
      <div style="width: 60px;">
        <progress value="<?php echo $completion_rate; ?>" max="100" style="width: 100%;"></progress>
      </div>
    </div>
  </div>
</div>

<!-- Report Table Card -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Vaccination Records (<?php echo htmlspecialchars($from_date); ?> to <?php echo htmlspecialchars($to_date); ?>)</span>
    <span style="font-size: 12px; color: #64748b;"><?php echo $total_records; ?> records</span>
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="parentReportTable">
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Date</th>
            <th>Child Name</th>
            <th>Vaccine</th>
            <th>Hospital</th>
            <th>Status</th>
            <th style="text-align: right;">Certificate</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $r): ?>
            <tr>
              <td><strong style="color: #2563eb;">#BK-<?php echo str_pad($r['booking_id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
              <td><strong><?php echo htmlspecialchars($r['booking_date']); ?></strong></td>
              <td>
                <strong style="color: #0f172a;"><?php echo htmlspecialchars($r['child_name']); ?></strong>
                <div style="font-size: 11px; color: #64748b;">DOB: <?php echo htmlspecialchars($r['dob'] ?: 'Not recorded'); ?></div>
              </td>
              <td><span class="r-badge r-badge-primary"><?php echo htmlspecialchars($r['vaccine_name']); ?></span></td>
              <td><?php echo htmlspecialchars($r['hospital_name']); ?></td>
              <td>
                <?php if ($r['child_status'] === 'Vaccinated' || $r['booking_status'] === 'Completed'): ?>
                  <span class="r-badge r-badge-ok">Vaccinated</span>
                <?php elseif ($r['booking_status'] === 'Cancelled'): ?>
                  <span class="r-badge r-badge-danger">Cancelled</span>
                <?php else: ?>
                  <span class="r-badge r-badge-warn">Scheduled</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <?php if (!empty($r['certificate_sent'])): ?>
                  <button type="button" class="r-btn r-btn-sm" onclick="openCertDialog('<?php echo htmlspecialchars($r['child_name']); ?>', '<?php echo htmlspecialchars($user_name); ?>', '<?php echo htmlspecialchars($r['vaccine_name']); ?>', '<?php echo htmlspecialchars($r['hospital_name']); ?>', 'CH-<?php echo str_pad($r['child_id'], 4, '0', STR_PAD_LEFT); ?>', '<?php echo htmlspecialchars($r['booking_date']); ?>')">
                    <i class="fa-solid fa-certificate"></i> View Certificate
                  </button>
                <?php else: ?>
                  <span style="color: #94a3b8; font-size: 12px;">Waiting for hospital</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 28px; color: #64748b;">
                No vaccination history records found in this date range.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Your child vaccination record history</span>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

