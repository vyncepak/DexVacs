<?php
require_once __DIR__ . '/../config/db.php';
require_auth(['Parent']);

$user = current_user();
$user_id = $user['id'];
$user_name = $user['name'];

$page_title = "Register Child";
$header_action = '<a href="book-hospital.php" class="r-btn r-btn-primary"><i class="fa-solid fa-calendar-plus"></i> Book Appointment</a>';

$msg = '';
$err = '';

// Handle add child profile
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['register_child'])) {
    $child_name = trim($_POST['child_name'] ?? '');
    $dob        = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $gender     = $_POST['gender'] ?? 'Not Specified';
    $hospital_id = intval($_POST['hospital_id'] ?? 1);
    $vaccine_id  = intval($_POST['vaccine_id'] ?? 1);

    if (!empty($child_name)) {
        $stmt = $conn->prepare("INSERT INTO children (name, dob, gender, parent_id, vaccine_id, hospital_id, status) VALUES (?, ?, ?, ?, ?, ?, 'Not vaccinated')");
        $stmt->bind_param("sssiii", $child_name, $dob, $gender, $user_id, $vaccine_id, $hospital_id);
        if ($stmt->execute()) {
            $msg = "Child record for {$child_name} registered successfully!";
        } else {
            $err = "Failed to register child. Please try again.";
        }
    } else {
        $err = "Please enter the child's name.";
    }
}

// Fetch default options
$hospitals = $conn->query("SELECT id, name FROM hospitals WHERE status = 'Active' ORDER BY name");
$vaccines  = $conn->query("SELECT id, name FROM vaccines WHERE status = 'Available' ORDER BY name");

// Fetch children belonging to this parent
$stmt = $conn->prepare("SELECT c.id, c.name as child_name, c.dob, c.gender, c.status,
                               v.name as vaccine_name, h.name as hospital_name
                        FROM children c
                        JOIN vaccines v ON c.vaccine_id = v.id
                        JOIN hospitals h ON c.hospital_id = h.id
                        WHERE c.parent_id = ?
                        ORDER BY c.id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

include __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($msg)): ?>
  <div class="r-alert r-alert-ok">
    <i class="fa-solid fa-circle-check"></i>
    <span><?php echo htmlspecialchars($msg); ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($err)): ?>
  <div class="r-alert r-alert-err">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span><?php echo htmlspecialchars($err); ?></span>
  </div>
<?php endif; ?>

<!-- Registration Form Card -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Register a Child Profile</span>
  </div>
  <div class="r-card-body">
    <form method="POST" action="register-child.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: flex-end;">
      <input type="hidden" name="register_child" value="1">

      <div class="r-field" style="margin: 0;">
        <label for="c_name">Child Full Name</label>
        <input type="text" id="c_name" name="child_name" class="r-input-block" placeholder="e.g. Leo David" required>
      </div>

      <div class="r-field" style="margin: 0;">
        <label for="c_dob">Date of Birth</label>
        <input type="date" id="c_dob" name="dob" class="r-input-block" value="<?php echo date('Y-m-d'); ?>">
      </div>

      <div class="r-field" style="margin: 0;">
        <label for="c_gender">Gender</label>
        <select id="c_gender" name="gender" class="r-input-block">
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div class="r-field" style="margin: 0;">
        <label for="c_hospital">Preferred Hospital</label>
        <select id="c_hospital" name="hospital_id" class="r-input-block" required>
          <?php if ($hospitals): ?>
            <?php while ($h = $hospitals->fetch_assoc()): ?>
              <option value="<?php echo $h['id']; ?>"><?php echo htmlspecialchars($h['name']); ?></option>
            <?php endwhile; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="r-field" style="margin: 0;">
        <label for="c_vaccine">First Vaccine</label>
        <select id="c_vaccine" name="vaccine_id" class="r-input-block" required>
          <?php if ($vaccines): ?>
            <?php while ($v = $vaccines->fetch_assoc()): ?>
              <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['name']); ?></option>
            <?php endwhile; ?>
          <?php endif; ?>
        </select>
      </div>

      <div style="margin: 0;">
        <button type="submit" class="r-btn r-btn-primary r-btn-block">
          <i class="fa-solid fa-user-plus"></i> Save Child Profile
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Children Records Table -->
<div class="r-card">
  <div class="r-titlebar">
    <span>Your Registered Children</span>
    <input type="text" placeholder="Search children..." class="r-input-block" style="width: 220px; padding: 4px 10px; font-size: 12px;" oninput="filterTable(this.value, 'childProfilesTable')">
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="childProfilesTable">
        <thead>
          <tr>
            <th>Child Name</th>
            <th>Date of Birth</th>
            <th>Gender</th>
            <th>Initial Vaccine</th>
            <th>Preferred Hospital</th>
            <th>Status</th>
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
              <td><?php echo htmlspecialchars($c['dob'] ?: 'Not recorded'); ?></td>
              <td><?php echo htmlspecialchars($c['gender']); ?></td>
              <td><span class="r-badge r-badge-primary"><?php echo htmlspecialchars($c['vaccine_name']); ?></span></td>
              <td><?php echo htmlspecialchars($c['hospital_name']); ?></td>
              <td>
                <?php if ($c['status'] === 'Vaccinated'): ?>
                  <span class="r-badge r-badge-ok">Vaccinated</span>
                <?php else: ?>
                  <span class="r-badge r-badge-warn">Not vaccinated</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 24px; color: #64748b;">
                No children registered yet. Fill the form above to add your first child.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Manage profiles of children linked to your family account</span>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

