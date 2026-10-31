<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch settings
$app_site_name = 'GA LKG MW';
$app_site_desc = 'Manulife Medan';
$app_logo_url = '';
$app_favicon_url = '';
if (isset($conn)) {
    $query_settings = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
    if ($query_settings && mysqli_num_rows($query_settings) > 0) {
        $settings = mysqli_fetch_assoc($query_settings);
        $app_site_name = $settings['site_name'] ?: 'GA LKG MW';
        $app_site_desc = $settings['site_description'] ?: 'Manulife Medan';
        $app_logo_url = $settings['logo_url'] ?: '';
        $app_favicon_url = $settings['favicon_url'] ?: '';
    }
}

// Pengamanan variabel nama & role
$nama_tampil = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? $_SESSION['nama_user'] ?? 'User';
$role_tampil = $_SESSION['role'] ?? 'Agen';
$user_id = $_SESSION['user_id'];
$no_lisensi_tampil = $_SESSION['no_lisensi'] ?? '-';
$current_page = $_GET['page'] ?? 'dashboard';

// Fetch profile photo & verify active status
$user_profile_photo = '';
if (isset($conn)) {
    $q_user = mysqli_query($conn, "SELECT profile_photo, status FROM users WHERE id = '$user_id'");
    if ($q_user && mysqli_num_rows($q_user) > 0) {
        $u_data = mysqli_fetch_assoc($q_user);
        if (trim($u_data['status'] ?? '') !== 'Aktif') {
            session_unset();
            session_destroy();
            header("Location: login.php?error=inactive");
            exit;
        }
        $user_profile_photo = $u_data['profile_photo'];
    }
}
$profile_image_src = (!empty($user_profile_photo) && file_exists($user_profile_photo)) ? $user_profile_photo : "https://ui-avatars.com/api/?name=" . urlencode($nama_tampil) . "&background=ffffff&color=007a3e&rounded=true&bold=true";

