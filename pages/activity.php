<?php
require_once 'config/database.php';
$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Agen';

$bobot_poin = [
    'Tele-polling' => 1, 
    'Janji Temu'   => 3, 
    'Presentasi'   => 4, 
    'Closing'      => 5
];

$alert_message = '';

// PROSES SIMPAN AKTIVITAS
if (isset($_POST['submit_activity'])) {
    $tanggal            = !empty($_POST['tanggal']) ? mysqli_real_escape_string($conn, $_POST['tanggal']) : date('Y-m-d');
    $kategori_aktivitas = mysqli_real_escape_string($conn, $_POST['kategori_aktivitas']);
    $nama_klien         = mysqli_real_escape_string($conn, trim($_POST['nama_klien']));
    $deskripsi_kegiatan = mysqli_real_escape_string($conn, trim($_POST['deskripsi_kegiatan']));
    $hasil_aktivitas    = mysqli_real_escape_string($conn, trim($_POST['hasil_aktivitas']));
    $poin               = $bobot_poin[$kategori_aktivitas] ?? 0;

    $query = "INSERT INTO activity_tracking (user_id, tanggal, kategori_aktivitas, nama_klien, deskripsi_kegiatan, hasil_aktivitas, poin) 
              VALUES ('$user_id', '$tanggal', '$kategori_aktivitas', '$nama_klien', '$deskripsi_kegiatan', '$hasil_aktivitas', '$poin')";
    
    if (mysqli_query($conn, $query)) {
        $alert_message = "<div class='alert alert-success alert-dismissible fade show shadow-sm' role='alert'>
            <i class='fa-solid fa-circle-check me-2'></i><strong>Aktivitas Berhasil Dicatat!</strong> Anda memperoleh tambahan <strong>+$poin Poin</strong> untuk capaian target mingguan.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    } else {
        $alert_message = "<div class='alert alert-danger'>Gagal menyimpan: " . mysqli_error($conn) . "</div>";
    }
}

