<?php
require_once 'config/database.php';

// Proteksi role khusus Admin
if (($_SESSION['role'] ?? '') !== 'Admin') {
    echo "<div class='alert alert-danger'>Akses ditolak! Halaman ini hanya untuk Staf Administrasi.</div>";
    exit;
}

$alert_message = '';

// 1. PROSES TAMBAH AGEN BARU
if (isset($_POST['tambah_agen'])) {
    $nama       = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $no_lisensi = mysqli_real_escape_string($conn, trim($_POST['no_lisensi']));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email']));
    $status     = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Aktif');
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role       = 'Agen';

    $check = mysqli_query($conn, "SELECT id FROM users WHERE no_lisensi = '$no_lisensi'");
    if (mysqli_num_rows($check) > 0) {
        $alert_message = "<div class='alert alert-warning alert-dismissible fade show'><i class='fa-solid fa-triangle-exclamation me-2'></i>Nomor Lisensi <strong>$no_lisensi</strong> sudah terdaftar! Gunakan nomor lisensi lain.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $query = "INSERT INTO users (nama_lengkap, email, no_lisensi, password, role, status) VALUES ('$nama', '$email', '$no_lisensi', '$password', '$role', '$status')";
        if (mysqli_query($conn, $query)) {
            $alert_message = "<div class='alert alert-success alert-dismissible fade show'><i class='fa-solid fa-circle-check me-2'></i>Akun Agen <strong>$nama</strong> berhasil dibuat dengan status <strong>$status</strong>!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $alert_message = "<div class='alert alert-danger'>Gagal menyimpan: " . mysqli_error($conn) . "</div>";
        }
    }
}

// 2. PROSES UPDATE/EDIT AGEN & PASSWORD & STATUS
if (isset($_POST['update_agen'])) {
    $id         = mysqli_real_escape_string($conn, $_POST['id']);
    $nama       = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $no_lisensi = mysqli_real_escape_string($conn, trim($_POST['no_lisensi']));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email']));
    $status     = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Aktif');
    $new_pass   = $_POST['new_password'];

    if (!empty($new_pass)) {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $query = "UPDATE users SET nama_lengkap='$nama', no_lisensi='$no_lisensi', email='$email', status='$status', password='$hashed_pass' WHERE id='$id'";
    } else {
        $query = "UPDATE users SET nama_lengkap='$nama', no_lisensi='$no_lisensi', email='$email', status='$status' WHERE id='$id'";
    }

    if (mysqli_query($conn, $query)) {
        $alert_message = "<div class='alert alert-success alert-dismissible fade show'><i class='fa-solid fa-circle-check me-2'></i>Data akun <strong>$nama</strong> berhasil diperbarui (Status: <strong>$status</strong>)!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $alert_message = "<div class='alert alert-danger'>Gagal memperbarui: " . mysqli_error($conn) . "</div>";
    }
}

