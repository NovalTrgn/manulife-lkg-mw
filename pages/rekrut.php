<?php
require_once 'config/database.php';
$user_role = $_SESSION['role'] ?? 'Agen';

$alert_message = '';

// SIMPAN DATA REKRUTMEN
if (isset($_POST['submit_rekrut']) && $user_role === 'Admin') {
    $nama     = mysqli_real_escape_string($conn, trim($_POST['nama_kandidat']));
    $level    = mysqli_real_escape_string($conn, $_POST['level']);
    $coded    = mysqli_real_escape_string($conn, $_POST['coded_date']);
    $nr       = mysqli_real_escape_string($conn, $_POST['nr']);
    $nra      = mysqli_real_escape_string($conn, trim($_POST['nra']));
    $perekrut = mysqli_real_escape_string($conn, trim($_POST['perekrut']));

    $query = "INSERT INTO monitoring_rekrut (nama_kandidat, level, coded_date, nr, nra, perekrut) 
              VALUES ('$nama', '$level', '$coded', '$nr', '$nra', '$perekrut')";
    
    if (mysqli_query($conn, $query)) {
        $alert_message = "<div class='alert alert-success alert-dismissible fade show shadow-sm' role='alert'>
            <i class='fa-solid fa-circle-check me-2'></i>Data kandidat rekrutmen <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan!
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    } else {
        $alert_message = "<div class='alert alert-danger'>Gagal menyimpan: " . mysqli_error($conn) . "</div>";
    }
}

