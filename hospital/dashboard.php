<?php
require_once __DIR__ . '/../config/db.php';
require_auth(['Hospital']);

$user = current_user();
$user_id = $user['id'];
$user_name = $user['name'];

$page_title = "Hospital Dashboard";
$header_action = '<a href="update-status.php" class="r-btn r-btn-primary"><i class="fa-solid fa-list-check"></i> Patient Status &amp; Stock</a>';

$hid = get_hospital_id_for_user($conn, $user_id, $user_name);

// Metrics
$scheduled_cnt  = $conn->query("SELECT COUNT(*) as t FROM bookings WHERE hospital_id = $hid AND status = 'Approved'")->fetch_assoc()['t'] ?? 0;
$vaccinated_cnt = $conn->query("SELECT COUNT(*) as t FROM children WHERE hospital_id = $hid AND status = 'Vaccinated'")->fetch_assoc()['t'] ?? 0;
$stock_doses    = $conn->query("SELECT IFNULL(SUM(stock_quantity), 0) as t FROM hospital_vaccines WHERE hospital_id = $hid")->fetch_assoc()['t'] ?? 0;
$pending_cnt    = $conn->query("SELECT COUNT(*) as t FROM bookings WHERE hospital_id = $hid AND status = 'Pending'")->fetch_assoc()['t'] ?? 0;

// Hospital's upcoming appointments
$sql = "SELECT b.id as booking_id, b.booking_date, b.status as booking_status,
               c.id as child_id, c.name as child_name, c.status as child_status, c.certificate_sent,
               u.name as parent_name, u.email as parent_email,
               v.name as vaccine_name
        FROM bookings b
        JOIN children c ON b.child_id = c.id
        JOIN users u ON c.parent_id = u.id
        JOIN vaccines v ON b.vaccine_id = v.id
        WHERE b.hospital_id = $hid
        ORDER BY b.booking_date DESC LIMIT 15";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<!-- 4 Clean Metric Cards -->
<div class="r-grid-4">
  <div class="r-metric-card">
    <div class="r-metric-head">
      <span class="r-metric-title">Scheduled Visits</span>
      <div class="r-metric-icon"><i class="fa-solid fa-calendar-check"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $scheduled_cnt; ?></div>
    <div class="r-metric-foot">
      <span>Approved appointments</span>
    </div>
  </div>

  <div class="r-metric-card accent-info">
    <div class="r-metric-head">
      <span class="r-metric-title">Vaccinated Patients</span>
      <div class="r-metric-icon"><i class="fa-solid fa-check"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $vaccinated_cnt; ?></div>
    <div class="r-metric-foot">
      <span>Doses administered</span>
    </div>
  </div>

  <div class="r-metric-card accent-warning">
    <div class="r-metric-head">
      <span class="r-metric-title">Pending Requests</span>
      <div class="r-metric-icon"><i class="fa-solid fa-clock"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $pending_cnt; ?></div>
    <div class="r-metric-foot">
      <span>Awaiting hospital approval</span>
    </div>
  </div>

  <div class="r-metric-card accent-success">
    <div class="r-metric-head">
      <span class="r-metric-title">Vaccines in Stock</span>
      <div class="r-metric-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $stock_doses; ?></div>
    <div class="r-metric-foot">
      <span>Total doses available</span>
    </div>
  </div>
</div>

<!-- Scheduled Appointments Table -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Scheduled Hospital Appointments</span>
    <input type="text" placeholder="Search patients..." class="r-input-block" style="width: 220px; padding: 4px 10px; font-size: 12px;" oninput="filterTable(this.value, 'hospitalApptTable')">
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="hospitalApptTable">
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Child Name</th>
            <th>Parent Contact</th>
            <th>Vaccine</th>
            <th>Appointment Date</th>
            <th>Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><strong style="color: #2563eb;">#BK-<?php echo str_pad($row['booking_id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
              <td><strong><?php echo htmlspecialchars($row['child_name']); ?></strong></td>
              <td>
                <div><?php echo htmlspecialchars($row['parent_name']); ?></div>
                <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($row['parent_email']); ?></div>
              </td>
              <td><span class="r-badge r-badge-primary"><?php echo htmlspecialchars($row['vaccine_name']); ?></span></td>
              <td><strong><?php echo htmlspecialchars($row['booking_date']); ?></strong></td>
              <td>
                <?php if ($row['child_status'] === 'Vaccinated' || $row['booking_status'] === 'Completed'): ?>
                  <span class="r-badge r-badge-ok">Vaccinated</span>
                <?php elseif ($row['booking_status'] === 'Approved'): ?>
                  <span class="r-badge r-badge-ok">Approved</span>
                <?php elseif ($row['booking_status'] === 'Cancelled'): ?>
                  <span class="r-badge r-badge-danger">Cancelled</span>
                <?php else: ?>
                  <span class="r-badge r-badge-warn">Pending Approval</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <a href="update-status.php" class="r-btn r-btn-sm" style="text-decoration:none;">
                  Manage &rarr;
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 24px; color: #64748b;">
                No appointments currently scheduled for this hospital.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Appointments assigned to your hospital center</span>
    <a href="update-status.php" class="r-btn r-btn-sm r-btn-primary" style="text-decoration:none;">Go to Status &amp; Stock Manager</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

