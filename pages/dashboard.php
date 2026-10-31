<?php
require_once 'config/database.php';

$user_id     = $_SESSION['user_id'];
$user_role   = $_SESSION['role'] ?? 'Agen';
$nama_user   = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Pengguna';
$target_mingguan = 20;

// ==========================================
// DATA METRIK UNTUK ROLE: AGEN
// ==========================================
if ($user_role === 'Agen') {
    // 1. Status presensi hari ini
    $q_absen_today = mysqli_query($conn, "SELECT * FROM presensi WHERE user_id = '$user_id' AND DATE(tanggal_waktu) = CURDATE() ORDER BY id DESC LIMIT 1");
    $presensi_today = mysqli_fetch_assoc($q_absen_today);

    // 2. Akumulasi Poin 7 Hari Terakhir
    $q_poin = mysqli_query($conn, "SELECT SUM(poin) as total_poin, COUNT(*) as total_aktivitas FROM activity_tracking WHERE user_id = '$user_id' AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $d_poin = mysqli_fetch_assoc($q_poin);
    $total_poin_minggu  = (int)($d_poin['total_poin'] ?? 0);
    $total_akt_minggu   = (int)($d_poin['total_aktivitas'] ?? 0);
    $persen_target      = min(100, round(($total_poin_minggu / $target_mingguan) * 100));
    $sisa_poin          = max(0, $target_mingguan - $total_poin_minggu);

    // 3. Riwayat 5 aktivitas terbaru milik agen ini
    $q_recent_akt = mysqli_query($conn, "SELECT * FROM activity_tracking WHERE user_id = '$user_id' ORDER BY tanggal DESC, id DESC LIMIT 5");
}

// ==========================================
// DATA METRIK UNTUK ROLE: ADMIN / GA OWNER
// ==========================================
if ($user_role === 'Admin') {
    // 1. Total Agen Aktif
    $q_total_agen = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'Agen' AND status = 'Aktif'");
    $total_agen = (int)(mysqli_fetch_assoc($q_total_agen)['total'] ?? 0);

    // 2. Agen Hadir Hari Ini
    $q_hadir_today = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as total FROM presensi WHERE DATE(tanggal_waktu) = CURDATE()");
    $total_hadir_today = (int)(mysqli_fetch_assoc($q_hadir_today)['total'] ?? 0);

    // 3. Total Kandidat Rekrut Bulan Ini
    $q_rekrut_bulan = mysqli_query($conn, "SELECT COUNT(*) as total FROM monitoring_rekrut WHERE MONTH(coded_date) = MONTH(CURDATE()) AND YEAR(coded_date) = YEAR(CURDATE())");
    $total_rekrut_bulan = (int)(mysqli_fetch_assoc($q_rekrut_bulan)['total'] ?? 0);

    // 4. Rekap Performa Poin Seluruh Agen (7 Hari Terakhir)
    $q_agen_eval = mysqli_query($conn, "
        SELECT u.id, u.nama_lengkap, u.no_lisensi, u.status,
               COALESCE(SUM(a.poin), 0) as total_poin,
               COUNT(a.id) as total_kegiatan
        FROM users u
        LEFT JOIN activity_tracking a ON u.id = a.user_id AND a.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        WHERE u.role = 'Agen'
        GROUP BY u.id
        ORDER BY u.status ASC, total_poin ASC, u.nama_lengkap ASC
    ");

    // 5. Log Aktivitas Terkini Seluruh Agensi
    $q_all_recent = mysqli_query($conn, "
        SELECT a.*, u.nama_lengkap 
        FROM activity_tracking a 
        JOIN users u ON a.user_id = u.id 
        ORDER BY a.tanggal DESC, a.id DESC 
        LIMIT 6
    ");
}
?>

<!-- HERO WELCOME BANNER -->
<div class="row mb-3 mb-md-4">
    <div class="col-12">
        <div class="card card-custom p-3 p-md-4 bg-white" style="background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%); border-left: 5px solid #00a758;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-2 py-1 rounded-pill" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-circle-check me-1"></i> Portal Aktif
                        </span>
                        <span class="text-muted small">&bull; GA Office Manulife LKG MW</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1 fs-5">Selamat Datang, <?= htmlspecialchars($nama_user); ?>!</h5>
                    <p class="text-muted mb-0 small">
                        <?php if ($user_role === 'Agen'): ?>
                            Pantau capaian target 20 poin mingguan dan pastikan presensi harian Anda tercatat tepat waktu.
                        <?php else: ?>
                            Dasbor monitoring operasional agensi, presensi harian, dan evaluasi capaian aktivitas agen pemasar.
                        <?php endif; ?>
                    </p>
                </div>
                <!-- QUICK ACTIONS RESPONSIVE -->
                <div class="d-flex flex-wrap gap-2 w-100 w-md-auto">
                    <?php if ($user_role === 'Agen'): ?>
                        <a href="index.php?page=presensi" class="btn btn-manulife btn-sm px-3 py-2 d-inline-flex align-items-center justify-content-center gap-2 shadow-sm flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-camera"></i> <span>Presensi Sekarang</span>
                        </a>
                        <a href="index.php?page=activity" class="btn btn-outline-success btn-sm px-3 py-2 d-inline-flex align-items-center justify-content-center gap-2 bg-white shadow-sm flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-pen-to-square"></i> <span>Catat Aktivitas</span>
                        </a>
                    <?php else: ?>
                        <a href="index.php?page=kelola_agen" class="btn btn-manulife btn-sm px-3 py-2 d-inline-flex align-items-center justify-content-center gap-2 shadow-sm flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-user-plus"></i> <span>Kelola Agen</span>
                        </a>
                        <a href="index.php?page=rekrut" class="btn btn-outline-success btn-sm px-3 py-2 d-inline-flex align-items-center justify-content-center gap-2 bg-white shadow-sm flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-clipboard-list"></i> <span>Monitoring Rekrut</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($user_role === 'Agen'): ?>
    <!-- ======================================================== -->
    <!-- TAMPILAN DASHBOARD KHUSUS AGEN                           -->
    <!-- ======================================================== -->
    <div class="row g-3 g-md-4 mb-3 mb-md-4">
        <!-- Card 1: Status Presensi Hari Ini -->
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card card-custom p-3 p-md-4 h-100 border-start border-4 <?= $presensi_today ? 'border-success' : 'border-warning'; ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem; letter-spacing: 0.5px;">Presensi Hari Ini</small>
                        <h5 class="fw-bold mt-1 mb-0 fs-6 <?= $presensi_today ? 'text-success' : 'text-warning'; ?>">
                            <?= $presensi_today ? htmlspecialchars($presensi_today['status_kehadiran']) : 'Belum Presensi'; ?>
                        </h5>
                    </div>
                    <div class="p-2 rounded-circle <?= $presensi_today ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning'; ?>">
                        <i class="fa-solid <?= $presensi_today ? 'fa-user-check' : 'fa-clock'; ?>"></i>
                    </div>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <?php if ($presensi_today): ?>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <i class="fa-regular fa-clock me-1"></i> Jam <?= date('H:i', strtotime($presensi_today['tanggal_waktu'])); ?> WIB
                        </small>
                    <?php else: ?>
                        <a href="index.php?page=presensi" class="text-decoration-none small text-warning fw-semibold" style="font-size: 0.78rem;">
                            Ambil presensi sekarang <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card 2: Poin 7 Hari Terakhir -->
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card card-custom p-3 p-md-4 h-100 border-start border-4 <?= $total_poin_minggu >= $target_mingguan ? 'border-success' : 'border-danger'; ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem; letter-spacing: 0.5px;">Poin 7 Hari Terakhir</small>
                        <h4 class="fw-bold mt-1 mb-0 text-dark fs-5">
                            <?= $total_poin_minggu; ?> <span class="fs-6 text-muted fw-normal">/ <?= $target_mingguan; ?> Poin</span>
                        </h4>
                    </div>
                    <div class="p-2 rounded-circle <?= $total_poin_minggu >= $target_mingguan ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger'; ?>">
                        <i class="fa-solid fa-award"></i>
                    </div>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <small class="<?= $total_poin_minggu >= $target_mingguan ? 'text-success' : 'text-danger'; ?> fw-medium" style="font-size: 0.75rem;">
                        <?php if ($total_poin_minggu >= $target_mingguan): ?>
                            <i class="fa-solid fa-circle-check me-1"></i> Target tercapai! Performa mantap.
                        <?php else: ?>
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Kurang <strong><?= $sisa_poin; ?> Poin</strong> lagi.
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Card 3: Aktivitas Mingguan -->
        <div class="col-12 col-sm-12 col-lg-4">
            <div class="card card-custom p-3 p-md-4 h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem; letter-spacing: 0.5px;">Aktivitas Tercatat</small>
                        <h4 class="fw-bold mt-1 mb-0 text-dark fs-5"><?= $total_akt_minggu; ?> <span class="fs-6 text-muted fw-normal">Kegiatan</span></h4>
                    </div>
                    <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <a href="index.php?page=activity" class="text-decoration-none small text-primary fw-semibold" style="font-size: 0.78rem;">
                        Lihat catatan aktivitas <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- TARGET MONITORING PROGRESS BAR -->
    <div class="row g-3 g-md-4 mb-3 mb-md-4">
        <div class="col-12 col-lg-8">
            <div class="card card-custom p-3 p-md-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-chart-line text-success me-2"></i>Progress Target Mingguan (20 Poin)
                    </h6>
                    <span class="badge <?= $persen_target >= 100 ? 'bg-success' : 'bg-primary'; ?> px-2 py-1 rounded-pill small">
                        <?= $persen_target; ?>%
                    </span>
                </div>
                <p class="text-muted small mb-3">Kriteria: Tele-polling (1 Poin), Janji Temu (3), Presentasi (4), Closing Polis (5).</p>
                
                <div class="progress mb-2" style="height: 16px; border-radius: 10px; background-color: #f1f5f9;">
                    <div class="progress-bar <?= $persen_target >= 100 ? 'bg-success' : 'bg-primary'; ?> progress-bar-striped progress-bar-animated fw-bold small" 
                         role="progressbar" 
                         style="width: <?= $persen_target; ?>%;">
                        <?= $total_poin_minggu; ?> Poin
                    </div>
                </div>

                <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                    <span>0 Poin</span>
                    <span class="fw-bold text-dark">Target: 20 Poin</span>
                    <span>30+ Poin</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <?php if ($total_poin_minggu < $target_mingguan): ?>
                <div class="card card-custom p-3 p-md-4 bg-warning bg-opacity-10 border border-warning h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
                        <h6 class="fw-bold mb-0 text-dark fs-6">Perlu Tambahan Poin</h6>
                    </div>
                    <p class="small text-secondary mb-3" style="font-size: 0.8rem;">
                        Poin aktivitas Anda <strong><?= $total_poin_minggu; ?> Poin</strong>. Kejar sisa <strong><?= $sisa_poin; ?> poin</strong> sebelum evaluasi mingguan.
                    </p>
                    <div class="mt-auto">
                        <a href="index.php?page=activity" class="btn btn-warning btn-sm w-100 fw-semibold text-dark">
                            <i class="fa-solid fa-plus me-1"></i> Tambah Aktivitas
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card card-custom p-3 p-md-4 bg-success bg-opacity-10 border border-success h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-circle-check text-success fs-5"></i>
                        <h6 class="fw-bold mb-0 text-dark fs-6">Performa Sangat Baik!</h6>
                    </div>
                    <p class="small text-secondary mb-3" style="font-size: 0.8rem;">
                        Selamat! Target poin mingguan kantor (20 Poin) telah berhasil Anda penuhi.
                    </p>
                    <div class="mt-auto">
                        <span class="badge bg-success w-100 py-2 fs-6"><i class="fa-solid fa-star me-1"></i> Agen Produktif</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABEL 5 AKTIVITAS TERAKHIR SAYA -->
    <div class="row">
        <div class="col-12">
            <div class="card card-custom p-3 p-md-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0 fs-6"><i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Aktivitas Terbaru Saya</h6>
                    <a href="index.php?page=activity" class="btn btn-xs btn-outline-success px-2 py-1 small">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <div class="d-block d-md-none text-muted mb-2 small"><i class="fa-solid fa-arrows-left-right text-success me-1"></i> Geser tabel untuk kolom lengkap</div>
                    <table class="table table-hover align-middle mb-0" style="min-width: 480px;">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Aktivitas</th>
                                <th>Nama Klien</th>
                                <th>Keterangan</th>
                                <th class="text-center">Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_recent_akt && mysqli_num_rows($q_recent_akt) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($q_recent_akt)): ?>
                                    <?php
                                        $badge_class = 'bg-secondary';
                                        if ($row['kategori_aktivitas'] === 'Closing') $badge_class = 'bg-success';
                                        elseif ($row['kategori_aktivitas'] === 'Presentasi') $badge_class = 'bg-warning text-dark';
                                        elseif ($row['kategori_aktivitas'] === 'Janji Temu') $badge_class = 'bg-primary';
                                        elseif ($row['kategori_aktivitas'] === 'Tele-polling') $badge_class = 'bg-info text-dark';
                                    ?>
                                    <tr>
                                        <td class="text-muted small"><i class="fa-regular fa-calendar me-1"></i><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
                                        <td><span class="badge <?= $badge_class; ?> rounded-pill px-2 py-1 small"><?= htmlspecialchars($row['kategori_aktivitas']); ?></span></td>
                                        <td class="fw-semibold text-dark small"><?= htmlspecialchars($row['nama_klien']); ?></td>
                                        <td><small class="text-secondary"><?= htmlspecialchars($row['deskripsi_kegiatan']); ?></small></td>
                                        <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1 small">+<?= $row['poin']; ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-file-circle-xmark fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                        Belum ada aktivitas yang dicatat minggu ini.
                                        <div class="mt-2">
                                            <a href="index.php?page=activity" class="btn btn-sm btn-manulife"><i class="fa-solid fa-plus me-1"></i> Catat Sekarang</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ======================================================== -->
    <!-- TAMPILAN DASHBOARD KHUSUS ADMIN / GA OWNER               -->
    <!-- ======================================================== -->
    <div class="row g-3 g-md-4 mb-3 mb-md-4">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card card-custom p-3 p-md-4 h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Agen Pemasar</small>
                        <h4 class="fw-bold mt-1 mb-0 text-dark fs-5"><?= $total_agen; ?> <span class="fs-6 text-muted fw-normal">Agen</span></h4>
                    </div>
                    <div class="p-2 rounded-circle bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <a href="index.php?page=kelola_agen" class="text-decoration-none small text-success fw-semibold" style="font-size: 0.78rem;">
                        Kelola data agen <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card card-custom p-3 p-md-4 h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem; letter-spacing: 0.5px;">Presensi Masuk Hari Ini</small>
                        <h4 class="fw-bold mt-1 mb-0 text-dark fs-5"><?= $total_hadir_today; ?> <span class="fs-6 text-muted fw-normal">/ <?= $total_agen; ?> Hadir</span></h4>
                    </div>
                    <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <a href="index.php?page=presensi" class="text-decoration-none small text-primary fw-semibold" style="font-size: 0.78rem;">
                        Cek absensi agen <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-12 col-lg-4">
            <div class="card card-custom p-3 p-md-4 h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem; letter-spacing: 0.5px;">Kandidat Rekrut (Bulan Ini)</small>
                        <h4 class="fw-bold mt-1 mb-0 text-dark fs-5"><?= $total_rekrut_bulan; ?> <span class="fs-6 text-muted fw-normal">Kandidat</span></h4>
                    </div>
                    <div class="p-2 rounded-circle bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <a href="index.php?page=rekrut" class="text-decoration-none small text-warning fw-semibold" style="font-size: 0.78rem;">
                        Monitoring rekrutmen <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- EVALUASI AGEN PERLU COACHING & LOG AKTIVITAS -->
    <div class="row g-3 g-md-4">
        <div class="col-12 col-lg-7">
            <div class="card card-custom p-3 p-md-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 fs-6"><i class="fa-solid fa-clipboard-check text-success me-2"></i>Capaian Poin Mingguan Agen</h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Target minimal agensi: 20 Poin/Minggu</small>
                    </div>
                </div>
                <div class="table-responsive">
                    <div class="d-block d-md-none text-muted mb-2 small"><i class="fa-solid fa-arrows-left-right text-success me-1"></i> Geser tabel untuk evaluasi lengkap</div>
                    <table class="table table-hover align-middle mb-0" style="min-width: 420px;">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Agen</th>
                                <th>No. Lisensi</th>
                                <th class="text-center">Total Poin</th>
                                <th class="text-center">Status Evaluasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_agen_eval && mysqli_num_rows($q_agen_eval) > 0): ?>
                                <?php while ($agen = mysqli_fetch_assoc($q_agen_eval)): ?>
                                    <?php 
                                        $is_active = (($agen['status'] ?? 'Aktif') === 'Aktif');
                                        $is_warning = (int)$agen['total_poin'] < 20; 
                                    ?>
                                    <tr class="<?= !$is_active ? 'opacity-75 table-light' : ''; ?>">
                                        <td class="fw-semibold text-dark small">
                                            <?= htmlspecialchars($agen['nama_lengkap']); ?>
                                            <?php if (!$is_active): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger ms-1" style="font-size: 0.68rem;">Non-Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-secondary font-monospace small"><?= htmlspecialchars($agen['no_lisensi'] ?: '-'); ?></span></td>
                                        <td class="text-center">
                                            <span class="fw-bold small <?= $is_warning ? 'text-danger' : 'text-success'; ?>">
                                                <?= (int)$agen['total_poin']; ?> / 20
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!$is_active): ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle rounded-pill px-2 py-1 small">
                                                    <i class="fa-solid fa-ban me-1"></i> Akun Ditutup
                                                </span>
                                            <?php elseif ($is_warning): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-2 py-1 small">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Coaching
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2 py-1 small">
                                                    <i class="fa-solid fa-check me-1"></i> Memenuhi
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-3 small">Belum ada akun agen terdaftar.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card card-custom p-3 p-md-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0 fs-6"><i class="fa-solid fa-bolt text-warning me-2"></i>Feed Aktivitas Agensi</h6>
                    <a href="index.php?page=activity" class="small text-success text-decoration-none fw-semibold">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="min-width: 320px;">
                        <tbody>
                            <?php if ($q_all_recent && mysqli_num_rows($q_all_recent) > 0): ?>
                                <?php while ($akt = mysqli_fetch_assoc($q_all_recent)): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold d-block text-dark small text-truncate" style="max-width: 180px;"><?= htmlspecialchars($akt['nama_lengkap']); ?></span>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($akt['kategori_aktivitas']); ?> &bull; <?= htmlspecialchars($akt['nama_klien']); ?></small>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold small">+<?= $akt['poin']; ?> Poin</span>
                                            <small class="text-muted d-block" style="font-size: 0.7rem;"><?= date('d/m', strtotime($akt['tanggal'])); ?></small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td class="text-center text-muted py-4 small">Belum ada aktivitas agen tercatat.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>