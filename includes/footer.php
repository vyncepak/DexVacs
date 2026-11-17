<?php
// includes/footer.php — Shared layout footer & scripts

$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$root = ($current_dir === 'DexVac' || $current_dir === 'htdocs') ? '' : '../';
?>
  </main>

  <!-- Official Vaccination Certificate Modal (WHO-SVC Compliant Layout) -->
  <dialog id="certDialog" class="r-modal cert-modal">
    <div class="r-titlebar cert-titlebar">
      <span><i class="fa-solid fa-certificate"></i> Official Vaccination Passport</span>
      <button type="button" class="r-btn r-btn-sm" onclick="closeCertDialog()" aria-label="Close dialog"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="r-modal-body" style="padding: 16px;">
      <div class="cert-frame">
        <!-- Certificate Header with Crest -->
        <div class="cert-crest-row">
          <div class="cert-emblem-badge">
            <i class="fa-solid fa-syringe"></i>
          </div>
          <div class="cert-authority">
            <div class="cert-authority-title">MINISTRY OF HEALTH &amp; IMMUNIZATION SERVICES</div>
            <div class="cert-authority-sub">WORLD HEALTH ORGANIZATION &bull; SMART VACCINATION CERTIFICATE (WHO-SVC)</div>
          </div>
          <div class="cert-doc-badge">
            <span class="cert-doc-id">DOC-REF: <strong id="dlgId">CH-0000</strong></span>
            <span class="cert-doc-status"><i class="fa-solid fa-circle-check"></i> VERIFIED</span>
          </div>
        </div>

        <div class="cert-divider"></div>

        <h2 class="cert-main-heading">CERTIFICATE OF IMMUNIZATION</h2>
        <div class="cert-intro">
          This document certifies that the individual named below has received verified immunization against designated vaccine-preventable diseases in accordance with national health guidelines.
        </div>

        <!-- Prominent Child Name Banner -->
        <div class="cert-beneficiary-card">
          <div class="cert-beneficiary-label">BENEFICIARY / CHILD NAME</div>
          <div class="cert-beneficiary-name" id="dlgChild">-</div>
        </div>

        <!-- Credentials Detail Grid -->
        <div class="cert-details-grid">
          <div class="cert-field">
            <span class="cert-f-label">PARENT / GUARDIAN</span>
            <span class="cert-f-val" id="dlgParent">-</span>
          </div>
          <div class="cert-field">
            <span class="cert-f-label">VACCINE FORMULATION</span>
            <span class="cert-f-val cert-highlight" id="dlgVaccine">-</span>
          </div>
          <div class="cert-field">
            <span class="cert-f-label">ADMINISTERING CLINIC / HOSPITAL</span>
            <span class="cert-f-val" id="dlgHospital">-</span>
          </div>
          <div class="cert-field">
            <span class="cert-f-label">ADMINISTRATION DATE</span>
            <span class="cert-f-val" id="dlgDate">-</span>
          </div>
          <div class="cert-field">
            <span class="cert-f-label">DOSE VERIFICATION STATUS</span>
            <span class="cert-f-val"><span class="r-badge r-badge-ok"><i class="fa-solid fa-circle-check"></i> Completed &amp; Recorded</span></span>
          </div>
          <div class="cert-field">
            <span class="cert-f-label">BATCH / LOT TRACKING</span>
            <span class="cert-f-val"><code id="dlgLot">LOT-2026-WHO-ACTIVE</code></span>
          </div>
        </div>

        <!-- Security, QR & Signatures Section -->
        <div class="cert-footer-row">
          <!-- 2D QR Verification Graphic -->
          <div class="cert-qr-box">
            <div class="cert-qr-code">
              <!-- Crisp SVG QR Code Representation -->
              <svg width="84" height="84" viewBox="0 0 100 100" fill="#0f172a" xmlns="http://www.w3.org/2000/svg">
                <!-- Top-Left Finder Pattern -->
                <rect x="5" y="5" width="28" height="28" rx="2" fill="none" stroke="#0f172a" stroke-width="6"/>
                <rect x="13" y="13" width="12" height="12" fill="#0f172a"/>
                <!-- Top-Right Finder Pattern -->
                <rect x="67" y="5" width="28" height="28" rx="2" fill="none" stroke="#0f172a" stroke-width="6"/>
                <rect x="75" y="13" width="12" height="12" fill="#0f172a"/>
                <!-- Bottom-Left Finder Pattern -->
                <rect x="5" y="67" width="28" height="28" rx="2" fill="none" stroke="#0f172a" stroke-width="6"/>
                <rect x="13" y="75" width="12" height="12" fill="#0f172a"/>
                <!-- Data modules -->
                <rect x="38" y="10" width="8" height="8"/>
                <rect x="50" y="10" width="8" height="8"/>
                <rect x="38" y="24" width="8" height="8"/>
                <rect x="50" y="24" width="8" height="8"/>
                <rect x="10" y="38" width="8" height="8"/>
                <rect x="24" y="38" width="8" height="8"/>
                <rect x="38" y="38" width="12" height="12"/>
                <rect x="56" y="38" width="8" height="8"/>
                <rect x="70" y="38" width="8" height="8"/>
                <rect x="84" y="38" width="8" height="8"/>
                <rect x="38" y="56" width="8" height="8"/>
                <rect x="50" y="56" width="12" height="12"/>
                <rect x="70" y="56" width="8" height="8"/>
                <rect x="84" y="56" width="8" height="8"/>
                <rect x="38" y="74" width="8" height="8"/>
                <rect x="50" y="74" width="8" height="8"/>
                <rect x="68" y="74" width="10" height="10"/>
                <rect x="84" y="74" width="8" height="8"/>
                <rect x="38" y="86" width="8" height="8"/>
                <rect x="56" y="86" width="8" height="8"/>
                <rect x="74" y="86" width="8" height="8"/>
              </svg>
            </div>
            <div class="cert-qr-caption">Scan with WHO-SVC reader to authenticate record</div>
          </div>

          <!-- Official Gold Embossed Stamp -->
          <div class="cert-seal-wrap">
            <div class="cert-golden-seal">
              <i class="fa-solid fa-ribbon"></i>
              <span class="seal-text-top">OFFICIAL SEAL</span>
              <span class="seal-star">&starf; &starf; &starf;</span>
              <span class="seal-text-bot">IMMUNIZATION RECORD</span>
            </div>
          </div>

          <!-- Signatures Column -->
          <div class="cert-signatures">
            <div class="cert-sig-block">
              <div class="cert-sig-script">Dr. R. Vance, MD</div>
              <div class="cert-sig-line"></div>
              <div class="cert-sig-title">Authorized Clinical Vaccinator</div>
            </div>
            <div class="cert-sig-block" style="margin-top: 12px;">
              <div class="cert-sig-meta"><i class="fa-solid fa-fingerprint"></i> Digitally Sealed by VaxCare Registry</div>
            </div>
          </div>
        </div>

        <div class="cert-watermark-note">
          Notice: This digital credential has been issued by VaxCare Health Informatics in compliance with HL7 FHIR and WHO Smart Vaccination Certificate standards. Valid across all accredited healthcare facilities.
        </div>
      </div>
    </div>
    <div class="r-modal-footer cert-modal-footer">
      <button type="button" class="r-btn r-btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Official Certificate</button>
      <button type="button" class="r-btn" onclick="closeCertDialog()"><i class="fa-solid fa-xmark"></i> Close</button>
    </div>
  </dialog>

  <!-- Three.js CDN Library & Unified Background System -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="<?php echo $root; ?>assets/js/three-bg.js"></script>
  <script src="<?php echo $root; ?>assets/js/main.js"></script>
</body>
</html>

