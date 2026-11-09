<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <h2>CV. MANUNGGAL</h2>
                <span class="sidebar-role-badge role-boss">
                    🚢 Cargo Manifest
                </span>
            </div>
            <button class="sidebar-close-btn" onclick="closeSidebarMobile()" aria-label="Tutup Menu" title="Tutup Menu">✕</button>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php' || $current_page == 'index.php') ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span> <span class="nav-text">Dashboard</span>
            </a>

            <a href="input_keberangkatan.php" class="nav-link <?= ($current_page == 'input_keberangkatan.php' || $current_page == 'a.php') ? 'active' : '' ?>">
                <span class="nav-icon">📅</span> <span class="nav-text">Keberangkatan</span>
            </a>

            <a href="input_muatan.php" class="nav-link <?= ($current_page == 'input_muatan.php' || $current_page == 'b.php') ? 'active' : '' ?>">
                <span class="nav-icon">📝</span> <span class="nav-text">Input Muatan</span>
            </a>

            <a href="daftar_barang.php" class="nav-link <?= $current_page == 'daftar_barang.php' ? 'active' : '' ?>">
                <span class="nav-icon">📦</span> <span class="nav-text">Daftar Barang</span>
            </a>

            <a href="data.php" class="nav-link <?= ($current_page == 'data.php' || $current_page == 'preview_manifest_lama.php') ? 'active' : '' ?>">
                <span class="nav-icon">📜</span> <span class="nav-text">Riwayat Arsip</span>
            </a>
        </nav>
    </div>
</div>

<!-- Sidebar Overlay for Mobile -->
<div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebarMobile()"></div>

<!-- Mobile Bottom Navigation Bar -->
<nav class="mobile-bottom-nav" id="mobileBottomNav" aria-label="Navigasi Bawah">
    <a href="dashboard.php" class="bottom-nav-item <?= ($current_page == 'dashboard.php' || $current_page == 'index.php') ? 'active' : '' ?>">
        <span class="bottom-nav-icon">🏠</span>
        <span class="bottom-nav-label">Home</span>
    </a>

    <a href="input_keberangkatan.php" class="bottom-nav-item <?= ($current_page == 'input_keberangkatan.php' || $current_page == 'a.php') ? 'active' : '' ?>">
        <span class="bottom-nav-icon">📅</span>
        <span class="bottom-nav-label">Jadwal</span>
    </a>

    <a href="input_muatan.php" class="bottom-nav-item <?= ($current_page == 'input_muatan.php' || $current_page == 'b.php') ? 'active' : '' ?>">
        <span class="bottom-nav-icon">📝</span>
        <span class="bottom-nav-label">Muatan</span>
    </a>

    <a href="daftar_barang.php" class="bottom-nav-item <?= $current_page == 'daftar_barang.php' ? 'active' : '' ?>">
        <span class="bottom-nav-icon">📦</span>
        <span class="bottom-nav-label">Barang</span>
    </a>

    <a href="data.php" class="bottom-nav-item <?= ($current_page == 'data.php' || $current_page == 'preview_manifest_lama.php') ? 'active' : '' ?>">
        <span class="bottom-nav-icon">📜</span>
        <span class="bottom-nav-label">Riwayat</span>
    </a>
</nav>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isMobile = window.innerWidth <= 1024;

    if (isMobile) {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        if (overlay) {
            if (isCollapsed) {
                overlay.classList.remove('active');
            } else {
                overlay.classList.add('active');
            }
        }
    } else {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }
}

function closeSidebarMobile() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.add('collapsed');
    if (overlay) overlay.classList.remove('active');
}

(function() {
    const isMobile = window.innerWidth <= 1024;
    const sidebar = document.getElementById('sidebar');
    if (isMobile) {
        if (sidebar) sidebar.classList.add('collapsed');
    } else {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            if (sidebar) sidebar.classList.add('collapsed');
        }
    }

    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay) overlay.classList.remove('active');
        }
    });
})();
</script>

