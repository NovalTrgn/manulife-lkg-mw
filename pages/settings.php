<?php
require_once 'config/database.php';

// Cek apakah user adalah Admin
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Akses Khusus Staf Administrasi.</div>";
    exit;
}

$pesan = '';

if (isset($_POST['hapus_logo'])) {
    $query_settings = mysqli_query($conn, "SELECT logo_url FROM settings WHERE id = 1");
    $settings = mysqli_fetch_assoc($query_settings);
    if (!empty($settings['logo_url']) && file_exists($settings['logo_url'])) {
        unlink($settings['logo_url']);
    }
    mysqli_query($conn, "UPDATE settings SET logo_url = '' WHERE id = 1");
    $pesan = "<div class='alert alert-success alert-dismissible fade show'><i class='fa-solid fa-circle-check me-2'></i>Logo berhasil dihapus! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

if (isset($_POST['hapus_favicon'])) {
    $query_settings = mysqli_query($conn, "SELECT favicon_url FROM settings WHERE id = 1");
    $settings = mysqli_fetch_assoc($query_settings);
    if (!empty($settings['favicon_url']) && file_exists($settings['favicon_url'])) {
        unlink($settings['favicon_url']);
    }
    mysqli_query($conn, "UPDATE settings SET favicon_url = '' WHERE id = 1");
    $pesan = "<div class='alert alert-success alert-dismissible fade show'><i class='fa-solid fa-circle-check me-2'></i>Favicon (Icon) berhasil dihapus! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

if (isset($_POST['simpan_pengaturan'])) {
    $site_name        = mysqli_real_escape_string($conn, trim($_POST['site_name']));
    $site_description = mysqli_real_escape_string($conn, trim($_POST['site_description']));
    
    $logo_url_query = "";
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['logo_upload']['tmp_name'];
        $file_name = time() . '_logo_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['logo_upload']['name']);
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        
        if (in_array($file_ext, $allowed)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($file_tmp, $target_file)) {
                $logo_url_query = ", logo_url = '$target_file'";
            }
        }
    }
    
    $favicon_url_query = "";
    if (isset($_FILES['favicon_upload']) && $_FILES['favicon_upload']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['favicon_upload']['tmp_name'];
        $file_name = time() . '_fav_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['favicon_upload']['name']);
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['png', 'ico', 'gif', 'jpg', 'jpeg', 'svg'];
        
        if (in_array($file_ext, $allowed)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($file_tmp, $target_file)) {
                $favicon_url_query = ", favicon_url = '$target_file'";
            }
        }
    }
    
    $query = "UPDATE settings SET site_name = '$site_name', site_description = '$site_description' $logo_url_query $favicon_url_query WHERE id = 1";
    if (mysqli_query($conn, $query)) {
        $pesan = "<div class='alert alert-success alert-dismissible fade show'><i class='fa-solid fa-circle-check me-2'></i>Pengaturan sistem berhasil disimpan! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $pesan = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Ambil pengaturan saat ini
$query_settings = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
$settings = mysqli_fetch_assoc($query_settings);

$current_site_name = $settings['site_name'] ?? 'GA LKG MW';
$current_site_desc = $settings['site_description'] ?? 'Manulife Medan';
$current_logo_url  = $settings['logo_url'] ?? '';
$current_favicon   = $settings['favicon_url'] ?? '';
?>

<div class="row mb-3 mb-md-4">
    <div class="col-12">
        <div class="card card-custom p-3 p-md-4 bg-white" style="border-left: 5px solid #00a758;">
            <h5 class="fw-bold text-dark mb-1 fs-5"><i class="fa-solid fa-gear text-success me-2"></i>Pengaturan Portal Agensi</h5>
            <p class="text-muted small mb-0">Ubah identitas portal, nama agensi, logo resmi, dan favicon tab browser</p>
        </div>
    </div>
</div>

<?= $pesan; ?>

<div class="row">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card card-custom p-3 p-md-4 bg-white">
            <form action="index.php?page=settings" method="POST" enctype="multipart/form-data">
                <div class="row mb-4 g-3">
                    <!-- LOGO PREVIEW & UPLOAD -->
                    <div class="col-12 col-sm-6 text-center">
                        <label class="form-label fw-bold small text-secondary d-block">Logo Agensi Saat Ini</label>
                        <div class="p-3 bg-light rounded-3 border d-flex flex-column align-items-center justify-content-center" style="min-height: 140px;">
                            <?php if(!empty($current_logo_url) && file_exists($current_logo_url)): ?>
                                <img id="logoPreviewImg" src="<?= htmlspecialchars($current_logo_url); ?>" alt="Logo Saat Ini" class="img-fluid rounded mb-2 shadow-sm" style="max-height: 75px; object-fit: contain;">
                                <div class="mt-2">
                                    <button type="submit" name="hapus_logo" class="btn btn-xs btn-outline-danger py-1 px-2" onclick="return confirm('Apakah Anda yakin ingin menghapus logo?');" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus Logo
                                    </button>
                                </div>
                            <?php else: ?>
                                <img id="logoPreviewImg" src="" class="img-fluid rounded mb-2 shadow-sm d-none" style="max-height: 75px; object-fit: contain;" alt="Logo Baru">
                                <div id="logoPlaceholder" class="text-muted small">
                                    <i class="fa-solid fa-image fa-2x mb-1 text-secondary opacity-50 d-block"></i>
                                    Belum ada logo terunggah
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FAVICON PREVIEW & UPLOAD -->
                    <div class="col-12 col-sm-6 text-center">
                        <label class="form-label fw-bold small text-secondary d-block">Favicon (Tab Browser)</label>
                        <div class="p-3 bg-light rounded-3 border d-flex flex-column align-items-center justify-content-center" style="min-height: 140px;">
                            <?php if(!empty($current_favicon) && file_exists($current_favicon)): ?>
                                <img id="favPreviewImg" src="<?= htmlspecialchars($current_favicon); ?>" alt="Favicon Saat Ini" class="img-thumbnail rounded mb-2 shadow-sm" style="max-height: 48px;">
                                <div class="mt-2">
                                    <button type="submit" name="hapus_favicon" class="btn btn-xs btn-outline-danger py-1 px-2" onclick="return confirm('Apakah Anda yakin ingin menghapus favicon?');" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus Icon
                                    </button>
                                </div>
                            <?php else: ?>
                                <img id="favPreviewImg" src="" class="img-thumbnail rounded mb-2 shadow-sm d-none" style="max-height: 48px;" alt="Favicon Baru">
                                <div id="favPlaceholder" class="text-muted small">
                                    <i class="fa-solid fa-icons fa-2x mb-1 text-secondary opacity-50 d-block"></i>
                                    Belum ada favicon
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Nama Portal / Agensi</label>
                    <input type="text" class="form-control form-control-sm" name="site_name" value="<?= htmlspecialchars($current_site_name); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Sub-Judul / Keterangan Kantor</label>
                    <input type="text" class="form-control form-control-sm" name="site_description" value="<?= htmlspecialchars($current_site_desc); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Unggah File Logo Baru (Opsional)</label>
                    <input type="file" class="form-control form-control-sm" id="inputLogo" name="logo_upload" accept="image/*">
                    <small class="text-muted" style="font-size: 0.72rem;">Format: PNG, JPG, WEBP, SVG. Pratinjau langsung terlihat di atas.</small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-secondary">Unggah File Favicon / Icon Tab (Opsional)</label>
                    <input type="file" class="form-control form-control-sm" id="inputFavicon" name="favicon_upload" accept=".ico, .png, .jpg, .jpeg, .gif, .svg">
                    <small class="text-muted" style="font-size: 0.72rem;">Icon kecil tab browser (disarankan rasio 1:1, misal 32x32px atau 64x64px).</small>
                </div>
                
                <div class="d-grid pt-2">
                    <button type="submit" name="simpan_pengaturan" class="btn btn-manulife py-2 shadow-sm d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Seluruh Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function setupLivePreview(inputId, imgId, placeholderId) {
        const input = document.getElementById(inputId);
        const img = document.getElementById(imgId);
        const placeholder = document.getElementById(placeholderId);
        if (input && img) {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                        img.classList.remove('d-none');
                        if (placeholder) { placeholder.classList.add('d-none'); }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }
    setupLivePreview('inputLogo', 'logoPreviewImg', 'logoPlaceholder');
    setupLivePreview('inputFavicon', 'favPreviewImg', 'favPlaceholder');
</script>
