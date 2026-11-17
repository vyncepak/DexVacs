<?php
/**
 * ============================================================================
 * DexVacs — Central Configuration & Database Engine
 * File: config/db.php
 * Description: Manages MySQL database connection, active session state,
 *              and strict role-based access control (RBAC) security checks.
 * ============================================================================
 */

// Initialize PHP session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------------------------
// Database Connection Settings
// ----------------------------------------------------------------------------
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'vaxcare';

// Establish connection using MySQLi driver
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check for connection failure and terminate execution with a descriptive error
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set character encoding to UTF-8 for internationalization and special characters
$conn->set_charset("utf8mb4");

// ----------------------------------------------------------------------------
// Authentication & Role Helper Functions
// ----------------------------------------------------------------------------

/**
 * Check if a user is currently logged into the system.
 * Logic: Verifies that the 'user_id' session variable exists and is not empty.
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Retrieve the current logged-in user's profile details.
 * Logic: Returns an associative array with sanitised session values or null.
 * @return array|null User details array [id, name, email, role] or null.
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['role'] ?? ''
    ];
}

/**
 * Verify whether the active user possesses a specific role or belongs to a list of roles.
 * Logic: Compares the active session role against a single string or an array of allowed roles.
 * @param string|array $role Single role name or array of allowed role names.
 * @return bool True if match found, false otherwise.
 */
function has_role($role) {
    $user = current_user();
    if (!$user) return false;
    if (is_array($role)) {
        return in_array($user['role'], $role, true);
    }
    return $user['role'] === $role;
}

/**
 * Enforce authentication and role permissions on protected pages.
 * Logic:
 * 1. Determines relative directory depth to formulate accurate redirection URLs.
 * 2. If the user is unauthenticated, redirects immediately to login.php.
 * 3. If allowed roles are provided and the user lacks permission, safely
 *    redirects the user back to their respective role dashboard.
 * @param array $allowed_roles Whitelist of roles permitted to access the calling page.
 */
function require_auth($allowed_roles = []) {
    // Calculate relative root path based on executing script's directory depth
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    $root = ($current_dir === 'DexVac' || $current_dir === 'htdocs') ? '' : '../';

    // Guard 1: Must be logged in
    if (!is_logged_in()) {
        header("Location: " . $root . "login.php?err=" . urlencode("Please sign in to access this page."));
        exit;
    }

    // Guard 2: Must have permitted role if whitelist specified
    if (!empty($allowed_roles)) {
        $allowed = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
        if (!has_role($allowed)) {
            $user = current_user();
            // Redirect unauthorized users to their assigned home module
            if ($user['role'] === 'Admin') {
                header("Location: " . $root . "admin/dashboard.php?err=" . urlencode("Access denied for your role."));
            } elseif ($user['role'] === 'Hospital') {
                header("Location: " . $root . "hospital/dashboard.php?err=" . urlencode("Access denied for your role."));
            } else {
                header("Location: " . $root . "parent/dashboard.php?err=" . urlencode("Access denied for your role."));
            }
            exit;
        }
    }
}

/**
 * Resolve the database hospital ID associated with a logged-in Hospital user.
 * Logic:
 * Uses the primary identifying keyword from the user's name to look up
 * the corresponding hospital record in the `hospitals` table.
 * If unmatched, safely defaults to hospital record #1.
 * @param mysqli $conn Active database connection.
 * @param int $user_id Logged-in user's database ID.
 * @param string $user_name Logged-in user's display name.
 * @return int Resolved hospital record ID.
 */
function get_hospital_id_for_user($conn, $user_id, $user_name) {
    // Extract first token of hospital name (e.g. "Abbasi" from "Abbasi Shaheed Hospital Admin")
    $first_word = explode(' ', trim($user_name))[0];
    $stmt = $conn->prepare("SELECT id FROM hospitals WHERE name LIKE ? LIMIT 1");
    $searchTerm = "%" . $first_word . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return (int)$row['id'];
    }
    return 1; // Default fallback ID
}
?>
