<?php
require_once __DIR__ . '/../config/db.php';
require_auth(['Admin']);

$page_title = "Manage Vaccines";
$header_action = '<a href="#addVaccineForm" class="r-btn r-btn-primary"><i class="fa-solid fa-plus"></i> Add New Vaccine</a>';

// Add new vaccine
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['add_vaccine'])) {
    $vname = trim($_POST['vaccine_name']);
    $price = floatval($_POST['price']);
    $desc  = trim($_POST['description'] ?? '');
    if (!empty($vname) && $price > 0) {
        $stmt = $conn->prepare("INSERT INTO vaccines (name, description, price, status) VALUES (?, ?, ?, 'Available')");
        $stmt->bind_param("ssd", $vname, $desc, $price);
        $stmt->execute();
        header("Location: manage-vaccines.php?msg=added");
        exit;
    }
}

// Update price
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['update_price'])) {
    $vid = intval($_POST['vaccine_id']);
    $price = floatval($_POST['price']);
    $stmt = $conn->prepare("UPDATE vaccines SET price = ? WHERE id = ?");
    $stmt->bind_param("di", $price, $vid);
    $stmt->execute();
    header("Location: manage-vaccines.php?msg=updated");
    exit;
}

// Toggle status
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['toggle_status'])) {
    $vid = intval($_POST['vaccine_id']);
    $curr = $_POST['current_status'];
    $new_status = ($curr === 'Available') ? 'Unavailable' : 'Available';
    $stmt = $conn->prepare("UPDATE vaccines SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $vid);
    $stmt->execute();
    header("Location: manage-vaccines.php?msg=toggled");
    exit;
}

// Global list
$sql = "SELECT v.id, v.name, v.description, v.price, v.status, 
               (SELECT COUNT(*) FROM bookings WHERE vaccine_id = v.id AND status = 'Completed') as administered_count
        FROM vaccines v
        ORDER BY v.id ASC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
  <?php if ($_GET['msg'] === 'added'): ?>
    <div class="r-alert r-alert-ok">
      <i class="fa-solid fa-circle-check"></i>
      <span>New vaccine added successfully.</span>
    </div>
  <?php elseif ($_GET['msg'] === 'updated'): ?>
    <div class="r-alert r-alert-ok">
      <i class="fa-solid fa-circle-check"></i>
      <span>Vaccine price updated.</span>
    </div>
  <?php elseif ($_GET['msg'] === 'toggled'): ?>
    <div class="r-alert r-alert-ok">
      <i class="fa-solid fa-circle-check"></i>
      <span>Vaccine status updated.</span>
    </div>
  <?php endif; ?>
<?php endif; ?>
<html data-oldweb="saas2020">

<!-- Add Vaccine Form -->
<div class="r-card" id="addVaccineForm">
  <div class="r-titlebar">
    <span>Add a New Vaccine</span>
  </div>
  <div class="r-card-body">
    <form method="POST" action="manage-vaccines.php" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
      <div class="r-field" style="flex: 2; min-width: 200px; margin: 0;">
        <label for="vaccine_name">Vaccine Name</label>
        <input type="text" id="vaccine_name" name="vaccine_name" class="r-input-block" placeholder="e.g. Hepatitis B" required>
      </div>
      <div class="r-field" style="flex: 3; min-width: 220px; margin: 0;">
        <label for="description">What it protects against</label>
        <input type="text" id="description" name="description" class="r-input-block" placeholder="e.g. Protects against Hepatitis B virus">
      </div>
      <div class="r-field" style="flex: 1; min-width: 140px; margin: 0;">
        <label for="price">Price (Rs)</label>
        <input type="number" id="price" name="price" class="r-input-block" placeholder="e.g. 1500" min="0" step="0.01" required>
      </div>
      <div style="margin: 0;">
        <button type="submit" name="add_vaccine" class="r-btn r-btn-primary" style="padding: 8px 16px;">
          <i class="fa-solid fa-plus"></i> Add Vaccine
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Vaccine Catalog Table Card -->
<div class="r-card">
  <div class="r-titlebar">
    <span>All Vaccines</span>
    <input type="text" placeholder="Filter vaccines..." class="r-input-block" style="width: 200px; padding: 4px 10px; font-size: 12px;" oninput="filterTable(this.value, 'vaccinesTable')">
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="vaccinesTable">
        <thead>
          <tr>
            <th>Vaccine Name</th>
            <th>Description</th>
            <th>Price (Rs)</th>
            <th>Doses Given</th>
            <th>Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($v = $result->fetch_assoc()): ?>
            <tr>
              <td>
                <strong style="color: #0f172a;"><?php echo htmlspecialchars($v['name']); ?></strong>
                <div style="font-size: 11px; color: #64748b;">ID: #VAX-<?php echo str_pad($v['id'], 3, '0', STR_PAD_LEFT); ?></div>
              </td>
              <td><?php echo htmlspecialchars($v['description'] ?: 'Standard child dose'); ?></td>
              <td>Rs. <?php echo number_format($v['price'], 2); ?></td>
              <td><span class="r-badge r-badge-primary"><?php echo (int)($v['administered_count'] ?? 0); ?> Doses</span></td>
              <td>
                <form method="POST" style="margin: 0; display: inline;">
                  <input type="hidden" name="vaccine_id" value="<?php echo $v['id']; ?>">
                  <input type="hidden" name="current_status" value="<?php echo $v['status']; ?>">
                  <button type="submit" name="toggle_status" class="r-badge <?php echo ($v['status'] === 'Available') ? 'r-badge-ok' : 'r-badge-danger'; ?>" style="cursor: pointer; border: none;">
                    <?php echo htmlspecialchars($v['status']); ?>
                  </button>
                </form>
              </td>
              <td style="text-align: right;">
                <form method="POST" style="display: inline-flex; gap: 4px; justify-content: flex-end; margin: 0;">
                  <input type="hidden" name="vaccine_id" value="<?php echo $v['id']; ?>">
                  <input type="number" name="price" value="<?php echo htmlspecialchars($v['price']); ?>" min="0" step="0.01" class="r-input-block" style="width: 80px; padding: 3px 6px; font-size: 12px;">
                  <button type="submit" name="update_price" class="r-btn r-btn-sm">
                    <i class="fa-solid fa-check"></i> Save Price
                  </button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 24px; color: #64748b;">
                No vaccines found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Complete list of vaccines in the system</span>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

