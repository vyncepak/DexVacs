<?php
/**
 * ============================================================================
 * VaxCare — Parent Module: Book Hospital Appointment
 * File: parent/book-hospital.php
 * Description: Enables registered parents to select a partner hospital, choose from
 *              only the vaccines currently in stock at that facility, reserve a dose,
 *              and schedule a confirmed vaccination appointment.
 * ============================================================================
 */

// Load database connection & security auth helper
require_once __DIR__ . '/../config/db.php';
// Restrict access strictly to authenticated Parents
require_auth(['Parent']);

// Retrieve current logged-in parent details
$user = current_user();
$user_id = $user['id'];
$user_name = $user['name'];

$page_title = "Book Hospital Appointment";
$header_action = "";

$error_msg = '';
$success_msg = '';

// ----------------------------------------------------------------------------
// Appointment Booking Form Handler
// ----------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['new_booking'])) {
    // Collect & sanitize POST data
    $child_name  = trim($_POST['child_name'] ?? '');
    $vaccine_id  = intval($_POST['vaccine_id'] ?? 0);
    $hospital_id = intval($_POST['hospital_id'] ?? 0);
    $book_date   = !empty($_POST['booking_date']) ? $_POST['booking_date'] : date('Y-m-d');
    
    // Verify required fields
    if (!empty($child_name) && $vaccine_id > 0 && $hospital_id > 0) {
        
        // Validate that appointment date is not in the past
        if ($book_date < date('Y-m-d')) {
            $error_msg = "You cannot book an appointment for a past date. Please pick today or a future date.";
        } else {
        // 1. Verify hospital carries this vaccine AND has available stock (> 0)
        $vchk = $conn->prepare("SELECT stock_quantity FROM hospital_vaccines WHERE hospital_id = ? AND vaccine_id = ?");
        $vchk->bind_param("ii", $hospital_id, $vaccine_id);
        $vchk->execute();
        $stock_res = $vchk->get_result();

        if ($stock_row = $stock_res->fetch_assoc()) {
            if ($stock_row['stock_quantity'] <= 0) {
                $error_msg = "This vaccine is currently out of stock at the selected hospital.";
            } else {
                // 2. Check if the child is already registered under this parent, otherwise create profile
                $cstmt = $conn->prepare("SELECT id FROM children WHERE parent_id = ? AND name = ? LIMIT 1");
                $cstmt->bind_param("is", $user_id, $child_name);
                $cstmt->execute();
                $cres = $cstmt->get_result();

                if ($existing = $cres->fetch_assoc()) {
                    $child_id = $existing['id'];
                } else {
                    $inst = $conn->prepare("INSERT INTO children (name, parent_id, vaccine_id, hospital_id, status) VALUES (?, ?, ?, ?, 'Not vaccinated')");
                    $inst->bind_param("siii", $child_name, $user_id, $vaccine_id, $hospital_id);
                    $inst->execute();
                    $child_id = $inst->insert_id;
                }

                // 3. Create appointment booking with initial status 'Pending'
                $stmt2 = $conn->prepare("INSERT INTO bookings (child_id, hospital_id, vaccine_id, booking_date, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt2->bind_param("iiis", $child_id, $hospital_id, $vaccine_id, $book_date);
                $stmt2->execute();

                // Stock is deducted later by the hospital when they mark the child as Vaccinated.
                // This prevents lost doses if a booking gets cancelled before the vaccination happens.

                $success_msg = "Appointment booked successfully! The hospital will confirm your visit.";
            }
        } else {
            $error_msg = "This hospital does not carry the selected vaccine.";
        }
        } // end date validation else
    } else {
        $error_msg = "Please provide the child's name, choose a hospital, and select an available vaccine.";
    }
}

// ----------------------------------------------------------------------------
// Data Queries for Form & Active Appointments
// ----------------------------------------------------------------------------

// Fetch list of active hospitals
$hospitals = $conn->query("SELECT id, name, location FROM hospitals WHERE status = 'Active' ORDER BY name");

// Map each hospital to its stocked vaccines for instant dynamic JavaScript dropdown
$hosp_vax_map = [];
$hv_res = $conn->query("SELECT hv.hospital_id, hv.vaccine_id, hv.stock_quantity, v.name, v.price 
                        FROM hospital_vaccines hv 
                        JOIN vaccines v ON hv.vaccine_id = v.id 
                        WHERE hv.stock_quantity > 0 
                        ORDER BY v.name");
while ($hv_row = $hv_res->fetch_assoc()) {
    $hosp_vax_map[$hv_row['hospital_id']][] = $hv_row;
}

// Fetch existing registered children for parent autocomplete
$my_children = $conn->query("SELECT DISTINCT name FROM children WHERE parent_id = $user_id ORDER BY name");

// Query current parent's scheduled appointments
$sql = "SELECT b.id, c.name as child_name, v.name as vaccine_name, v.price as vaccine_price,
               h.name as hospital_name, b.booking_date, b.status
        FROM bookings b
        JOIN children c ON b.child_id = c.id
        JOIN vaccines v ON b.vaccine_id = v.id
        JOIN hospitals h ON b.hospital_id = h.id
        WHERE c.parent_id = $user_id
        ORDER BY b.id DESC";
$result = $conn->query($sql);

// Render common header & sidebar navigation
include __DIR__ . '/../includes/header.php';
?>

<!-- Alert Feedback -->
<?php if (!empty($error_msg)): ?>
  <div class="r-alert r-alert-err">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span><?php echo htmlspecialchars($error_msg); ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($success_msg)): ?>
  <div class="r-alert r-alert-ok">
    <i class="fa-solid fa-circle-check"></i>
    <span><?php echo htmlspecialchars($success_msg); ?></span>
  </div>
