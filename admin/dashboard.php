<?php
require_once __DIR__ . '/../config/db.php';
require_auth(['Admin']);

$page_title = "Admin Dashboard";
$header_action = "";

// Metrics
$total_parents  = $conn->query("SELECT COUNT(*) as t FROM users WHERE role = 'Parent'")->fetch_assoc()['t'] ?? 0;
$total_children = $conn->query("SELECT COUNT(*) as t FROM children")->fetch_assoc()['t'] ?? 0;
$total_bookings = $conn->query("SELECT COUNT(*) as t FROM bookings WHERE status = 'Approved'")->fetch_assoc()['t'] ?? 0;
$vaccinated_cnt = $conn->query("SELECT COUNT(*) as t FROM children WHERE status = 'Vaccinated'")->fetch_assoc()['t'] ?? 0;
$vax_pct        = ($total_children > 0) ? round(($vaccinated_cnt / $total_children) * 100) : 0;

// Recent Appointments & Schedules
$sql = "SELECT b.id, c.name as child_name, u.name as parent_name, u.email as parent_email,
               v.name as vaccine_name, h.name as hospital_name, b.booking_date, b.status
        FROM bookings b
        JOIN children c ON b.child_id = c.id
        JOIN users u ON c.parent_id = u.id
        JOIN vaccines v ON b.vaccine_id = v.id
        JOIN hospitals h ON b.hospital_id = h.id
        ORDER BY b.booking_date DESC LIMIT 15";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['err'])): ?>
  <div class="r-alert r-alert-err">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span><?php echo htmlspecialchars($_GET['err']); ?></span>
  </div>
<?php endif; ?>

<!-- 4 Clean Metric Cards -->
<div class="r-grid-4">
  <div class="r-metric-card">
    <div class="r-metric-head">
      <span class="r-metric-title">Total Parents</span>
      <div class="r-metric-icon"><i class="fa-solid fa-users"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $total_parents; ?></div>
    <div class="r-metric-foot">
      <span>Registered parent accounts</span>
    </div>
  </div>

  <div class="r-metric-card accent-info">
    <div class="r-metric-head">
      <span class="r-metric-title">Total Children</span>
      <div class="r-metric-icon"><i class="fa-solid fa-children"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $total_children; ?></div>
    <div class="r-metric-foot">
      <span>Children registered</span>
    </div>
  </div>

  <div class="r-metric-card accent-warning">
    <div class="r-metric-head">
      <span class="r-metric-title">Active Appointments</span>
      <div class="r-metric-icon"><i class="fa-solid fa-calendar-days"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $total_bookings; ?></div>
    <div class="r-metric-foot">
      <span>Upcoming visits scheduled</span>
    </div>
  </div>

  <div class="r-metric-card accent-success">
    <div class="r-metric-head">
      <span class="r-metric-title">Fully Vaccinated</span>
      <div class="r-metric-icon"><i class="fa-solid fa-circle-check"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $vaccinated_cnt; ?></div>
    <div class="r-metric-foot">
      <span>Coverage: <?php echo $vax_pct; ?>%</span>
      <div style="width: 60px;">
        <progress value="<?php echo $vax_pct; ?>" max="100" style="width: 100%;"></progress>
      </div>
    </div>
  </div>
</div>

<!-- Vaccination Schedules & Appointments Queue -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Vaccination Schedules &amp; Appointments</span>
    <input type="text" placeholder="Search appointments..." class="r-input-block" style="width: 220px; padding: 4px 10px; font-size: 12px;" oninput="filterTable(this.value, 'schedulesTable')">
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="schedulesTable">
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Child Name</th>
            <th>Parent</th>
            <th>Vaccine</th>
            <th>Hospital</th>
            <th>Date</th>
            <th style="text-align: right;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($b = $result->fetch_assoc()): ?>
            <tr>
              <td><strong style="color: #2563eb;">#BK-<?php echo str_pad($b['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
              <td><strong><?php echo htmlspecialchars($b['child_name']); ?></strong></td>
              <td>
                <div><?php echo htmlspecialchars($b['parent_name']); ?></div>
                <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($b['parent_email']); ?></div>
              </td>
              <td><span class="r-badge r-badge-primary"><?php echo htmlspecialchars($b['vaccine_name']); ?></span></td>
              <td><?php echo htmlspecialchars($b['hospital_name']); ?></td>
              <td><strong><?php echo htmlspecialchars($b['booking_date']); ?></strong></td>
              <td style="text-align: right;">
                <?php if ($b['status'] === 'Approved'): ?>
                  <span class="r-badge r-badge-ok">Approved</span>
                <?php elseif ($b['status'] === 'Completed'): ?>
                  <span class="r-badge r-badge-ok">Completed</span>
                <?php elseif ($b['status'] === 'Cancelled'): ?>
                  <span class="r-badge r-badge-danger">Cancelled</span>
                <?php else: ?>
                  <span class="r-badge r-badge-warn">Pending</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 24px; color: #64748b;">
                No scheduled appointments recorded yet.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Overview of recent vaccination schedules</span>
    <a href="manage-requests.php" class="r-btn r-btn-sm" style="text-decoration:none;">View All Requests &rarr;</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