// PROSES HAPUS AKTIVITAS (Jika ada kesalahan entri)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $del_id = mysqli_real_escape_string($conn, $_GET['id']);
    $del_query = ($user_role === 'Admin') 
        ? "DELETE FROM activity_tracking WHERE id='$del_id'"
        : "DELETE FROM activity_tracking WHERE id='$del_id' AND user_id='$user_id'";
    
    if (mysqli_query($conn, $del_query)) {
        $alert_message = "<div class='alert alert-info alert-dismissible fade show'>Catatan aktivitas berhasil dihapus. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// HITUNG METRIK MINGGUAN AGEN
$query_poin = mysqli_query($conn, "SELECT SUM(poin) as total_poin, COUNT(*) as total_akt FROM activity_tracking WHERE user_id = '$user_id' AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$data_poin  = mysqli_fetch_assoc($query_poin);
$total_poin_minggu = (int)($data_poin['total_poin'] ?? 0);
$total_akt_minggu  = (int)($data_poin['total_akt'] ?? 0);
$target_mingguan   = 20;
$persen_target     = min(100, round(($total_poin_minggu / $target_mingguan) * 100));

// FILTER DATA AKTIVITAS
$search_klien = $_GET['search_klien'] ?? '';
$filter_kat   = $_GET['filter_kat'] ?? '';
$filter_agen  = $_GET['filter_agen'] ?? '';

$where = [];
if (!empty($search_klien)) {
    $where[] = "a.nama_klien LIKE '%" . mysqli_real_escape_string($conn, $search_klien) . "%'";
}
if (!empty($filter_kat)) {
    $where[] = "a.kategori_aktivitas = '" . mysqli_real_escape_string($conn, $filter_kat) . "'";
}

if ($user_role === 'Admin') {
    if (!empty($filter_agen)) {
        $where[] = "a.user_id = '" . mysqli_real_escape_string($conn, $filter_agen) . "'";
    }
    $where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";
    $data_activity = mysqli_query($conn, "SELECT a.*, u.nama_lengkap FROM activity_tracking a JOIN users u ON a.user_id = u.id $where_sql ORDER BY a.tanggal DESC, a.id DESC");
    
    $list_agen = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE role = 'Agen' ORDER BY nama_lengkap ASC");
} else {
    // Agen hanya melihat aktivitas miliknya sendiri (Kerahasiaan Prospek Klien)
    $where[] = "a.user_id = '$user_id'";
    $where_sql = "WHERE " . implode(' AND ', $where);
    $data_activity = mysqli_query($conn, "SELECT a.*, u.nama_lengkap FROM activity_tracking a JOIN users u ON a.user_id = u.id $where_sql ORDER BY a.tanggal DESC, a.id DESC");
}
?>

<?= $alert_message; ?>

<!-- PROGRESS SUMMARY CAPAIAN TARGET 20 POIN -->
<?php if ($user_role === 'Agen'): ?>
<div class="row mb-3 mb-md-4">
    <div class="col-12">
        <div class="card card-custom p-3 p-md-4 bg-white border-top border-4 <?= $total_poin_minggu >= $target_mingguan ? 'border-success' : 'border-warning'; ?>">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-7">
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <span class="badge <?= $total_poin_minggu >= $target_mingguan ? 'bg-success' : 'bg-warning text-dark'; ?> rounded-pill px-3 py-1 small">
                            <i class="fa-solid fa-award me-1"></i> Target Mingguan: 20 Poin
                        </span>
                        <small class="text-muted" style="font-size: 0.75rem;">(7 Hari Terakhir)</small>
                    </div>
                    <h6 class="fw-bold mb-2 text-dark fs-6">
                        Capaian Anda Saat Ini: <span class="<?= $total_poin_minggu >= $target_mingguan ? 'text-success' : 'text-primary'; ?>"><?= $total_poin_minggu; ?> / 20 Poin</span>
                    </h6>
                    <div class="progress" style="height: 14px; border-radius: 8px;">
                        <div class="progress-bar <?= $total_poin_minggu >= $target_mingguan ? 'bg-success' : 'bg-primary'; ?> progress-bar-striped progress-bar-animated small" 
                             style="width: <?= $persen_target; ?>%;">
                            <?= $persen_target; ?>%
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-md-end">
                    <?php if ($total_poin_minggu >= $target_mingguan): ?>
                        <div class="d-inline-block text-start p-2 px-3 bg-success bg-opacity-10 rounded-3 border border-success-subtle w-100 w-md-auto">
                            <span class="text-success fw-bold d-block small"><i class="fa-solid fa-circle-check me-1"></i> Target Terpenuhi</span>
                            <small class="text-secondary" style="font-size: 0.75rem;">Pertahankan konsistensi closing & presentasi Anda.</small>
                        </div>
                    <?php else: ?>
                        <div class="d-inline-block text-start p-2 px-3 bg-warning bg-opacity-10 rounded-3 border border-warning-subtle w-100 w-md-auto">
                            <span class="text-dark fw-bold d-block small"><i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> Kurang <?= 20 - $total_poin_minggu; ?> Poin Lagi</span>
                            <small class="text-secondary" style="font-size: 0.75rem;">Yuk tambah interaksi prospek untuk hindari warning pimpinan.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 g-md-4">
    <!-- FORM INPUT AKTIVITAS -->
    <div class="col-12 col-lg-5">
        <div class="card card-custom p-3 p-md-4 bg-white">
            <h6 class="fw-bold mb-3 text-dark border-bottom pb-2 fs-6">
                <i class="fa-solid fa-pen-to-square text-success me-2"></i>Catat Aktivitas Prospek
            </h6>
            <form action="" method="POST">
                <!-- Tanggal Aktivitas -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Tanggal Pelaksanaan</label>
                    <input type="date" name="tanggal" class="form-control form-control-sm" 
                           value="<?= date('Y-m-d'); ?>" 
                           max="<?= date('Y-m-d'); ?>" 
                           min="<?= date('Y-m-d', strtotime('-7 days')); ?>" required>
                    <small class="text-muted" style="font-size: 0.72rem;">*Bisa mencatat aktivitas maksimal 7 hari ke belakang.</small>
                </div>

                <!-- Kategori Kegiatan -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Jenis Kegiatan & Bobot Poin</label>
                    <select name="kategori_aktivitas" class="form-select form-select-sm" required>
                        <option value="Tele-polling">Tele-polling / Sapa Prospek (+1 Poin)</option>
                        <option value="Janji Temu">Janji Temu / Meeting Perdana (+3 Poin)</option>
                        <option value="Presentasi">Presentasi Program & Ilustrasi (+4 Poin)</option>
                        <option value="Closing">Closing Polis & Submit Berkas (+5 Poin)</option>
                    </select>
                </div>

                <!-- Nama Klien -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Nama Calon Nasabah / Klien</label>
                    <input type="text" name="nama_klien" class="form-control form-control-sm" placeholder="Contoh: Bpk. Hendra Sitorus" required>
                </div>

                <!-- Deskripsi Kegiatan -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Deskripsi & Agenda Kegiatan</label>
                    <textarea name="deskripsi_kegiatan" class="form-control form-control-sm" rows="3" placeholder="Jelaskan kebutuhan nasabah, topik diskusi, atau program asuransi yang dibahas..." required></textarea>
                </div>

                <!-- Tindak Lanjut -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Hasil / Rencana Tindak Lanjut (Follow-up)</label>
                    <input type="text" name="hasil_aktivitas" class="form-control form-control-sm" placeholder="Contoh: Janji kirim ilustrasi besok jam 14.00 WIB">
                </div>

                <button type="submit" name="submit_activity" class="btn btn-manulife w-100 py-2 shadow-sm d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Simpan & Perbarui Poin
                </button>
            </form>
        </div>
    </div>

    <!-- TABEL RIWAYAT AKTIVITAS -->
    <div class="col-12 col-lg-7">
        <div class="card card-custom p-3 p-md-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark fs-6">
                        <i class="fa-solid fa-list-check text-success me-2"></i>
                        <?= $user_role === 'Admin' ? 'Audit Seluruh Aktivitas Agen' : 'Catatan Aktivitas Saya'; ?>
                    </h6>
                    <small class="text-muted" style="font-size: 0.75rem;">
                        <?= $user_role === 'Admin' ? 'Pantau pergerakan dan aktivitas prospek tim agensi' : 'Daftar interaksi nasabah yang telah Anda rekam'; ?>
                    </small>
                </div>
            </div>

            <!-- SEARCH & FILTER BAR RESPONSIVE -->
            <form action="index.php" method="GET" class="row g-2 mb-3 p-2 bg-light rounded-3 border">
                <input type="hidden" name="page" value="activity">
                <?php if ($user_role === 'Admin'): ?>
                    <div class="col-12 col-sm-6 col-md-4">
                        <select name="filter_agen" class="form-select form-select-sm">
                            <option value="">-- Semua Agen --</option>
                            <?php if ($list_agen): while ($a = mysqli_fetch_assoc($list_agen)): ?>
                                <option value="<?= $a['id']; ?>" <?= ($filter_agen == $a['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($a['nama_lengkap']); ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-12 col-sm-6 <?= $user_role === 'Admin' ? 'col-md-3' : 'col-md-5'; ?>">
                    <input type="text" name="search_klien" class="form-control form-control-sm" placeholder="Cari nama klien..." value="<?= htmlspecialchars($search_klien); ?>">
                </div>

                <div class="col-6 col-sm-6 <?= $user_role === 'Admin' ? 'col-md-3' : 'col-md-4'; ?>">
                    <select name="filter_kat" class="form-select form-select-sm">
                        <option value="">-- Kategori --</option>
                        <option value="Tele-polling" <?= $filter_kat === 'Tele-polling' ? 'selected' : ''; ?>>Tele-polling (1)</option>
                        <option value="Janji Temu" <?= $filter_kat === 'Janji Temu' ? 'selected' : ''; ?>>Janji Temu (3)</option>
                        <option value="Presentasi" <?= $filter_kat === 'Presentasi' ? 'selected' : ''; ?>>Presentasi (4)</option>
                        <option value="Closing" <?= $filter_kat === 'Closing' ? 'selected' : ''; ?>>Closing Polis (5)</option>
                    </select>
                </div>

                <div class="col-6 col-sm-6 <?= $user_role === 'Admin' ? 'col-md-2' : 'col-md-3'; ?> d-flex gap-1">
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fa-solid fa-search"></i></button>
                    <a href="index.php?page=activity" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>

            <div class="table-responsive">
                <div class="d-block d-md-none text-muted mb-2 small"><i class="fa-solid fa-arrows-left-right text-success me-1"></i> Geser tabel ke samping untuk melihat rincian aktivitas</div>
                <table class="table table-hover align-middle mb-0" style="min-width: 500px;">
                    <thead class="table-light">
                        <tr>
                            <?php if ($user_role === 'Admin'): ?><th>Agen</th><?php endif; ?>
                            <th>Tanggal & Jenis</th>
                            <th>Klien & Keterangan</th>
                            <th class="text-center">Poin</th>
                            <th class="text-center text-nowrap" style="width: 60px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($data_activity && mysqli_num_rows($data_activity) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($data_activity)): ?>
                                <?php
                                    $k_badge = 'bg-secondary';
                                    $k_icon  = 'fa-circle-dot';
                                    if ($row['kategori_aktivitas'] === 'Closing') {
                                        $k_badge = 'bg-success text-white';
                                        $k_icon  = 'fa-trophy';
                                    } elseif ($row['kategori_aktivitas'] === 'Presentasi') {
                                        $k_badge = 'bg-warning text-dark';
                                        $k_icon  = 'fa-file-powerpoint';
                                    } elseif ($row['kategori_aktivitas'] === 'Janji Temu') {
                                        $k_badge = 'bg-primary text-white';
                                        $k_icon  = 'fa-handshake';
                                    } elseif ($row['kategori_aktivitas'] === 'Tele-polling') {
                                        $k_badge = 'bg-info text-dark';
                                        $k_icon  = 'fa-phone';
                                    }
                                ?>
                                <tr>
                                    <?php if ($user_role === 'Admin'): ?>
                                        <td>
                                            <span class="fw-bold small d-block"><?= htmlspecialchars($row['nama_lengkap']); ?></span>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;"><i class="fa-regular fa-calendar me-1"></i><?= date('d M Y', strtotime($row['tanggal'])); ?></small>
                                        <span class="badge <?= $k_badge; ?> rounded-pill px-2 py-1 mt-1 small">
                                            <i class="fa-solid <?= $k_icon; ?> me-1"></i> <?= $row['kategori_aktivitas']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($row['nama_klien']); ?></div>
                                        <small class="text-secondary d-block" style="font-size: 0.75rem;"><?= htmlspecialchars($row['deskripsi_kegiatan']); ?></small>
                                        <?php if (!empty($row['hasil_aktivitas'])): ?>
                                            <small class="text-primary d-block mt-1" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-arrow-turn-down-right me-1"></i><strong>Follow-up:</strong> <?= htmlspecialchars($row['hasil_aktivitas']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success bg-opacity-10 text-success fs-6 fw-bold px-2 py-1 small">
                                            +<?= $row['poin']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap" style="width: 60px;">
                                        <a href="index.php?page=activity&action=delete&id=<?= $row['id']; ?>" 
                                           class="btn btn-action-icon btn-outline-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus catatan aktivitas ini? Poin akan disesuaikan kembali.');" 
                                           title="Hapus Catatan">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $user_role === 'Admin' ? '5' : '4'; ?>" class="text-center text-muted py-4 small">
                                    <i class="fa-solid fa-folder-open fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                    Belum ada catatan aktivitas prospek yang sesuai kriteria pencarian.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>