<?php endif; ?>

<!-- Booking Form Card -->
<div class="r-card" id="bookingCard">
  <div class="r-titlebar">
    <span>Book a Vaccination Date at a Hospital</span>
  </div>
  <div class="r-card-body">
    <form method="POST" action="book-hospital.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: flex-end;">
      <input type="hidden" name="new_booking" value="1">

      <!-- Child Name Field with Autocomplete Datalist -->
      <div class="r-field" style="margin: 0;">
        <label for="b_child">Child Name</label>
        <input type="text" id="b_child" name="child_name" class="r-input-block" placeholder="Enter child's name" list="childList" required>
        <datalist id="childList">
          <?php if ($my_children): ?>
            <?php while ($ch = $my_children->fetch_assoc()): ?>
              <option value="<?php echo htmlspecialchars($ch['name']); ?>">
            <?php endwhile; ?>
          <?php endif; ?>
        </datalist>
      </div>

      <!-- Hospital Selection -->
      <div class="r-field" style="margin: 0;">
        <label for="b_hospital">Select Hospital</label>
        <select id="b_hospital" name="hospital_id" class="r-input-block" onchange="updateHospitalVaccines(this.value)" required>
          <option value="">Choose Hospital...</option>
          <?php if ($hospitals): ?>
            <?php while ($h = $hospitals->fetch_assoc()): ?>
              <option value="<?php echo $h['id']; ?>"><?php echo htmlspecialchars($h['name']); ?> (<?php echo htmlspecialchars($h['location']); ?>)</option>
            <?php endwhile; ?>
          <?php endif; ?>
        </select>
      </div>

      <!-- Live Vaccine Dropdown Filtered by Selected Hospital -->
      <div class="r-field" style="margin: 0;">
        <label for="b_vaccine">Available Vaccines at Hospital</label>
        <select id="b_vaccine" name="vaccine_id" class="r-input-block" required>
          <option value="">Choose hospital first...</option>
        </select>
      </div>

      <!-- Target Appointment Date -->
      <div class="r-field" style="margin: 0;">
        <label for="b_date">Target Appointment Date</label>
        <input type="date" id="b_date" name="booking_date" class="r-input-block" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
      </div>

      <div style="margin: 0;">
        <button type="submit" class="r-btn r-btn-primary r-btn-block">
          <i class="fa-solid fa-check"></i> Book Appointment
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Booking Queue Table -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Your Scheduled Appointments</span>
    <input type="text" placeholder="Search appointments..." class="r-input-block" style="width: 220px; padding: 4px 10px; font-size: 12px;" oninput="filterTable(this.value, 'parentBookingsTable')">
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="parentBookingsTable">
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Child Name</th>
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
                <span class="r-badge r-badge-primary"><?php echo htmlspecialchars($b['vaccine_name']); ?></span>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Rs. <?php echo number_format($b['vaccine_price'], 2); ?></div>
              </td>
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
                  <span class="r-badge r-badge-warn">Pending Approval</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 24px; color: #64748b;">
                You have not booked any appointments yet. Use the form above to book your child's vaccination.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Appointments are confirmed directly with partner hospitals</span>
  </div>
</div>

<!-- Dynamic Hospital Vaccine Stock Synchronisation Script -->
<script>
  // Injected JSON mapping of hospital IDs to available in-stock vaccines
  const hospVaxData = <?php echo json_encode($hosp_vax_map); ?>;

  function updateHospitalVaccines(hospitalId) {
    const vSelect = document.getElementById('b_vaccine');
    vSelect.innerHTML = '<option value="">Select Vaccine...</option>';
    
    // Check if hospital has allocated vaccines with available stock
    if (!hospitalId || !hospVaxData[hospitalId]) {
      vSelect.innerHTML = '<option value="">No vaccines in stock at this hospital</option>';
      return;
    }

    // Populate dropdown with vaccines available at chosen facility
    hospVaxData[hospitalId].forEach(v => {
      const opt = document.createElement('option');
      opt.value = v.vaccine_id;
      opt.textContent = `${v.name} (Stock: ${v.stock_quantity}) — Rs. ${parseFloat(v.price).toFixed(2)}`;
      vSelect.appendChild(opt);
    });
  }
</script>

<?php 
// Render common layout footer & script bindings
include __DIR__ . '/../includes/footer.php'; 
?>
