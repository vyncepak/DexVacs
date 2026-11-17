<?php
/**
 * ============================================================================
 * VaxCare — Hospital Module: Update Patient Status & Clinic Stock
 * File: hospital/update-status.php
 * Description: Allows hospital staff to mark patient appointments as Vaccinated
 *              or Unvaccinated, issue official immunization certificates, and
 *              update available clinic vaccine stock quantities.
 * ============================================================================
 */

// Load central database connection and authentication engine
require_once __DIR__ . '/../config/db.php';
// Restrict access strictly to authenticated Hospital users
require_auth(['Hospital']);

// Retrieve current logged-in hospital user credentials
$user = current_user();
$user_id = $user['id'];
$user_name = $user['name'];

$page_title = "Update Status & Stock";
$header_action = "";

// Resolve the active hospital ID for this user session
$hid = get_hospital_id_for_user($conn, $user_id, $user_name);

// ----------------------------------------------------------------------------
// Action Handlers
// ----------------------------------------------------------------------------

// 1. Issue Certificate Action
// Hospital validates vaccination and marks certificate as sent/issued for the parent
if (isset($_POST['send_cert'])) {
    $child_id = intval($_POST['child_id']);
    $stmt = $conn->prepare("UPDATE children SET certificate_sent = 1 WHERE id = ? AND hospital_id = ?");
    $stmt->bind_param("ii", $child_id, $hid);
    $stmt->execute();
    header("Location: update-status.php?msg=cert_issued");
    exit;
}

// 2. Mark as Vaccinated Action
// Updates child status to 'Vaccinated', booking to 'Completed', and deducts 1 dose from stock
if (isset($_POST['mark_vaccinated'])) {
    $child_id = intval($_POST['child_id']);
    $stmt = $conn->prepare("UPDATE children SET status = 'Vaccinated' WHERE id = ? AND hospital_id = ?");
    $stmt->bind_param("ii", $child_id, $hid);
    $stmt->execute();

    $bstmt = $conn->prepare("UPDATE bookings SET status = 'Completed' WHERE child_id = ? AND hospital_id = ?");
    $bstmt->bind_param("ii", $child_id, $hid);
    $bstmt->execute();

    // Deduct 1 dose from hospital stock for the vaccine assigned to this child
    $vax_stmt = $conn->prepare("SELECT vaccine_id FROM children WHERE id = ?");
    $vax_stmt->bind_param("i", $child_id);
    $vax_stmt->execute();
    $vax_row = $vax_stmt->get_result()->fetch_assoc();
    if ($vax_row) {
        $deduct = $conn->prepare("UPDATE hospital_vaccines SET stock_quantity = GREATEST(stock_quantity - 1, 0) WHERE hospital_id = ? AND vaccine_id = ?");
        $deduct->bind_param("ii", $hid, $vax_row['vaccine_id']);
        $deduct->execute();
    }

    header("Location: update-status.php?msg=vaccinated");
    exit;
}

// 3. Mark as Not Vaccinated Action (Reset)
// Reverts status back to 'Not vaccinated' and restores 1 dose to hospital stock
if (isset($_POST['mark_not_vaccinated'])) {
    $child_id = intval($_POST['child_id']);

    // Restore 1 dose to hospital stock before resetting status
    $vax_stmt = $conn->prepare("SELECT vaccine_id FROM children WHERE id = ?");
    $vax_stmt->bind_param("i", $child_id);
    $vax_stmt->execute();
    $vax_row = $vax_stmt->get_result()->fetch_assoc();
    if ($vax_row) {
        $restore = $conn->prepare("UPDATE hospital_vaccines SET stock_quantity = stock_quantity + 1 WHERE hospital_id = ? AND vaccine_id = ?");
        $restore->bind_param("ii", $hid, $vax_row['vaccine_id']);
        $restore->execute();
    }

    // Reset child status and revoke certificate
    $stmt = $conn->prepare("UPDATE children SET status = 'Not vaccinated', certificate_sent = 0 WHERE id = ? AND hospital_id = ?");
    $stmt->bind_param("ii", $child_id, $hid);
    $stmt->execute();

    $bstmt = $conn->prepare("UPDATE bookings SET status = 'Approved' WHERE child_id = ? AND hospital_id = ?");
    $bstmt->bind_param("ii", $child_id, $hid);
    $bstmt->execute();

    header("Location: update-status.php?msg=unvaccinated");
    exit;
}

// 4. Update Booking Request Status (Approve or Cancel)
if (isset($_POST['update_booking_status'])) {
    $bid = intval($_POST['booking_id']);
    $new_status = $_POST['new_status'];
    if (in_array($new_status, ['Approved', 'Cancelled'], true)) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND hospital_id = ?");
        $stmt->bind_param("sii", $new_status, $bid, $hid);
        $stmt->execute();
        header("Location: update-status.php?msg=booking_updated");
        exit;
    }
}

