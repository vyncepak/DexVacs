<?php
require_once __DIR__ . '/../config/db.php';
require_auth(['Parent']);

$user = current_user();
$user_id = $user['id'];
$user_name = $user['name'];

$page_title = "Parent Dashboard";
$header_action = '<a href="book-hospital.php" class="r-btn r-btn-primary"><i class="fa-solid fa-plus"></i> Book Appointment</a>';

// Metrics
$total_children = $conn->query("SELECT COUNT(*) as t FROM children WHERE parent_id = $user_id")->fetch_assoc()['t'] ?? 0;
$vaccinated_cnt = $conn->query("SELECT COUNT(*) as t FROM children WHERE parent_id = $user_id AND status = 'Vaccinated'")->fetch_assoc()['t'] ?? 0;
$upcoming_cnt   = $conn->query("SELECT COUNT(*) as t FROM bookings b JOIN children c ON b.child_id = c.id WHERE c.parent_id = $user_id AND b.status = 'Approved'")->fetch_assoc()['t'] ?? 0;
$vax_pct        = ($total_children > 0) ? round(($vaccinated_cnt / $total_children) * 100) : 0;

// Children list
$sql = "SELECT c.id, c.name as child_name, c.dob, v.name as vaccine_name, h.name as hospital_name, 
               c.status, c.certificate_sent, b.booking_date, b.status as booking_status
        FROM children c
        JOIN vaccines v ON c.vaccine_id = v.id
        JOIN hospitals h ON c.hospital_id = h.id
        LEFT JOIN bookings b ON b.child_id = c.id
        WHERE c.parent_id = $user_id
        ORDER BY c.id DESC";
$result = $conn->query($sql);