// UPDATE DATA REKRUTMEN
if (isset($_POST['update_rekrut']) && $user_role === 'Admin') {
    $edit_id  = mysqli_real_escape_string($conn, $_POST['id']);
    $nama     = mysqli_real_escape_string($conn, trim($_POST['nama_kandidat']));
    $level    = mysqli_real_escape_string($conn, $_POST['level']);
    $coded    = mysqli_real_escape_string($conn, $_POST['coded_date']);
    $nr       = mysqli_real_escape_string($conn, $_POST['nr']);
    $nra      = mysqli_real_escape_string($conn, trim($_POST['nra']));
    $perekrut = mysqli_real_escape_string($conn, trim($_POST['perekrut']));

    $update_query = "UPDATE monitoring_rekrut SET nama_kandidat='$nama', level='$level', coded_date='$coded', nr='$nr', nra='$nra', perekrut='$perekrut' WHERE id='$edit_id'";
    if (mysqli_query($conn, $update_query)) {
        $alert_message = "<div class='alert alert-success alert-dismissible fade show shadow-sm'>Data kandidat berhasil diperbarui! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// HAPUS DATA REKRUTMEN
if (isset($_GET['action']) && $_GET['action'] === 'delete' && $user_role === 'Admin') {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    if (mysqli_query($conn, "DELETE FROM monitoring_rekrut WHERE id='$id'")) {
        $alert_message = "<div class='alert alert-info alert-dismissible fade show'>Data rekrutmen telah dihapus. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// FILTER PENCARIAN
$where = [];
if (!empty($_GET['search_nama'])) { $where[] = "nama_kandidat LIKE '%".mysqli_real_escape_string($conn, $_GET['search_nama'])."%'"; }
if (!empty($_GET['search_tgl'])) { $where[] = "DAY(coded_date) = '".mysqli_real_escape_string($conn, $_GET['search_tgl'])."'"; }
if (!empty($_GET['search_bulan'])) { $where[] = "MONTH(coded_date) = '".mysqli_real_escape_string($conn, $_GET['search_bulan'])."'"; }
if (!empty($_GET['search_tahun'])) { $where[] = "YEAR(coded_date) = '".mysqli_real_escape_string($conn, $_GET['search_tahun'])."'"; }
if (!empty($_GET['search_level'])) { $where[] = "level = '".mysqli_real_escape_string($conn, $_GET['search_level'])."'"; }

$sql = "SELECT * FROM monitoring_rekrut";
if (count($where) > 0) { $sql .= " WHERE " . implode(' AND ', $where); }
$sql .= " ORDER BY coded_date DESC, id DESC";
$data_rekrut = mysqli_query($conn, $sql);

// METRIK REKRUTMEN
$q_total_all = mysqli_query($conn, "SELECT COUNT(*) as total FROM monitoring_rekrut");
$total_kandidat = (int)(mysqli_fetch_assoc($q_total_all)['total'] ?? 0);

$q_total_tahun = mysqli_query($conn, "SELECT COUNT(*) as total FROM monitoring_rekrut WHERE YEAR(coded_date) = YEAR(CURDATE())");
$total_tahun = (int)(mysqli_fetch_assoc($q_total_tahun)['total'] ?? 0);

$q_total_bulan = mysqli_query($conn, "SELECT COUNT(*) as total FROM monitoring_rekrut WHERE MONTH(coded_date) = MONTH(CURDATE()) AND YEAR(coded_date) = YEAR(CURDATE())");
$total_bulan = (int)(mysqli_fetch_assoc($q_total_bulan)['total'] ?? 0);

// LIST PEREKRUT UNTUK SELECT
$list_perekrut = mysqli_query($conn, "SELECT DISTINCT nama_lengkap FROM users ORDER BY nama_lengkap ASC");
?>

<style>
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        .table-responsive { overflow: visible !important; }
        table { font-size: 11px !important; }
    }
    .print-only { display: none; }
</style>

<?= $alert_message; ?>

<!-- KOP SURAT KHUSUS PRINT -->
<div class="print-only text-center mb-4 pb-3 border-bottom border-dark border-2">
    <h4 class="fw-bold mb-0 text-uppercase">PT ASURANSI JIWA MANULIFE INDONESIA</h4>
    <h5 class="fw-bold mb-1">GENERAL AGENCY (GA) OFFICE LKG MW MEDAN</h5>
    <p class="small mb-0 text-muted">Laporan Rekapitulasi Data Monitoring Rekrutmen Agen Baru (Coded Period <?= date('Y'); ?>)</p>
    <small class="text-secondary">Dicetak pada: <?= date('d F Y, H:i'); ?> WIB</small>
</div>

<!-- HEADER HALAMAN -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 mb-md-4 no-print">
    <div>
        <h5 class="fw-bold mb-1 text-dark fs-5">
            <i class="fa-solid fa-user-plus text-success me-2"></i>Monitoring Rekrut Agen Baru
        </h5>
        <p class="text-muted small mb-0">Pemantauan data kandidat calon agen pemasar ter-coded di kantor GA LKG MW Medan</p>
    </div>
    <div class="d-flex gap-2 flex-wrap w-100 w-md-auto">
        <?php if ($user_role === 'Admin'): ?>
            <button class="btn btn-manulife btn-sm px-3 shadow-sm flex-grow-1 flex-md-grow-0" data-bs-toggle="collapse" data-bs-target="#collapseFormRekrut" aria-expanded="false">
                <i class="fa-solid fa-plus me-1"></i> Input Rekrut Baru
            </button>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-outline-danger btn-sm px-3 shadow-sm flex-grow-1 flex-md-grow-0">
            <i class="fa-solid fa-print me-1"></i> Cetak Laporan PDF
        </button>
    </div>
</div>

<!-- STAT CARDS RINGKASAN REKRUTMEN RESPONSIVE -->
<div class="row g-3 g-md-4 mb-3 mb-md-4 no-print">
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-custom p-3 bg-white border-start border-4 border-success h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Kandidat Bulan Ini</small>
                    <h4 class="fw-bold text-dark mb-0 mt-1 fs-5"><?= $total_bulan; ?> <span class="fs-6 text-muted fw-normal">Agen</span></h4>
                </div>
                <div class="p-2 rounded-circle bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-custom p-3 bg-white border-start border-4 border-primary h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Coded Tahun <?= date('Y'); ?></small>
                    <h4 class="fw-bold text-dark mb-0 mt-1 fs-5"><?= $total_tahun; ?> <span class="fs-6 text-muted fw-normal">Kandidat</span></h4>
                </div>
                <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-lg-4">
        <div class="card card-custom p-3 bg-white border-start border-4 border-warning h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Total Riwayat Rekrutmen</small>
                    <h4 class="fw-bold text-dark mb-0 mt-1 fs-5"><?= $total_kandidat; ?> <span class="fs-6 text-muted fw-normal">Orang</span></h4>
                </div>
                <div class="p-2 rounded-circle bg-warning bg-opacity-10 text-warning">
                    <i class="fa-solid fa-id-badge"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FORM INPUT REKRUT BARU (COLLAPSIBLE UNTUK ADMIN) -->
<?php if ($user_role === 'Admin'): ?>
<div class="collapse mb-4 no-print" id="collapseFormRekrut">
    <div class="card card-custom p-3 p-md-4 bg-white border border-success-subtle">
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <h6 class="fw-bold mb-0 text-dark fs-6"><i class="fa-solid fa-pen-nib text-success me-2"></i>Form Entri Kandidat Rekrutmen Baru</h6>
            <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#collapseFormRekrut" aria-label="Close"></button>
        </div>
        <form action="" method="POST" class="row g-3">
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Nama Kandidat Calon Agen</label>
                <input type="text" name="nama_kandidat" class="form-control form-control-sm" placeholder="Nama Lengkap" required>
            </div>
            <div class="col-6 col-sm-6 col-md-2">
                <label class="form-label small fw-semibold text-secondary">Level Posisi</label>
                <select name="level" class="form-select form-select-sm" required>
                    <option value="FC">FC (Financial Consultant)</option>
                    <option value="BP">BP (Business Partner)</option>
                    <option value="LP">LP (Life Planner)</option>
                    <option value="SBP">SBP (Senior Business Partner)</option>
                </select>
            </div>
            <div class="col-6 col-sm-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary">Tanggal Coded</label>
                <input type="date" name="coded_date" class="form-control form-control-sm" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary">Agen Perekrut</label>
                <input class="form-control form-control-sm" list="listPerekrutOptions" name="perekrut" placeholder="Pilih / Ketik Agen..." required>
                <datalist id="listPerekrutOptions">
                    <?php if ($list_perekrut): while ($p = mysqli_fetch_assoc($list_perekrut)): ?>
                        <option value="<?= htmlspecialchars($p['nama_lengkap']); ?>">
                    <?php endwhile; endif; ?>
                </datalist>
            </div>
            <div class="col-6 col-sm-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary">NR (Bulan Coded)</label>
                <select name="nr" class="form-select form-select-sm">
                    <?php 
                        $bulan_list = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        $cur_month = $bulan_list[(int)date('n') - 1];
                        foreach ($bulan_list as $b):
                    ?>
                        <option value="<?= $b; ?>" <?= $b === $cur_month ? 'selected' : ''; ?>><?= $b; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-sm-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary">NRA (Target/Produksi)</label>
                <input type="text" name="nra" class="form-control form-control-sm" placeholder="Contoh: 1">
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end">
                <button type="submit" name="submit_rekrut" class="btn btn-manulife btn-sm px-4 py-2 w-100 shadow-sm d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Data Kandidat
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- FILTER PANEL RESPONSIVE -->
<div class="card card-custom p-3 bg-white mb-3 mb-md-4 no-print">
    <form action="index.php" method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="page" value="rekrut">
        <div class="col-12 col-sm-6 col-md-3">
            <input type="text" name="search_nama" class="form-control form-control-sm" placeholder="Cari nama kandidat..." value="<?= htmlspecialchars($_GET['search_nama'] ?? ''); ?>">
        </div>
        <div class="col-6 col-sm-6 col-md-2">
            <select name="search_level" class="form-select form-select-sm">
                <option value="">-- Level --</option>
                <option value="FC" <?= ($_GET['search_level'] ?? '') === 'FC' ? 'selected' : ''; ?>>FC</option>
                <option value="BP" <?= ($_GET['search_level'] ?? '') === 'BP' ? 'selected' : ''; ?>>BP</option>
                <option value="LP" <?= ($_GET['search_level'] ?? '') === 'LP' ? 'selected' : ''; ?>>LP</option>
                <option value="SBP" <?= ($_GET['search_level'] ?? '') === 'SBP' ? 'selected' : ''; ?>>SBP</option>
            </select>
        </div>
        <div class="col-6 col-sm-6 col-md-2">
            <select name="search_bulan" class="form-select form-select-sm">
                <option value="">-- Bulan --</option>
                <?php 
                    $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                    foreach($months as $k=>$v): 
                ?>
                    <option value="<?= $k; ?>" <?= (isset($_GET['search_bulan']) && $_GET['search_bulan'] == $k) ? 'selected' : ''; ?>><?= $v; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-6 col-md-2">
            <select name="search_tahun" class="form-select form-select-sm">
                <option value="">-- Tahun --</option>
                <?php for ($y=date('Y'); $y>=2020; $y--): ?>
                    <option value="<?= $y; ?>" <?= (isset($_GET['search_tahun']) && $_GET['search_tahun'] == $y) ? 'selected' : ''; ?>><?= $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6 col-sm-12 col-md-3 d-flex gap-1">
            <button type="submit" class="btn btn-success btn-sm w-100"><i class="fa-solid fa-search me-1"></i> Cari</button>
            <a href="index.php?page=rekrut" class="btn btn-secondary btn-sm" title="Reset Filter"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
</div>

<!-- TABEL DATA MONITORING REKRUTMEN -->
<div class="card card-custom p-3 p-md-4 bg-white">
    <div class="table-responsive">
        <div class="d-block d-md-none text-muted mb-2 small"><i class="fa-solid fa-arrows-left-right text-success me-1"></i> Geser tabel ke samping untuk melihat data kandidat lengkap</div>
        <table class="table table-bordered table-hover text-center align-middle mb-0" style="min-width: 660px;">
            <thead class="table-light">
                <tr>
                    <th style="width: 45px;">NO</th>
                    <th class="text-start">NAMA KANDIDAT</th>
                    <th>LEVEL</th>
                    <th>TANGGAL CODED</th>
                    <th>NR (BULAN)</th>
                    <th>NRA</th>
                    <th class="text-start">AGEN PEREKRUT</th>
                    <?php if ($user_role === 'Admin'): ?>
                        <th class="no-print text-center text-nowrap" style="width: 90px; min-width: 90px;">AKSI</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($data_rekrut && mysqli_num_rows($data_rekrut) > 0): ?>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($data_rekrut)): ?>
                        <?php
                            $lvl_badge = 'bg-secondary';
                            if ($row['level'] === 'SBP') $lvl_badge = 'bg-dark text-white';
                            elseif ($row['level'] === 'LP') $lvl_badge = 'bg-primary text-white';
                            elseif ($row['level'] === 'BP') $lvl_badge = 'bg-info text-dark';
                            elseif ($row['level'] === 'FC') $lvl_badge = 'bg-success text-white';
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td class="text-start fw-bold text-dark small"><?= htmlspecialchars($row['nama_kandidat']); ?></td>
                            <td><span class="badge <?= $lvl_badge; ?> rounded-pill px-2 py-1 small"><?= htmlspecialchars($row['level']); ?></span></td>
                            <td class="small"><?= date('d-m-Y', strtotime($row['coded_date'])); ?></td>
                            <td class="small"><?= htmlspecialchars($row['nr'] ?: '-'); ?></td>
                            <td class="small"><?= htmlspecialchars($row['nra'] ?: '-'); ?></td>
                            <td class="text-start fw-semibold text-success small"><?= htmlspecialchars($row['perekrut']); ?></td>
                            <?php if ($user_role === 'Admin'): ?>
                                <td class="no-print text-center text-nowrap" style="width: 90px;">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                        <button type="button" class="btn btn-action-icon btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditRekrut<?= $row['id']; ?>" title="Edit Data">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <a href="index.php?page=rekrut&action=delete&id=<?= $row['id']; ?>" 
                                           class="btn btn-action-icon btn-outline-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data kandidat <?= htmlspecialchars(addslashes($row['nama_kandidat'])); ?>?');"
                                           title="Hapus Data">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- MODAL EDIT REKRUTMEN -->
                        <?php if ($user_role === 'Admin'): ?>
                        <div class="modal fade text-start" id="modalEditRekrut<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header border-bottom py-2">
                                        <h6 class="modal-title fw-bold">Edit Data: <?= htmlspecialchars($row['nama_kandidat']); ?></h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Nama Kandidat</label>
                                                <input type="text" name="nama_kandidat" class="form-control" value="<?= htmlspecialchars($row['nama_kandidat']); ?>" required>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">Level</label>
                                                    <select name="level" class="form-select">
                                                        <option value="FC" <?= $row['level'] === 'FC' ? 'selected' : ''; ?>>FC</option>
                                                        <option value="BP" <?= $row['level'] === 'BP' ? 'selected' : ''; ?>>BP</option>
                                                        <option value="LP" <?= $row['level'] === 'LP' ? 'selected' : ''; ?>>LP</option>
                                                        <option value="SBP" <?= $row['level'] === 'SBP' ? 'selected' : ''; ?>>SBP</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">Tanggal Coded</label>
                                                    <input type="date" name="coded_date" class="form-control" value="<?= $row['coded_date']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">NR (Bulan)</label>
                                                    <input type="text" name="nr" class="form-control" value="<?= htmlspecialchars($row['nr']); ?>">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">NRA</label>
                                                    <input type="text" name="nra" class="form-control" value="<?= htmlspecialchars($row['nra']); ?>">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Agen Perekrut</label>
                                                <input type="text" name="perekrut" class="form-control" value="<?= htmlspecialchars($row['perekrut']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top py-2">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="update_rekrut" class="btn btn-success btn-sm">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="<?= $user_role === 'Admin' ? '8' : '7'; ?>" class="text-muted py-4 small">Data monitoring rekrutmen tidak ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TANDA TANGAN DISPOSISI KHUSUS CETAK -->
    <div class="print-only mt-5 pt-4">
        <div class="row text-center">
            <div class="col-6">
                <p class="mb-5 small">Dibuat oleh,<br><strong>Staf Administrasi Agensi</strong></p>
                <p class="fw-bold text-decoration-underline mb-0">DWY / TABITA</p>
                <small class="text-muted">Admin GA Office LKG MW</small>
            </div>
            <div class="col-6">
                <p class="mb-5 small">Mengetahui & Menyetujui,<br><strong>Pimpinan Agensi (GA Owner)</strong></p>
                <p class="fw-bold text-decoration-underline mb-0">KASTERIA GINTING</p>
                <small class="text-muted">General Agency Owner</small>
            </div>
        </div>
    </div>
</div>