// 3. PROSES TOGGLE STATUS CEPAT (AKTIF / NON-AKTIF)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $q_cur = mysqli_query($conn, "SELECT status, nama_lengkap FROM users WHERE id='$id' AND role='Agen'");
    if ($q_cur && mysqli_num_rows($q_cur) > 0) {
        $u_cur = mysqli_fetch_assoc($q_cur);
        $new_st = ($u_cur['status'] === 'Aktif') ? 'Non-Aktif' : 'Aktif';
        if (mysqli_query($conn, "UPDATE users SET status='$new_st' WHERE id='$id'")) {
            $alert_message = "<div class='alert alert-success alert-dismissible fade show'><i class='fa-solid fa-circle-check me-2'></i>Status akun <strong>" . htmlspecialchars($u_cur['nama_lengkap']) . "</strong> berhasil diubah menjadi <strong>$new_st</strong>." . ($new_st === 'Non-Aktif' ? ' Akun ini tidak dapat login ke portal.' : ' Akun ini dapat login kembali.') . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// 4. PROSES HAPUS AGEN
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    if (mysqli_query($conn, "DELETE FROM users WHERE id='$id' AND role='Agen'")) {
        $alert_message = "<div class='alert alert-info alert-dismissible fade show'><i class='fa-solid fa-trash me-2'></i>Akun Agen berhasil dihapus dari sistem.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// FILTER PENCARIAN & STATUS
$search        = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$where_parts = ["role = 'Agen'"];
if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where_parts[] = "(nama_lengkap LIKE '%$s%' OR no_lisensi LIKE '%$s%' OR email LIKE '%$s%')";
}
if (!empty($filter_status)) {
    $st = mysqli_real_escape_string($conn, $filter_status);
    $where_parts[] = "status = '$st'";
}

$where_sql = "WHERE " . implode(' AND ', $where_parts);
$data_agen = mysqli_query($conn, "SELECT * FROM users $where_sql ORDER BY nama_lengkap ASC");
$total_agen_count = mysqli_num_rows($data_agen);

// HITUNG TOTAL AKTIF DAN NON-AKTIF
$q_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'Agen' AND status = 'Aktif'");
$total_aktif = (int)(mysqli_fetch_assoc($q_aktif)['total'] ?? 0);

$q_nonaktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'Agen' AND status = 'Non-Aktif'");
$total_nonaktif = (int)(mysqli_fetch_assoc($q_nonaktif)['total'] ?? 0);
?>

<?= $alert_message; ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 mb-md-4">
    <div>
        <h5 class="fw-bold mb-1 text-dark fs-5">
            <i class="fa-solid fa-users-gear text-success me-2"></i>Kelola Akun & Lisensi Agen
        </h5>
        <p class="text-muted small mb-0">Manajemen akun agen pemasar, pengaturan status aktif/non-aktif, izin akses login, dan reset kata sandi.</p>
    </div>
    <div class="w-100 w-md-auto">
        <button class="btn btn-manulife btn-sm px-3 py-2 shadow-sm w-100 w-md-auto d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#modalTambahAgen">
            <i class="fa-solid fa-user-plus me-2"></i> Buat Akun Agen Baru
        </button>
    </div>
</div>



<!-- STATISTIK CEPAT STATUS AGEN -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-4">
        <div class="card card-custom p-2 p-md-3 text-center bg-white border-start border-4 border-primary">
            <small class="text-muted fw-bold text-uppercase d-block text-truncate" style="font-size: 0.7rem;">Semua Agen</small>
            <h5 class="fw-bold text-dark mb-0 fs-6"><?= $total_aktif + $total_nonaktif; ?></h5>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-custom p-2 p-md-3 text-center bg-white border-start border-4 border-success">
            <small class="text-muted fw-bold text-uppercase d-block text-truncate" style="font-size: 0.7rem;">Akun Aktif</small>
            <h5 class="fw-bold text-success mb-0 fs-6"><?= $total_aktif; ?></h5>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-custom p-2 p-md-3 text-center bg-white border-start border-4 border-danger">
            <small class="text-muted fw-bold text-uppercase d-block text-truncate" style="font-size: 0.7rem;">Non-Aktif</small>
            <h5 class="fw-bold text-danger mb-0 fs-6"><?= $total_nonaktif; ?></h5>
        </div>
    </div>
</div>

<!-- SEARCH & FILTER STATUS BAR RESPONSIVE -->
<div class="card card-custom p-3 bg-white mb-3 mb-md-4">
    <form action="index.php" method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="page" value="kelola_agen">
        <div class="col-12 col-md-6">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="fa-solid fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari nama agen, no. lisensi, atau email..." value="<?= htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="filter_status" class="form-select form-select-sm">
                <option value="">-- Semua Status --</option>
                <option value="Aktif" <?= $filter_status === 'Aktif' ? 'selected' : ''; ?>>Hanya Aktif</option>
                <option value="Non-Aktif" <?= $filter_status === 'Non-Aktif' ? 'selected' : ''; ?>>Hanya Non-Aktif</option>
            </select>
        </div>
        <div class="col-6 col-md-3 d-flex gap-1">
            <button type="submit" class="btn btn-success btn-sm w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <?php if (!empty($search) || !empty($filter_status)): ?>
                <a href="index.php?page=kelola_agen" class="btn btn-secondary btn-sm" title="Reset Filter"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- TABEL DAFTAR AGEN -->
<div class="card card-custom p-3 p-md-4 bg-white">
    <div class="table-responsive">
        <div class="d-block d-md-none text-muted mb-2 small"><i class="fa-solid fa-arrows-left-right text-success me-1"></i> Geser tabel ke samping untuk melihat opsi akun lengkap</div>
        <table class="table table-hover align-middle mb-0" style="min-width: 660px;">
            <thead class="table-light">
                <tr>
                    <th style="width: 45px;">NO</th>
                    <th>NAMA AGEN</th>
                    <th>NO. LISENSI (LOGIN)</th>
                    <th>EMAIL</th>
                    <th class="text-center" style="width: 110px;">STATUS</th>
                    <th>TERDAFTAR</th>
                    <th class="text-center text-nowrap" style="width: 125px; min-width: 125px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data_agen && mysqli_num_rows($data_agen) > 0): ?>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($data_agen)): ?>
                        <?php
                            $photo_src = (!empty($row['profile_photo']) && file_exists($row['profile_photo'])) 
                                ? $row['profile_photo'] 
                                : "https://ui-avatars.com/api/?name=" . urlencode($row['nama_lengkap']) . "&background=00a758&color=ffffff&rounded=true&bold=true";
                            $is_aktif = ($row['status'] === 'Aktif');
                        ?>
                        <tr class="<?= !$is_aktif ? 'table-light opacity-75' : ''; ?>">
                            <td><?= $no++; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= htmlspecialchars($photo_src); ?>" alt="Avatar" width="36" height="36" class="rounded-circle shadow-sm flex-shrink-0" style="object-fit: cover; border: 2px solid <?= $is_aktif ? '#00a758' : '#cbd5e1'; ?>;">
                                    <div>
                                        <span class="fw-bold text-dark d-block small"><?= htmlspecialchars($row['nama_lengkap']); ?></span>
                                        <small class="badge bg-light text-secondary" style="font-size: 0.7rem;">Financial Consultant</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle font-monospace px-2 py-1 small">
                                    <i class="fa-solid fa-id-card me-1"></i><?= htmlspecialchars($row['no_lisensi'] ?? '-'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($row['email'])): ?>
                                    <small class="text-secondary" style="font-size: 0.78rem;"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($row['email']); ?></small>
                                <?php else: ?>
                                    <small class="text-muted fst-italic" style="font-size: 0.75rem;">Belum diisi</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($is_aktif): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2 py-1 small">
                                        <i class="fa-solid fa-circle-check me-1"></i>Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-2 py-1 small">
                                        <i class="fa-solid fa-circle-xmark me-1"></i>Non-Aktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted" style="font-size: 0.75rem;"><i class="fa-regular fa-calendar me-1"></i><?= date('d M Y', strtotime($row['created_at'])); ?></small>
                            </td>
                            <td class="text-center text-nowrap" style="width: 125px;">
                                <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                    <!-- Quick Toggle Status Button -->
                                    <?php if ($is_aktif): ?>
                                        <a href="index.php?page=kelola_agen&action=toggle_status&id=<?= $row['id']; ?>" 
                                           class="btn btn-action-icon btn-outline-danger" 
                                           onclick="return confirm('Konfirmasi: Nonaktifkan akun agen <?= htmlspecialchars(addslashes($row['nama_lengkap'])); ?>?\n\nAkun Non-Aktif TIDAK AKAN BISA LOGIN ke portal.');" 
                                           title="Nonaktifkan Akun (Cabut Izin Login)">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="index.php?page=kelola_agen&action=toggle_status&id=<?= $row['id']; ?>" 
                                           class="btn btn-action-icon btn-outline-success" 
                                           onclick="return confirm('Konfirmasi: Aktifkan kembali akun agen <?= htmlspecialchars(addslashes($row['nama_lengkap'])); ?>?\n\nAgen akan diberikan hak akses untuk login ke portal.');" 
                                           title="Aktifkan Akun (Beri Izin Login)">
                                            <i class="fa-solid fa-user-check"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Edit Modal Button -->
                                    <button type="button" class="btn btn-action-icon btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditAgen<?= $row['id']; ?>" title="Edit Data & Reset Sandi">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <a href="index.php?page=kelola_agen&action=delete&id=<?= $row['id']; ?>" 
                                       class="btn btn-action-icon btn-outline-secondary" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus akun agen <?= htmlspecialchars(addslashes($row['nama_lengkap'])); ?> secara permanen?');" 
                                       title="Hapus Akun Permanen">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- MODAL EDIT AGEN & RESET PASSWORD & STATUS -->
                        <div class="modal fade text-start" id="modalEditAgen<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header border-bottom py-2">
                                        <h6 class="modal-title fw-bold">Edit Akun: <?= htmlspecialchars($row['nama_lengkap']); ?></h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small text-secondary">Nama Lengkap</label>
                                                <input type="text" name="nama_lengkap" class="form-control form-control-sm" value="<?= htmlspecialchars($row['nama_lengkap']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small text-secondary">Nomor Lisensi AAJI (Login)</label>
                                                <input type="text" name="no_lisensi" class="form-control form-control-sm" value="<?= htmlspecialchars($row['no_lisensi']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small text-secondary">Alamat Email (Opsional)</label>
                                                <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($row['email'] ?? ''); ?>" placeholder="agen@manulife-lkg.com">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small text-secondary">Status Akses Akun</label>
                                                <select name="status" class="form-select form-select-sm" required>
                                                    <option value="Aktif" <?= $row['status'] === 'Aktif' ? 'selected' : ''; ?>>Aktif (Dapat Login ke Portal)</option>
                                                    <option value="Non-Aktif" <?= $row['status'] === 'Non-Aktif' ? 'selected' : ''; ?>>Non-Aktif (Diblokir / Ditolak Saat Login)</option>
                                                </select>
                                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">*Hanya akun berstatus Aktif yang dapat login ke portal.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small text-secondary">Reset Kata Sandi Baru</label>
                                                <input type="password" name="new_password" class="form-control form-control-sm" placeholder="Kosongkan jika tidak ingin mereset password">
                                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">*Isi kolom ini hanya jika agen lupa sandi.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top py-2">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="update_agen" class="btn btn-success btn-sm px-3">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4 small">
                            <i class="fa-solid fa-user-slash fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                            Tidak ditemukan data akun agen yang sesuai.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH AGEN BARU -->
<div class="modal fade" id="modalTambahAgen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-2">
                <h6 class="modal-title fw-bold text-dark"><i class="fa-solid fa-user-plus text-success me-2"></i>Buat Akun Agen Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Nama Lengkap Agen</label>
                        <input type="text" name="nama_lengkap" class="form-control form-control-sm" placeholder="Contoh: Budi Pratama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Nomor Lisensi AAJI (Digunakan untuk Login)</label>
                        <input type="text" name="no_lisensi" class="form-control form-control-sm" placeholder="Contoh: AAJI12345" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Email Kantor / Pribadi (Opsional)</label>
                        <input type="email" name="email" class="form-control form-control-sm" placeholder="budi@manulife-lkg.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Status Awal Akun</label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="Aktif" selected>Aktif (Bisa Langsung Login ke Portal)</option>
                            <option value="Non-Aktif">Non-Aktif (Belum Diizinkan Login)</option>
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">*Hanya akun berstatus Aktif yang dapat login ke portal.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Kata Sandi Awal</label>
                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Buat kata sandi awal..." required>
                        <small class="text-muted" style="font-size: 0.72rem;">Disarankan minimal 6 karakter kombinasi huruf dan angka.</small>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_agen" class="btn btn-manulife btn-sm px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan & Terbitkan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>