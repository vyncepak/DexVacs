<?php
/**
 * ============================================================================
 * DexVacs — System Landing Page & Entry Gateway
 * File: index.php
 * Description: Checks active session state and redirects logged-in users to
 *              their dashboard, or renders the single-page OldwebUI site.
 * ============================================================================
 */

require_once __DIR__ . '/config/db.php';

// If user is already authenticated, route straight to their assigned dashboard
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

// Serve the single-page presentation website
include __DIR__ . '/index.html';