// Point mingguan untuk pill navbar jika agen
$navbar_poin_minggu = 0;
if ($role_tampil === 'Agen' && isset($conn)) {
    $q_np = mysqli_query($conn, "SELECT SUM(poin) as total FROM activity_tracking WHERE user_id = '$user_id' AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    if ($q_np) {
        $d_np = mysqli_fetch_assoc($q_np);
        $navbar_poin_minggu = (int)($d_np['total'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?= htmlspecialchars($app_site_name); ?> - <?= htmlspecialchars($app_site_desc); ?></title>
    <?php if (!empty($app_favicon_url) && file_exists($app_favicon_url)): ?>
    <link rel="icon" href="<?= htmlspecialchars($app_favicon_url); ?>" type="image/x-icon">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --manulife-primary: #00a758;
            --manulife-dark: #007a3e;
            --manulife-forest: #003b1e;
            --manulife-soft: #f0fdf4;
            --surface-bg: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        html, body { 
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            background-color: var(--surface-bg); 
            font-family: 'Poppins', sans-serif; 
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
            position: relative;
        }
        
        .wrapper {
            display: flex;
            align-items: stretch;
            min-height: 100vh;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            position: relative;
        }

        /* ======================================================== */
        /* SIDEBAR STYLING - FULLY RESPONSIVE (BREAKPOINT 992px)    */
        /* ======================================================== */
        #sidebar {
            width: 260px;
            min-width: 260px;
            max-width: 260px;
            background: linear-gradient(180deg, var(--manulife-forest) 0%, var(--manulife-dark) 60%, var(--manulife-primary) 100%);
            color: #fff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            z-index: 1050;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }

        #sidebar::-webkit-scrollbar {
            width: 5px;
        }
        #sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        
        #sidebar .sidebar-header {
            padding: 20px 16px;
            background: rgba(0,0,0,0.2);
            text-align: center;
            position: relative;
            flex-shrink: 0;
        }
        #sidebar .sidebar-profile {
            flex-shrink: 0;
            background: rgba(255,255,255,0.03);
            padding: 18px 12px;
        }
        #sidebar ul.components {
            flex-grow: 1;
            margin-bottom: 1rem;
            padding-left: 0;
            list-style: none;
        }
        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        #sidebar ul li a:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-left: 4px solid rgba(255,255,255,0.6);
        }
        #sidebar ul li.active > a {
            color: #fff;
            background: rgba(255,255,255,0.2);
            border-left: 4px solid #ffffff;
            font-weight: 600;
        }
        #sidebar ul li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.05em;
            flex-shrink: 0;
        }
        .sidebar-footer {
            margin-top: auto;
            flex-shrink: 0;
            padding: 16px;
            padding-bottom: max(16px, env(safe-area-inset-bottom));
        }

        /* DESKTOP (>= 992px) */
        @media (min-width: 992px) {
            #sidebar {
                position: sticky;
                top: 0;
                height: 100vh;
                max-height: 100vh;
                margin-left: 0;
                transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            #sidebar.desktop-collapsed {
                margin-left: -260px;
            }
            .sidebar-overlay {
                display: none !important;
            }
        }

        /* MOBILE & TABLET (< 992px) */
        @media (max-width: 991.98px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 270px;
                max-width: 84vw;
                min-width: unset;
                height: 100vh;
                height: 100dvh;
                max-height: 100vh;
                max-height: 100dvh;
                transform: translateX(-100%);
                box-shadow: none;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            #sidebar.active {
                transform: translateX(0);
                box-shadow: 6px 0 25px rgba(0,0,0,0.35);
            }
            .sidebar-overlay {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                height: 100dvh;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(3px);
                -webkit-backdrop-filter: blur(3px);
                z-index: 1040;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }
            .sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }
        }

        /* ======================================================== */
        /* MAIN CONTENT & TOP NAVBAR                                */
        /* ======================================================== */
        #content {
            flex: 1;
            min-width: 0;
            width: 100%;
            max-width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .top-navbar {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 995;
            width: 100%;
        }

        @media (min-width: 768px) {
            .top-navbar {
                padding: 12px 24px;
            }
        }

        /* ======================================================== */
        /* CARDS & UTILITIES                                        */
        /* ======================================================== */
        .card-custom { 
            border: none; 
            border-radius: 14px; 
            box-shadow: 0 3px 14px rgba(0,0,0,0.04); 
            background: #ffffff;
            transition: box-shadow 0.2s ease;
        }

        .container-fluid {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding-left: 12px !important;
            padding-right: 12px !important;
        }

        @media (min-width: 576px) {
            .container-fluid {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
        }

        @media (min-width: 992px) {
            .container-fluid {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }
        }

        @media (max-width: 575.98px) {
            .card-custom {
                padding: 14px !important;
                border-radius: 12px !important;
            }
        }

        .btn-manulife { 
            background-color: var(--manulife-primary); 
            color: #fff; 
            font-weight: 600; 
            border-radius: 10px;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-manulife:hover { 
            background-color: var(--manulife-dark); 
            color: #fff; 
            box-shadow: 0 4px 12px rgba(0,167,88,0.25);
        }

        /* Table Responsive Horizontal Scroll Smoothing */
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        /* Form Controls Touch Targets */
        @media (max-width: 767.98px) {
            .form-control, .form-select, .btn:not(.btn-action-icon) {
                font-size: 0.92rem;
            }
            .btn:not(.btn-action-icon) {
                min-height: 38px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* Action Buttons in Tables (Uniform, Responsive, Pixel-Perfect) */
        .btn-action-icon {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            min-height: 32px !important;
            max-width: 32px !important;
            max-height: 32px !important;
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 8px !important;
            font-size: 0.85rem !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            box-sizing: border-box !important;
            transition: all 0.18s ease-in-out !important;
        }
        .btn-action-icon i {
            margin: 0 !important;
            padding: 0 !important;
            font-size: 0.85rem !important;
            line-height: 1 !important;
            display: inline-block;
        }
        .btn-action-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
        }
        .btn-outline-warning {
            color: #d97706 !important;
            border-color: #f59e0b !important;
        }
        .btn-outline-warning:hover {
            background-color: #f59e0b !important;
            border-color: #f59e0b !important;
            color: #ffffff !important;
        }
        
        @media print {
            #sidebar, .top-navbar, .btn, form, footer, .sidebar-overlay, .no-print { display: none !important; }
            body { background-color: #fff !important; }
            #content { margin: 0 !important; width: 100% !important; max-width: 100% !important; }
            .container-fluid { padding: 0 !important; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar Overlay for Mobile/Tablet -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <!-- Close Button for Mobile -->
            <button type="button" id="sidebarCloseBtn" class="btn btn-sm text-white p-1 d-lg-none position-absolute" style="top: 15px; right: 15px; border: none; background: rgba(255,255,255,0.18); border-radius: 8px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;" aria-label="Tutup Menu">
                <i class="fa-solid fa-xmark fs-5"></i>
            </button>

            <?php if (!empty($app_logo_url) && file_exists($app_logo_url)): ?>
                <img src="<?= htmlspecialchars($app_logo_url); ?>" alt="Logo" class="img-fluid mb-2 rounded bg-white p-1 shadow-sm" style="max-height: 50px; object-fit: contain;">
            <?php else: ?>
                <h5 class="mb-0 fw-bold d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-leaf"></i> <?= htmlspecialchars($app_site_name); ?>
                </h5>
            <?php endif; ?>
            <small class="text-white-50 d-block" style="font-size: 0.78rem;"><?= htmlspecialchars($app_site_desc); ?></small>
        </div>
        
        <div class="text-center sidebar-profile border-bottom border-light border-opacity-10">
            <a href="index.php?page=profile" class="text-decoration-none">
                <img src="<?= htmlspecialchars($profile_image_src); ?>" alt="User" width="70" height="70" class="rounded-circle mb-2 shadow" style="object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                <h6 class="mb-1 fw-bold text-white px-2 text-truncate"><?= htmlspecialchars($nama_tampil); ?></h6>
            </a>
            <div class="d-flex align-items-center justify-content-center gap-1 mt-1">
                <span class="badge bg-white text-success rounded-pill px-3 py-1 shadow-sm font-monospace" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-id-badge me-1"></i> <?= htmlspecialchars($role_tampil); ?>
                </span>
            </div>
            <small class="text-white-50 d-block mt-1 font-monospace" style="font-size: 0.75rem;">Lisensi: <?= htmlspecialchars($no_lisensi_tampil); ?></small>
        </div>

        <ul class="list-unstyled components mt-3">
            <li class="<?= $current_page == 'dashboard' ? 'active' : '' ?>">
                <a href="index.php?page=dashboard"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            </li>
            <li class="<?= $current_page == 'presensi' ? 'active' : '' ?>">
                <a href="index.php?page=presensi"><i class="fa-solid fa-camera-retro"></i> Presensi GPS</a>
            </li>
            <li class="<?= $current_page == 'activity' ? 'active' : '' ?>">
                <a href="index.php?page=activity"><i class="fa-solid fa-tasks"></i> Activity Tracking</a>
            </li>
            <li class="<?= $current_page == 'profile' ? 'active' : '' ?>">
                <a href="index.php?page=profile"><i class="fa-solid fa-user-circle"></i> Profil Saya</a>
            </li>
            
            <?php if ($role_tampil === 'Admin'): ?>
            <li class="px-4 mt-3 mb-2 text-uppercase text-white-50 fw-semibold" style="font-size: 0.72rem; letter-spacing: 1.5px;">Admin Control</li>
            <li class="<?= $current_page == 'rekrut' ? 'active' : '' ?>">
                <a href="index.php?page=rekrut"><i class="fa-solid fa-user-plus"></i> Monitoring Rekrut</a>
            </li>
            <li class="<?= $current_page == 'kelola_agen' ? 'active' : '' ?>">
                <a href="index.php?page=kelola_agen"><i class="fa-solid fa-users-cog"></i> Kelola Agen</a>
            </li>
            <li class="<?= $current_page == 'settings' ? 'active' : '' ?>">
                <a href="index.php?page=settings"><i class="fa-solid fa-gear"></i> Pengaturan</a>
            </li>
            <?php endif; ?>
        </ul>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="btn btn-outline-light w-100 rounded-pill fw-semibold btn-sm py-2">
                <i class="fa-solid fa-right-from-bracket me-2"></i>Keluar Portal
            </a>
        </div>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-2 gap-sm-3 flex-grow-1 min-w-0" style="min-width: 0;">
                <!-- Hamburger Button (Universal Toggle on Desktop & Mobile) -->
                <button type="button" id="sidebarCollapse" class="btn btn-light shadow-sm border px-2 py-1 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; border-radius: 8px;" aria-label="Buka/Tutup Menu" title="Buka/Tutup Menu">
                    <i class="fa-solid fa-bars text-success fs-5"></i>
                </button>
                
                <!-- Page Title -->
                <?php 
                    $icons = [
                        'dashboard' => 'fa-chart-pie',
                        'presensi' => 'fa-camera-retro',
                        'activity' => 'fa-tasks',
                        'rekrut' => 'fa-user-plus',
                        'kelola_agen' => 'fa-users-cog',
                        'settings' => 'fa-gear',
                        'profile' => 'fa-user-circle'
                    ];
                    $icon = $icons[$current_page] ?? 'fa-cube';

                    $page_titles = [
                        'dashboard' => 'Dashboard Kinerja',
                        'presensi' => 'Presensi GPS & Selfie',
                        'activity' => 'Activity Tracking',
                        'rekrut' => 'Monitoring Rekrut Agen Baru',
                        'kelola_agen' => 'Kelola Akun & Lisensi Agen',
                        'settings' => 'Pengaturan Portal',
                        'profile' => 'Profil Pengguna'
                    ];
                    $page_title_text = $page_titles[$current_page] ?? 'Sistem Informasi';
                ?>
                <div class="d-flex align-items-center gap-2 gap-md-3 min-w-0" style="min-width: 0;">
                    <div class="bg-success bg-opacity-10 text-success p-2 rounded-circle d-none d-md-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                        <i class="fa-solid <?= $icon ?>"></i>
                    </div>
                    <div style="min-width: 0;">
                        <h6 class="mb-0 fw-bold text-dark text-nowrap text-truncate" style="font-size: clamp(0.85rem, 2.8vw, 1.05rem); line-height: 1.25;" title="<?= htmlspecialchars($page_title_text); ?>">
                            <?= $page_title_text; ?>
                        </h6>
                        <small class="text-muted d-none d-sm-block text-truncate" style="font-size: 0.72rem; line-height: 1.1;">GA Office Manulife LKG MW Medan</small>
                    </div>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-1 gap-sm-2 flex-shrink-0 ms-2">
                <?php if ($role_tampil === 'Agen'): ?>
                    <div class="badge <?= $navbar_poin_minggu >= 20 ? 'bg-success' : 'bg-warning text-dark'; ?> px-2 px-sm-3 py-1 py-sm-2 rounded-pill d-inline-flex align-items-center gap-1 shadow-sm small" title="Poin 7 Hari Terakhir: <?= $navbar_poin_minggu; ?> dari target 20 poin">
                        <i class="fa-solid fa-award"></i>
                        <span class="d-none d-sm-inline">Poin: </span><strong><?= $navbar_poin_minggu; ?>/20</strong>
                    </div>
                <?php endif; ?>

                <div class="text-muted bg-light px-2 px-sm-3 py-1 rounded-pill border shadow-sm d-none d-sm-block small" style="font-size: 0.78rem;">
                    <i class="fa-regular fa-calendar-alt text-success me-1"></i> <span><?= date('d M Y'); ?></span>
                </div>

                <!-- User Dropdown (Universal Mobile + Desktop) -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center gap-1 text-decoration-none dropdown-toggle p-1 rounded-pill" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= htmlspecialchars($profile_image_src); ?>" alt="User" width="34" height="34" class="rounded-circle shadow-sm" style="object-fit: cover; border: 2px solid #00a758;">
                        <span class="d-none d-md-inline fw-semibold text-dark small"><?= htmlspecialchars(explode(' ', $nama_tampil)[0]); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2 mt-2" aria-labelledby="dropdownUser1" style="min-width: 200px; border-radius: 12px;">
                        <li class="px-3 py-2 border-bottom mb-1">
                            <span class="fw-bold d-block text-dark text-truncate small"><?= htmlspecialchars($nama_tampil); ?></span>
                            <small class="badge bg-success bg-opacity-10 text-success"><?= htmlspecialchars($role_tampil); ?></small>
                        </li>
                        <li><a class="dropdown-item py-2 small" href="index.php?page=profile"><i class="fa-solid fa-user-circle me-2 text-success"></i>Profil Saya</a></li>
                        <?php if ($role_tampil === 'Admin'): ?>
                            <li><a class="dropdown-item py-2 small" href="index.php?page=settings"><i class="fa-solid fa-gear me-2 text-secondary"></i>Pengaturan</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item py-2 small text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Keluar Portal</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main Content Container with Responsive Padding -->
        <div class="container-fluid p-2 p-sm-3 p-md-4 flex-grow-1">