// 5. Update Hospital Vaccine Inventory Stock
// Allows clinic staff to directly increment or correct available dose counts
if (isset($_POST['update_stock'])) {
    $vid = intval($_POST['vaccine_id']);
    $new_qty = max(0, intval($_POST['stock_qty']));
    $ustmt = $conn->prepare("UPDATE hospital_vaccines SET stock_quantity = ? WHERE hospital_id = ? AND vaccine_id = ?");
    $ustmt->bind_param("iii", $new_qty, $hid, $vid);
    $ustmt->execute();
    header("Location: update-status.php?msg=stock_updated");
    exit;
}

// ----------------------------------------------------------------------------
// Data Queries
// ----------------------------------------------------------------------------

// Query children and bookings assigned specifically to this hospital
$sql = "SELECT c.id as child_id, c.name as child_name, u.name as parent_name, u.email as parent_email, 
               v.name as vaccine_name, c.status as child_status, c.certificate_sent,
               b.id as booking_id, b.booking_date, b.status as booking_status
        FROM children c
        JOIN users u ON c.parent_id = u.id
        JOIN vaccines v ON c.vaccine_id = v.id
        LEFT JOIN bookings b ON b.child_id = c.id
        WHERE c.hospital_id = $hid
        ORDER BY c.id DESC";
$result = $conn->query($sql);

// Query only vaccines allocated to this hospital by the central health authority
$stmt = $conn->prepare("SELECT v.id, v.name, v.description, v.price, hv.stock_quantity,
                        (CASE WHEN hv.stock_quantity > 0 THEN 'In Stock' ELSE 'Out of Stock' END) as status
                        FROM hospital_vaccines hv
                        JOIN vaccines v ON hv.vaccine_id = v.id
                        WHERE hv.hospital_id = ?
                        ORDER BY v.name ASC");
$stmt->bind_param("i", $hid);
$stmt->execute();
$vax_result = $stmt->get_result();

// Render shared layout header & sidebar
include __DIR__ . '/../includes/header.php';
?>

<!-- Alert Feedback Messages -->
<?php if (isset($_GET['msg'])): ?>
  <?php if ($_GET['msg'] === 'vaccinated'): ?>
    <div class="r-alert r-alert-ok">
      <i class="fa-solid fa-circle-check"></i>
      <span>Patient marked as Vaccinated.</span>
    </div>
  <?php elseif ($_GET['msg'] === 'unvaccinated'): ?>
    <div class="r-alert r-alert-ok">
      <i class="fa-solid fa-circle-check"></i>
      <span>Patient status reset to Not Vaccinated.</span>
    </div>
  <?php elseif ($_GET['msg'] === 'cert_issued'): ?>
    <div class="r-alert r-alert-ok">
      <i class="fa-solid fa-circle-check"></i>
      <span>Vaccination certificate issued and sent to the parent record.</span>
    </div>
  <?php elseif ($_GET['msg'] === 'booking_updated'): ?>
    <div class="r-alert r-alert-ok">
      <i class="fa-solid fa-circle-check"></i>
      <span>Appointment status updated.</span>
    </div>
  <?php elseif ($_GET['msg'] === 'stock_updated'): ?>
    <div class="r-alert r-alert-ok">
      <i class="fa-solid fa-circle-check"></i>
      <span>Vaccine stock quantity updated.</span>
    </div>
  <?php endif; ?>
<?php endif; ?>

<!-- 1. Patient Vaccination Status Section -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Patient Vaccination Status &amp; Certification</span>
    <input type="text" placeholder="Search patients..." class="r-input-block" style="width: 220px; padding: 4px 10px; font-size: 12px;" oninput="filterTable(this.value, 'patientStatusTable')">
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="patientStatusTable">
        <thead>
          <tr>
            <th>Child Name</th>
            <th>Parent</th>
            <th>Vaccine</th>
            <th>Date</th>
            <th>Vaccination Status</th>
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
                <div style="font-size: 11px; color: #64748b;">ID: #CH-<?php echo str_pad($c['child_id'], 4, '0', STR_PAD_LEFT); ?></div>
              </td>
              <td>
                <div><?php echo htmlspecialchars($c['parent_name']); ?></div>
                <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($c['parent_email']); ?></div>
              </td>
              <td><span class="r-badge r-badge-primary"><?php echo htmlspecialchars($c['vaccine_name']); ?></span></td>
              <td><?php echo htmlspecialchars($c['booking_date'] ?: 'Not scheduled'); ?></td>
              <td>
                <?php if ($c['child_status'] === 'Vaccinated'): ?>
                  <span class="r-badge r-badge-ok">Vaccinated</span>
                <?php else: ?>
                  <span class="r-badge r-badge-warn">Not vaccinated</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['child_status'] === 'Vaccinated'): ?>
                  <?php if (!empty($c['certificate_sent'])): ?>
                    <span class="r-badge r-badge-ok">Issued</span>
                  <?php else: ?>
                    <form method="POST" style="margin: 0; display: inline;">
                      <input type="hidden" name="child_id" value="<?php echo $c['child_id']; ?>">
                      <button type="submit" name="send_cert" class="r-btn r-btn-primary r-btn-sm" title="Issue certificate to parent">
                        <i class="fa-solid fa-certificate"></i> Issue
                      </button>
                    </form>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color: #94a3b8; font-size: 12px;">Pending dose</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 4px; justify-content: flex-end;">
                  <?php if ($c['child_status'] !== 'Vaccinated'): ?>
                    <form method="POST" style="margin: 0; display: inline;">
                      <input type="hidden" name="child_id" value="<?php echo $c['child_id']; ?>">
                      <button type="submit" name="mark_vaccinated" class="r-btn r-btn-sm" onclick="return confirm('Mark <?php echo htmlspecialchars($c['child_name']); ?> as Vaccinated?');">
                        <i class="fa-solid fa-check"></i> Mark Vaccinated
                      </button>
                    </form>
                  <?php else: ?>
                    <form method="POST" style="margin: 0; display: inline;">
                      <input type="hidden" name="child_id" value="<?php echo $c['child_id']; ?>">
                      <button type="submit" name="mark_not_vaccinated" class="r-btn r-btn-sm" onclick="return confirm('Reset <?php echo htmlspecialchars($c['child_name']); ?> status to Not Vaccinated?');">
                        Reset to Unvaccinated
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if (!empty($c['booking_id']) && $c['booking_status'] === 'Pending'): ?>
                    <form method="POST" style="margin: 0; display: inline;">
                      <input type="hidden" name="booking_id" value="<?php echo $c['booking_id']; ?>">
                      <input type="hidden" name="new_status" value="Approved">
                      <button type="submit" name="update_booking_status" class="r-btn r-btn-primary r-btn-sm">
                        Approve
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 24px; color: #64748b;">
                No patient records found for this hospital.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Manage patient status and issue vaccination certificates</span>
  </div>
