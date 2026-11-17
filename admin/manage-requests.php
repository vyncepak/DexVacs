<?php
require_once __DIR__ . '/../config/db.php';
require_auth(['Admin']);

$page_title = "Manage Requests";
$header_action = "";

// Update Status Action (Approve, Complete, Cancel)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['update_status'])) {
    $bid = intval($_POST['booking_id']);
    $new_status = $_POST['new_status'];

    if (in_array($new_status, ['Approved', 'Completed', 'Cancelled'], true)) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $bid);
        $stmt->execute();

        if ($new_status === 'Completed') {
            $cstmt = $conn->prepare("UPDATE children c JOIN bookings b ON b.child_id = c.id SET c.status = 'Vaccinated' WHERE b.id = ?");
            $cstmt->bind_param("i", $bid);
            $cstmt->execute();
        }

        header("Location: manage-requests.php?msg=updated");
        exit;
    }
}

$status_filter = $_GET['filter'] ?? 'all';
$query_where = [];
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $query_where[] = "b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_clause = !empty($query_where) ? "WHERE " . implode(" AND ", $query_where) : "";

$sql = "SELECT b.id, c.name as child_name, u.name as parent_name, u.email as parent_email,
               v.name as vaccine_name, v.price as vaccine_price,
               h.name as hospital_name, b.booking_date, b.status
        FROM bookings b
        JOIN children c ON b.child_id = c.id
        JOIN users u ON c.parent_id = u.id
        JOIN vaccines v ON b.vaccine_id = v.id
        JOIN hospitals h ON b.hospital_id = h.id
        $where_clause
        ORDER BY b.id DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

include __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
  <div class="r-alert r-alert-ok">
    <i class="fa-solid fa-circle-check"></i>
    <span>Request status updated.</span>
  </div>
<?php endif; ?>

<div class="r-card">
  <div class="r-titlebar">
    <span>Vaccination Booking Requests</span>
    <div class="r-filter-tabs">
      <a href="manage-requests.php?filter=all" class="<?php echo ($status_filter === 'all') ? 'active' : ''; ?>">All</a>
      <a href="manage-requests.php?filter=Pending" class="<?php echo ($status_filter === 'Pending') ? 'active' : ''; ?>">Pending</a>
      <a href="manage-requests.php?filter=Approved" class="<?php echo ($status_filter === 'Approved') ? 'active' : ''; ?>">Approved</a>
      <a href="manage-requests.php?filter=Completed" class="<?php echo ($status_filter === 'Completed') ? 'active' : ''; ?>">Completed</a>
      <a href="manage-requests.php?filter=Cancelled" class="<?php echo ($status_filter === 'Cancelled') ? 'active' : ''; ?>">Cancelled</a>
    </div>
  </div>

  <div class="r-card-body" style="padding: 0;">
    <div class="r-table-responsive">
      <table class="r-full" id="requestsTable">
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Child Name</th>
            <th>Parent</th>
            <th>Vaccine</th>
            <th>Hospital</th>
            <th>Date</th>
            <th>Status</th>
            <th style="text-align: right;">Action</th>
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
              <td>
                <span class="r-badge r-badge-primary"><?php echo htmlspecialchars($b['vaccine_name']); ?></span>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Rs. <?php echo number_format($b['vaccine_price'], 2); ?></div>
              </td>
              <td><?php echo htmlspecialchars($b['hospital_name']); ?></td>
              <td><strong><?php echo htmlspecialchars($b['booking_date']); ?></strong></td>
              <td>
                <?php if ($b['status'] === 'Approved'): ?>
                  <span class="r-badge r-badge-ok">Approved</span>
                <?php elseif ($b['status'] === 'Completed'): ?>
                  <span class="r-badge r-badge-ok">Completed</span>
                <?php elseif ($b['status'] === 'Cancelled'): ?>
                  <span class="r-badge r-badge-danger">Cancelled</span>
                <?php else: ?>
                  <span class="r-badge r-badge-warn">Pending Review</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 4px; justify-content: flex-end;">
                  <?php if ($b['status'] === 'Pending'): ?>
                    <form method="POST" style="margin: 0; display: inline;">
                      <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                      <input type="hidden" name="new_status" value="Approved">
                      <button type="submit" name="update_status" class="r-btn r-btn-primary r-btn-sm">
                        <i class="fa-solid fa-check"></i> Approve
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if ($b['status'] !== 'Cancelled' && $b['status'] !== 'Completed'): ?>
                    <form method="POST" style="margin: 0; display: inline;">
                      <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                      <input type="hidden" name="new_status" value="Cancelled">
                      <button type="submit" name="update_status" class="r-btn r-btn-danger r-btn-sm" onclick="return confirm('Reject or cancel this request?');">
                        <i class="fa-solid fa-xmark"></i> Reject
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 26px; color: #64748b;">
                No booking requests found in this category.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="r-card-footer">
    <span>Manage and review all parent appointment requests</span>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