// Upcoming appointment alert check
$upcoming_stmt = $conn->prepare("SELECT c.name as child_name, v.name as vaccine_name, h.name as hospital_name, b.booking_date 
                                 FROM bookings b 
                                 JOIN children c ON b.child_id = c.id 
                                 JOIN vaccines v ON b.vaccine_id = v.id 
                                 JOIN hospitals h ON b.hospital_id = h.id 
                                 WHERE c.parent_id = ? AND b.status = 'Approved' AND b.booking_date >= CURDATE() 
                                 ORDER BY b.booking_date ASC LIMIT 1");
$upcoming_stmt->bind_param("i", $user_id);
$upcoming_stmt->execute();
$next_visit = $upcoming_stmt->get_result()->fetch_assoc();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($next_visit): ?>
  <div class="r-alert r-alert-ok">
    <i class="fa-solid fa-bell"></i>
    <span><strong>Upcoming Visit Reminder:</strong> Appointment for <strong><?php echo htmlspecialchars($next_visit['child_name']); ?></strong> (<?php echo htmlspecialchars($next_visit['vaccine_name']); ?>) at <strong><?php echo htmlspecialchars($next_visit['hospital_name']); ?></strong> on <strong><?php echo htmlspecialchars($next_visit['booking_date']); ?></strong>.</span>
  </div>
<?php endif; ?>

<!-- 4 Clean Metric Cards -->
<div class="r-grid-4">
  <div class="r-metric-card">
    <div class="r-metric-head">
      <span class="r-metric-title">My Children</span>
      <div class="r-metric-icon"><i class="fa-solid fa-children"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $total_children; ?></div>
    <div class="r-metric-foot">
      <span>Registered in system</span>
    </div>
  </div>

  <div class="r-metric-card accent-info">
    <div class="r-metric-head">
      <span class="r-metric-title">Vaccinated</span>
      <div class="r-metric-icon"><i class="fa-solid fa-check"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $vaccinated_cnt; ?></div>
    <div class="r-metric-foot">
      <span>Doses completed</span>
    </div>
  </div>

  <div class="r-metric-card accent-warning">
    <div class="r-metric-head">
      <span class="r-metric-title">Upcoming Visits</span>
      <div class="r-metric-icon"><i class="fa-solid fa-calendar-days"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $upcoming_cnt; ?></div>
    <div class="r-metric-foot">
      <span>Approved visits</span>
    </div>
  </div>

  <div class="r-metric-card accent-success">
    <div class="r-metric-head">
      <span class="r-metric-title">Family Progress</span>
      <div class="r-metric-icon"><i class="fa-solid fa-chart-pie"></i></div>
    </div>
    <div class="r-metric-value"><?php echo $vax_pct; ?>%</div>
    <div class="r-metric-foot">
      <span>Vaccination rate</span>
      <div style="width: 60px;">
        <progress value="<?php echo $vax_pct; ?>" max="100" style="width: 100%;"></progress>
      </div>
    </div>
  </div>
</div>

<!-- Children & Vaccination Schedule -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Your Children's Vaccination Status</span>
    <input type="text" placeholder="Search by name or vaccine..." class="r-input-block" style="width: 220px; padding: 4px 10px; font-size: 12px;" oninput="filterTable(this.value, 'parentTable')">
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="parentTable">
        <thead>
          <tr>
            <th>Child Name</th>
            <th>Vaccine</th>
            <th>Hospital</th>
            <th>Appointment Date</th>
            <th>Status</th>
            <th>Certificate</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($c = $result->fetch_assoc()): ?>
            <tr>
              <td>
                <strong style="color: #0f172a;"><?php echo htmlspecialchars($c['child_name']); ?></strong>
                <div style="font-size: 11px; color: #64748b;">ID: #CH-<?php echo str_pad($c['id'], 4, '0', STR_PAD_LEFT); ?></div>
              </td>
              <td><span class="r-badge r-badge-primary"><?php echo htmlspecialchars($c['vaccine_name']); ?></span></td>
              <td><?php echo htmlspecialchars($c['hospital_name']); ?></td>
              <td>
                <?php if (!empty($c['booking_date'])): ?>
                  <strong><?php echo htmlspecialchars($c['booking_date']); ?></strong>
                  <div style="font-size: 11px; color: #64748b;">
                    <?php if ($c['booking_status'] === 'Approved'): ?>
                      Approved visit
                    <?php elseif ($c['booking_status'] === 'Completed'): ?>
                      Done
                    <?php elseif ($c['booking_status'] === 'Cancelled'): ?>
                      Cancelled
                    <?php else: ?>
                      Waiting approval
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span style="color: #94a3b8; font-size: 12px;">Not scheduled</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['status'] === 'Vaccinated'): ?>
                  <span class="r-badge r-badge-ok">Vaccinated</span>
                <?php else: ?>
                  <span class="r-badge r-badge-warn">Not vaccinated</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['status'] === 'Vaccinated'): ?>
                  <?php if (!empty($c['certificate_sent'])): ?>
                    <span class="r-badge r-badge-ok">Issued</span>
                  <?php else: ?>
                    <span style="color: #94a3b8; font-size: 12px;">Waiting for hospital</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color: #94a3b8; font-size: 12px;">After vaccination</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <?php if ($c['status'] === 'Vaccinated' && !empty($c['certificate_sent'])): ?>
                  <button type="button" class="r-btn r-btn-sm" onclick="openCertDialog('<?php echo htmlspecialchars($c['child_name']); ?>', '<?php echo htmlspecialchars($user_name); ?>', '<?php echo htmlspecialchars($c['vaccine_name']); ?>', '<?php echo htmlspecialchars($c['hospital_name']); ?>', 'CH-<?php echo str_pad($c['id'], 4, '0', STR_PAD_LEFT); ?>', '<?php echo htmlspecialchars($c['booking_date'] ?? date('Y-m-d')); ?>')">
                    <i class="fa-solid fa-certificate"></i> View Certificate
                  </button>
                <?php else: ?>
                  <span style="color: #94a3b8; font-size: 11px;">No action needed</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 28px; color: #64748b;">
                No children registered yet. <a href="register-child.php" style="color: #2563eb; font-weight: 500;">Register a child</a> or <a href="book-hospital.php" style="color: #2563eb; font-weight: 500;">book an appointment</a> to get started.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Your family's immunization records and status</span>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

