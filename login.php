<?php
/**
 * ============================================================================
 * VaxCare — Unified User Login Gateway
 * File: login.php
 * Description: Authenticates users across all three roles (Parent, Hospital, Admin).
 *              Enforces strict 3-way validation: Email, Password, and Role must
 *              all match simultaneously before session establishment.
 * ============================================================================
 */

// Load central configuration and database connection
require_once __DIR__ . '/config/db.php';

// ----------------------------------------------------------------------------
// Active Session Check: Redirect already logged-in users to their dashboard
// ----------------------------------------------------------------------------
if (is_logged_in()) {
    $u = current_user();
    if ($u['role'] === 'Admin') {
        header('Location: admin/dashboard.php');
    } elseif ($u['role'] === 'Hospital') {
        header('Location: hospital/dashboard.php');
    } else {
        header('Location: parent/dashboard.php');
    }
    exit;
}

$error = '';

// ----------------------------------------------------------------------------
// Form Submission Processing
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form inputs
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? '');

    // Validate that required fields are not empty
    if (!empty($email) && !empty($password) && !empty($role)) {
        
        // Prepare parameterized statement to query user record matching BOTH email AND selected role
        // This prevents cross-role impersonation (e.g., logging into an admin account as a parent)
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            // Verify password match
            if ($password === $user['password']) {
                // Initialize session variables upon successful authentication
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role']       = $user['role'];

                // Route the authenticated user to their role-specific dashboard module
                if ($user['role'] === 'Admin') {
                    header('Location: admin/dashboard.php');
                } elseif ($user['role'] === 'Hospital') {
                    header('Location: hospital/dashboard.php');
                } else {
                    header('Location: parent/dashboard.php');
                }
                exit;
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            $error = 'No account found with this email and role (' . htmlspecialchars($role) . ').';
        }
    } else {
        $error = 'Please enter your email, password, and select your role.';
    }
}
?>
<!doctype html>
<html lang="en" data-oldweb="saas2020">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VaxCare — Sign In</title>
  <!-- OldwebUI Framework CSS -->
  <link rel="stylesheet" href="https://chabdulwahhab.github.io/OldwebUI/dist/oldweb-all.css">
  <!-- Application Layout Stylesheet -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px;">

  

  <div style="width: 100%; max-width: 420px; position: relative; z-index: 1;">
    <!-- Navigation Back to Main Page -->
    <div style="margin-bottom: 14px;">
      <a href="index.php" class="r-btn r-btn-sm" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
        <i class="fa-solid fa-arrow-left"></i> Back to Main Page
      </a>
    </div>

    <!-- Brand Header -->
    <div style="text-align: center; margin-bottom: 24px;">
      <a href="index.php" style="text-decoration: none; display: inline-block;">
        <div class="r-brand-logo" style="width: 44px; height: 44px; font-size: 20px; margin: 0 auto 12px;">
          <i class="fa-solid fa-syringe"></i>
        </div>
        <h1 style="margin: 0; font-size: 1.6rem; font-weight: 700; color: #0f172a;">DexVacs</h1>
      </a>
      <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Child Vaccination System</div>
    </div>

    <!-- Login Card -->
    <div class="r-card">
      <div class="r-titlebar">
        <span>Sign In</span>
      </div>

      <div class="r-card-body">
        <!-- Display alert if error occurred during login -->
        <?php if (!empty($error)): ?>
          <div class="r-alert r-alert-err">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>

        <!-- Display warning if redirected from auth guard -->
        <?php if (isset($_GET['err'])): ?>
          <div class="r-alert r-alert-warn">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($_GET['err']); ?></span>
          </div>
        <?php endif; ?>

        <!-- Quick Demo Autofill Bar -->
        <div style="margin-bottom: 16px; padding: 10px 12px; background: var(--r-surface-alt, #f8fafc); border: var(--r-bw, 1px) solid var(--r-border, #e2e8f0); border-radius: var(--r-radius, 4px);">
          <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; color: var(--r-fg-muted, #64748b);">
            <i class="fa-solid fa-wand-magic-sparkles"></i> One-Click Demo Autofill:
          </div>
          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <button type="button" class="r-btn r-btn-sm" onclick="autofill('Parent', 'Elly@gmail.com', 'parent123')" title="Autofill Parent Account">
              <i class="fa-solid fa-user"></i> Parent
            </button>
            <button type="button" class="r-btn r-btn-sm" onclick="autofill('Hospital', 'info@abbasi.pk', 'hosp123')" title="Autofill Hospital Account">
              <i class="fa-solid fa-hospital"></i> Hospital
            </button>
            <button type="button" class="r-btn r-btn-sm" onclick="autofill('Admin', 'admin@vaxcare.com', 'admin123')" title="Autofill Admin Account">
              <i class="fa-solid fa-user-shield"></i> Admin
            </button>
          </div>
        </div>

        <!-- Authentication Form -->
        <form method="POST" action="login.php">
          <div class="r-field">
            <label for="roleSelect">Sign In As</label>
            <select id="roleSelect" name="role" class="r-input-block" required>
              <option value="Parent">Parent</option>
              <option value="Hospital">Hospital</option>
              <option value="Admin">Admin</option>
            </select>
          </div>

          <div class="r-field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="r-input-block" placeholder="name@example.com" required>
          </div>

          <div class="r-field">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
              <label for="password" style="margin-bottom: 0;">Password</label>
            </div>
            <div style="position: relative; display: flex; align-items: center;">
              <input type="password" id="password" name="password" class="r-input-block" placeholder="Password" style="padding-right: 36px;" required>
              <button type="button" onclick="togglePassword('password', this)" style="position: absolute; right: 8px; background: none; border: none; cursor: pointer; color: var(--r-fg-muted, #64748b); font-size: 14px; padding: 4px;" title="Toggle password visibility">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="r-btn r-btn-primary r-btn-block" style="padding: 9px; font-size: 14px; margin-top: 8px;">
            <i class="fa-solid fa-right-to-bracket"></i> Sign In
          </button>
        </form>

        <!-- Test Accounts Table (Click to Autofill) -->
        <div style="margin-top: 24px; padding-top: 16px; border-top: var(--r-bw, 1px) solid var(--r-border, #f1f5f9);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span style="font-size: 11px; font-weight: 600; color: var(--r-fg-muted, #64748b); text-transform: uppercase; letter-spacing: 0.05em;">
              Test Accounts (Click any row to fill)
            </span>
          </div>
          <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
            <tbody>
              <tr onclick="autofill('Parent', 'Elly@gmail.com', 'parent123')" style="cursor: pointer; border-bottom: 1px solid var(--r-border, #f1f5f9);" title="Click to autofill Parent login">
                <td style="padding: 6px 0; color: var(--r-fg-muted, #64748b);"><strong>Parent:</strong></td>
                <td><code>Elly@gmail.com</code></td>
                <td style="text-align: right;"><button type="button" class="r-btn r-btn-sm" style="padding: 2px 6px; font-size: 11px;">Fill</button></td>
              </tr>
              <tr onclick="autofill('Hospital', 'info@abbasi.pk', 'hosp123')" style="cursor: pointer; border-bottom: 1px solid var(--r-border, #f1f5f9);" title="Click to autofill Hospital login">
                <td style="padding: 6px 0; color: var(--r-fg-muted, #64748b);"><strong>Hospital:</strong></td>
                <td><code>info@abbasi.pk</code></td>
                <td style="text-align: right;"><button type="button" class="r-btn r-btn-sm" style="padding: 2px 6px; font-size: 11px;">Fill</button></td>
              </tr>
              <tr onclick="autofill('Admin', 'admin@vaxcare.com', 'admin123')" style="cursor: pointer;" title="Click to autofill Admin login">
                <td style="padding: 6px 0; color: var(--r-fg-muted, #64748b);"><strong>Admin:</strong></td>
                <td><code>admin@vaxcare.com</code></td>
                <td style="text-align: right;"><button type="button" class="r-btn r-btn-sm" style="padding: 2px 6px; font-size: 11px;">Fill</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="r-card-footer" style="justify-content: center;">
        <span>Need an account? <a href="register.php" style="color: var(--r-accent, #2563eb); font-weight: 500; text-decoration: none;">Create an account</a></span>
      </div>
    </div>
  </div>

  <!-- Three.js CDN Library & Unified Background System -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="assets/js/three-bg.js"></script>
  <script>
    function autofill(role, email, password) {
      const roleSelect = document.getElementById('roleSelect');
      const emailInput = document.getElementById('email');
      const passInput = document.getElementById('password');

      if (roleSelect) roleSelect.value = role;
      if (emailInput) emailInput.value = email;
      if (passInput) passInput.value = password;

      // Subtle focus on password input ready to submit
      if (passInput) passInput.focus();
    }

    function togglePassword(inputId, triggerBtn) {
      const input = document.getElementById(inputId);
      if (!input) return;

      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';

      // Update all toggle icons/labels associated with this field
      document.querySelectorAll(`button[onclick*="'${inputId}'"]`).forEach(btn => {
        const icon = btn.querySelector('i');
        const text = btn.querySelector('span');
        if (icon) {
          icon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }
        if (text) {
          text.textContent = isPassword ? 'Hide' : 'Show';
        }
      });
    }
  </script>
</body>
</html>
