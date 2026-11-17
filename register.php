<?php
// register.php — Parent and Hospital Registration Forms
require_once __DIR__ . '/config/db.php';

$msg = '';
$err = '';
$tab = $_GET['tab'] ?? 'parent';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $reg_type = $_POST['reg_type'] ?? 'parent';
    
    if ($reg_type === 'parent') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($name) && !empty($email) && !empty($password)) {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $err = "An account with email {$email} already exists.";
                $tab = 'parent';
            } else {
                $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'Parent')");
                $stmt->bind_param("ssss", $name, $email, $phone, $password);
                if ($stmt->execute()) {
                    $msg = "Account created successfully! You can now sign in.";
                    $tab = 'parent';
                } else {
                    $err = "Registration failed. Please try again.";
                }
            }
        } else {
            $err = "Please fill in all required fields.";
        }
    } elseif ($reg_type === 'hospital') {
        $hname    = trim($_POST['hname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($hname) && !empty($email) && !empty($location) && !empty($password)) {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $err = "An account with email {$email} already exists.";
                $tab = 'hospital';
            } else {
                $stmt1 = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'Hospital')");
                $stmt1->bind_param("ssss", $hname, $email, $phone, $password);
                $stmt1->execute();

                $stmt2 = $conn->prepare("INSERT INTO hospitals (name, location, phone, status) VALUES (?, ?, ?, 'Active')");
                $stmt2->bind_param("sss", $hname, $location, $phone);
                $stmt2->execute();

                $msg = "Hospital registered successfully! You can now sign in.";
                $tab = 'hospital';
            }
        } else {
            $err = "Please fill in all required hospital details.";
        }
    }
}
?>
<!doctype html>
<html lang="en" data-oldweb="saas2020">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VaxCare — Create Account</title>
  <!-- OldwebUI Framework CSS -->
  <link rel="stylesheet" href="https://chabdulwahhab.github.io/OldwebUI/dist/oldweb-all.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      box-sizing: border-box;
    }
    .reg-container {
      width: 100%;
      max-width: 460px;
    }
    .reg-tabs {
      display: flex;
      border-bottom: 2px solid var(--r-border, #e2e8f0);
      margin-bottom: 20px;
      gap: 20px;
    }
    .reg-tab-btn {
      padding: 8px 2px;
      font-size: 14px;
      font-weight: 500;
      color: var(--r-fg-muted, #64748b);
      text-decoration: none;
      border-bottom: 2px solid transparent;
      margin-bottom: -2px;
      transition: all 0.15s ease;
    }
    .reg-tab-btn:hover {
      color: var(--r-fg, #0f172a);
    }
    .reg-tab-btn.active {
      color: var(--r-accent, #2563eb);
      font-weight: 600;
      border-bottom-color: var(--r-accent, #2563eb);
    }
  </style>
</head>
<body>

  <div class="reg-container">
    <div style="text-align: center; margin-bottom: 24px;">
      <a href="index.php" style="text-decoration: none; display: inline-block;">
        <div class="r-brand-logo" style="width: 44px; height: 44px; font-size: 20px; margin: 0 auto 12px;">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h1 style="margin: 0; font-size: 1.6rem; font-weight: 700; color: #0f172a;">
          Create Account
        </h1>
      </a>
      <div style="margin-top: 4px; font-size: 13px; color: #64748b;">
        Child Vaccination System
      </div>
    </div>

    <div class="r-card">
      <div class="r-titlebar">
        <span>Create Account</span>
      </div>

      <div class="r-card-body">
        <?php if (!empty($msg)): ?>
          <div class="r-alert r-alert-ok">
            <i class="fa-solid fa-circle-check"></i>
            <div>
              <?php echo htmlspecialchars($msg); ?>
              <div style="margin-top: 6px;">
                <a href="login.php" class="r-btn r-btn-primary r-btn-sm" style="text-decoration:none;">Go to Sign In</a>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($err)): ?>
          <div class="r-alert r-alert-err">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($err); ?></span>
          </div>
        <?php endif; ?>

        <div class="reg-tabs">
          <a href="register.php?tab=parent" class="reg-tab-btn <?php echo ($tab === 'parent') ? 'active' : ''; ?>">
            Parent Account
          </a>
          <a href="register.php?tab=hospital" class="reg-tab-btn <?php echo ($tab === 'hospital') ? 'active' : ''; ?>">
            Hospital Account
          </a>
        </div>

        <?php if ($tab === 'parent'): ?>
          <form method="POST" action="register.php?tab=parent">
            <input type="hidden" name="reg_type" value="parent">

            <div class="r-field">
              <label for="p_name">Your Name</label>
              <input type="text" id="p_name" name="name" class="r-input-block" placeholder="e.g. Sarah Jenkins" required>
            </div>

            <div class="r-field">
              <label for="p_email">Email Address</label>
              <input type="email" id="p_email" name="email" class="r-input-block" placeholder="parent@example.com" required>
            </div>

            <div class="r-field">
              <label for="p_phone">Phone Number</label>
              <input type="text" id="p_phone" name="phone" class="r-input-block" placeholder="e.g. 0300-1234567">
            </div>

            <div class="r-field">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <label for="p_pass" style="margin-bottom: 0;">Password</label>
                <button type="button" onclick="togglePassword('p_pass', this)" style="background: none; border: none; cursor: pointer; color: var(--r-fg-muted, #64748b); font-size: 12px; padding: 0; display: inline-flex; align-items: center; gap: 4px;" title="Show or hide password">
                  <i class="fa-solid fa-eye"></i> <span>Show</span>
                </button>
              </div>
              <div style="position: relative; display: flex; align-items: center;">
                <input type="password" id="p_pass" name="password" class="r-input-block" placeholder="Choose a password" style="padding-right: 36px;" required>
                <button type="button" onclick="togglePassword('p_pass', this)" style="position: absolute; right: 8px; background: none; border: none; cursor: pointer; color: var(--r-fg-muted, #64748b); font-size: 14px; padding: 4px;" title="Toggle password visibility">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="r-btn r-btn-primary r-btn-block" style="padding: 9px; font-size: 14px; margin-top: 8px;">
              <i class="fa-solid fa-user-plus"></i> Create Parent Account
            </button>
          </form>

        <?php else: ?>
          <form method="POST" action="register.php?tab=hospital">
            <input type="hidden" name="reg_type" value="hospital">

            <div class="r-field">
              <label for="h_name">Hospital Name</label>
              <input type="text" id="h_name" name="hname" class="r-input-block" placeholder="e.g. City General Hospital" required>
            </div>

            <div class="r-field">
              <label for="h_email">Email Address</label>
              <input type="email" id="h_email" name="email" class="r-input-block" placeholder="hospital@example.com" required>
            </div>

            <div class="r-field">
              <label for="h_loc">Address / Location</label>
              <input type="text" id="h_loc" name="location" class="r-input-block" placeholder="e.g. Clifton, Karachi" required>
            </div>

            <div class="r-field">
              <label for="h_phone">Phone Number</label>
              <input type="text" id="h_phone" name="phone" class="r-input-block" placeholder="e.g. 021-39999999">
            </div>

            <div class="r-field">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <label for="h_pass" style="margin-bottom: 0;">Password</label>
                <button type="button" onclick="togglePassword('h_pass', this)" style="background: none; border: none; cursor: pointer; color: var(--r-fg-muted, #64748b); font-size: 12px; padding: 0; display: inline-flex; align-items: center; gap: 4px;" title="Show or hide password">
                  <i class="fa-solid fa-eye"></i> <span>Show</span>
                </button>
              </div>
              <div style="position: relative; display: flex; align-items: center;">
                <input type="password" id="h_pass" name="password" class="r-input-block" placeholder="Choose a password" style="padding-right: 36px;" required>
                <button type="button" onclick="togglePassword('h_pass', this)" style="position: absolute; right: 8px; background: none; border: none; cursor: pointer; color: var(--r-fg-muted, #64748b); font-size: 14px; padding: 4px;" title="Toggle password visibility">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="r-btn r-btn-primary r-btn-block" style="padding: 9px; font-size: 14px; margin-top: 8px;">
              <i class="fa-solid fa-hospital"></i> Create Hospital Account
            </button>
          </form>
        <?php endif; ?>

        <div style="margin-top: 20px; text-align: center; font-size: 13px; color: #64748b;">
          Already have an account? <a href="login.php" style="color: #2563eb; font-weight: 500; text-decoration: none;">Sign in here</a>
        </div>
      </div>

      <div class="r-card-footer" style="justify-content: center;">
        <span>VaxCare — Child Vaccination System</span>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(inputId, triggerBtn) {
      const input = document.getElementById(inputId);
      if (!input) return;

      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';

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
