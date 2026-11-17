<?php
require_once __DIR__ . '/../config/db.php';
require_auth(['Admin']);

$page_title = "Manage Hospitals";
$header_action = '';

// Toggle status
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['toggle_hospital'])) {
    $hid  = intval($_POST['hospital_id']);
    $curr = $_POST['current_status'];
    $new_status = ($curr === 'Active') ? 'Inactive' : 'Active';
    $stmt = $conn->prepare("UPDATE hospitals SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $hid);
    $stmt->execute();
    header("Location: manage-hospitals.php?msg=toggled");
    exit;
}

$sql = "SELECT h.id, h.name, h.location, h.phone, h.status,
               (SELECT COUNT(*) FROM bookings b WHERE b.hospital_id = h.id AND b.status = 'Completed') as assigned_vaccinations,
               (SELECT COUNT(*) FROM hospital_vaccines hv WHERE hv.hospital_id = h.id) as allocated_vaccines
        FROM hospitals h
        ORDER BY h.id ASC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'toggled'): ?>
  <div class="r-alert r-alert-ok">
    <i class="fa-solid fa-circle-check"></i>
    <span>Hospital status updated.</span>
  </div>
<?php endif; ?>

<div class="r-alert r-alert-ok" style="font-size: 13px; margin-bottom: 20px;">
  <i class="fa-solid fa-circle-info"></i>
  <span><strong>Note:</strong> Hospitals register their own accounts when signing up. As an admin, you can set a hospital to Active or Inactive below.</span>
</div>

<div class="r-card">
  <div class="r-titlebar">
    <span>Registered Hospitals</span>
    <input type="text" placeholder="Search hospitals..." class="r-input-block" style="width: 220px; padding: 4px 10px; font-size: 12px;" oninput="filterTable(this.value, 'hospitalsTable')">
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="hospitalsTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Hospital Name</th>
            <th>Location</th>
            <th>Phone</th>
            <th>Vaccines Stocked</th>
            <th>Children Served</th>
            <th>Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($h = $result->fetch_assoc()): ?>
            <tr>
              <td><strong style="color: #2563eb;">#H-<?php echo str_pad($h['id'], 3, '0', STR_PAD_LEFT); ?></strong></td>
              <td><strong style="color: #0f172a;"><?php echo htmlspecialchars($h['name']); ?></strong></td>
              <td><?php echo htmlspecialchars($h['location']); ?></td>
              <td><?php echo htmlspecialchars($h['phone'] ?: 'None listed'); ?></td>
              <td><span class="r-badge r-badge-primary"><?php echo $h['allocated_vaccines']; ?> Vaccines</span></td>
              <td><span class="r-badge"><?php echo $h['assigned_vaccinations']; ?> Children</span></td>
              <td>
                <?php if ($h['status'] === 'Active'): ?>
                  <span class="r-badge r-badge-ok">Active</span>
                <?php else: ?>
                  <span class="r-badge r-badge-danger">Inactive</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <form method="POST" style="margin: 0; display: inline;">
                  <input type="hidden" name="hospital_id" value="<?php echo $h['id']; ?>">
                  <input type="hidden" name="current_status" value="<?php echo $h['status']; ?>">
                  <button type="submit" name="toggle_hospital" class="r-btn r-btn-sm">
                    <i class="fa-solid fa-power-off"></i> Set to <?php echo ($h['status'] === 'Active') ? 'Inactive' : 'Active'; ?>
                  </button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 24px; color: #64748b;">
                No hospitals found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Hospitals registered in the DexVacs system</span>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

