/**
 * ============================================================================
 * VaxCare — Shared Client-Side JavaScript
 * File: assets/js/main.js
 * Description: Client-side functions for real-time table filtering,
 *              DOM search, and native dialog modal manipulation.
 * ============================================================================
 */

/**
 * Filter an HTML table in real time based on user input.
 * Logic:
 * Iterates through all rows within the target table body, converts row text
 * to lowercase, and toggles display style based on whether the query matches.
 * 
 * @param {string} query The search string typed by the user.
 * @param {string} tableId The DOM ID of the target <table> element (default: 'childrenTable').
 */
function filterTable(query, tableId = 'childrenTable') {
  query = query.toLowerCase().trim();
  const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
  rows.forEach(r => {
    // If query is empty or row text contains query substring, show row
    if (!query || r.innerText.toLowerCase().includes(query)) {
      r.style.display = '';
    } else {
      r.style.display = 'none';
    }
  });
}

/**
 * Open the official Vaccination Certificate modal and populate data.
 * Logic:
 * Populates placeholder elements in the native <dialog> element with the
 * passed parameters (child name, parent, vaccine, hospital, ID) and calls showModal().
 * 
 * @param {string} child Name of the child.
 * @param {string} parent Name of the parent/guardian.
 * @param {string} vaccine Name of the administered vaccine.
 * @param {string} hospital Name of the administering hospital facility.
 * @param {string} id Formatted registration/child ID.
 * @param {string} certDate The actual vaccination/booking date string.
 */
function openCertDialog(child, parent, vaccine, hospital, id, certDate) {
  const dlg = document.getElementById('certDialog');
  if (!dlg) return;

  if (document.getElementById('dlgChild')) document.getElementById('dlgChild').innerText = child;
  if (document.getElementById('dlgParent')) document.getElementById('dlgParent').innerText = parent;
  if (document.getElementById('dlgVaccine')) document.getElementById('dlgVaccine').innerText = vaccine;
  if (document.getElementById('dlgHospital')) document.getElementById('dlgHospital').innerText = hospital;
  if (document.getElementById('dlgId')) document.getElementById('dlgId').innerText = id;
  if (document.getElementById('dlgDate')) document.getElementById('dlgDate').innerText = certDate || new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: '2-digit' });
  if (document.getElementById('dlgLot')) {
    const cleanId = String(id).replace(/\D/g, '') || '01';
    document.getElementById('dlgLot').innerText = 'LOT-2026-WHO-' + cleanId.padStart(3, '0');
  }

  // Use HTML5 dialog standard method
  dlg.showModal();
}

/**
 * Close the native certificate modal dialog.
 */
function closeCertDialog() {
  const dlg = document.getElementById('certDialog');
  if (dlg) {
    dlg.close();
  }
}

/**
 * Responsive Mobile Navigation & Hamburger Menu Toggle
 */
document.addEventListener('DOMContentLoaded', function () {
  const menuBtn = document.getElementById('mobileMenuBtn');
  const closeBtn = document.getElementById('sidebarCloseBtn');
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  function openSidebar() {
    if (sidebar) sidebar.classList.add('open');
    if (backdrop) backdrop.classList.add('active');
    document.body.classList.add('sidebar-open');
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('active');
    document.body.classList.remove('sidebar-open');
  }

  if (menuBtn) {
    menuBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      openSidebar();
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      closeSidebar();
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  // Close on Escape key press
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
      closeSidebar();
    }
  });

  // Automatically close sidebar when clicking a link on mobile screens
  if (sidebar) {
    const navLinks = sidebar.querySelectorAll('.r-sidebar-nav a, .r-sign-out-btn');
    navLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth <= 768) {
          closeSidebar();
        }
      });
    });
  }
});