</div>

<!-- 2. Hospital Allocated Vaccines Stock Section -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Your Hospital's Allocated Vaccine Stock</span>
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="stockTable">
        <thead>
          <tr>
            <th>Vaccine Name</th>
            <th>What it protects against</th>
            <th>Price (Rs)</th>
            <th>Doses Available</th>
            <th style="text-align: right;">Update Stock Doses</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($vax_result && $vax_result->num_rows > 0): ?>
            <?php while ($v = $vax_result->fetch_assoc()): ?>
            <tr>
              <td>
                <strong style="color: #0f172a;"><?php echo htmlspecialchars($v['name']); ?></strong>
                <div style="font-size: 11px; color: #64748b;">ID: #VAX-<?php echo str_pad($v['id'], 3, '0', STR_PAD_LEFT); ?></div>
              </td>
              <td><?php echo htmlspecialchars($v['description'] ?: 'Standard pediatric dose'); ?></td>
              <td>Rs. <?php echo number_format($v['price'], 2); ?></td>
              <td>
                <span class="r-badge <?php echo ($v['stock_quantity'] > 0) ? 'r-badge-ok' : 'r-badge-danger'; ?>">
                  <?php echo (int)$v['stock_quantity']; ?> Doses
                </span>
              </td>
              <td style="text-align: right;">
                <form method="POST" style="display: inline-flex; gap: 4px; justify-content: flex-end; margin: 0;">
                  <input type="hidden" name="vaccine_id" value="<?php echo $v['id']; ?>">
                  <input type="number" name="stock_qty" value="<?php echo (int)$v['stock_quantity']; ?>" min="0" class="r-input-block" style="width: 70px; padding: 3px 6px; font-size: 12px;">
                  <button type="submit" name="update_stock" class="r-btn r-btn-sm">
                    <i class="fa-solid fa-check"></i> Save
                  </button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 24px; color: #64748b;">
                No vaccines currently allocated to your hospital. Contact the system administrator.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Only vaccines allocated to your hospital by the administrator are shown here</span>
  </div>
</div>

<?php 
// Render shared layout footer & script tags
include __DIR__ . '/../includes/footer.php'; 
?>
