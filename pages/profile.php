<?php
require_once 'config/database.php';
$user_id = $_SESSION['user_id'];
$pesan = '';

// Proses Hapus Foto Profil
if (isset($_POST['hapus_foto'])) {
    $q_foto = mysqli_query($conn, "SELECT profile_photo FROM users WHERE id = '$user_id'");
    $d_foto = mysqli_fetch_assoc($q_foto);
    if (!empty($d_foto['profile_photo']) && file_exists($d_foto['profile_photo'])) {
        unlink($d_foto['profile_photo']);
    }
    mysqli_query($conn, "UPDATE users SET profile_photo = '' WHERE id = '$user_id'");
    $pesan = "<div class='alert alert-success alert-dismissible fade show'><i class='fa-solid fa-circle-check me-2'></i>Foto profil berhasil dihapus! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

// Proses Simpan Profil
if (isset($_POST['simpan_profil'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email']));
    
    // Update session agar sinkron
    $_SESSION['nama_lengkap'] = $nama_lengkap;
    $_SESSION['nama']         = $nama_lengkap;
    
    $foto_query = "";
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $file_tmp    = $_FILES['foto_profil']['tmp_name'];
        $file_name   = time() . '_user_' . $user_id . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['foto_profil']['name']);
        $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $upload_dir = 'uploads/profiles/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Hapus foto lama jika ada
                $q_old = mysqli_query($conn, "SELECT profile_photo FROM users WHERE id = '$user_id'");
                $d_old = mysqli_fetch_assoc($q_old);
                if (!empty($d_old['profile_photo']) && file_exists($d_old['profile_photo'])) {
                    unlink($d_old['profile_photo']);
                }
                $foto_query = ", profile_photo = '$target_file'";
            }
        } else {
            $pesan = "<div class='alert alert-warning'>Format file foto tidak diizinkan. Gunakan JPG, PNG, atau WEBP.</div>";
        }
    }
    
    // Validasi Password Baru & Konfirmasi
    $pass_query = "";
    $password_error = false;
    if (!empty($_POST['password'])) {
        $new_pass  = $_POST['password'];
        $conf_pass = $_POST['confirm_password'] ?? '';

        if ($new_pass !== $conf_pass) {
            $pesan = "<div class='alert alert-danger alert-dismissible fade show'><i class='fa-solid fa-triangle-exclamation me-2'></i>Konfirmasi kata sandi tidak cocok! Kata sandi tidak diperbarui.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            $password_error = true;
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $pass_query = ", password = '$hashed'";
        }
    }
    
    if (!$password_error) {
        $query = "UPDATE users SET nama_lengkap = '$nama_lengkap', email = '$email' $foto_query $pass_query WHERE id = '$user_id'";
        if (mysqli_query($conn, $query)) {
            if (empty($pesan)) {
                $pesan = "<div class='alert alert-success alert-dismissible fade show'><i class='fa-solid fa-circle-check me-2'></i>Profil Anda berhasil diperbarui! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $pesan = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Ambil data user terkini
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($query_user);

$user_photo    = $user['profile_photo'];
$default_photo = "https://ui-avatars.com/api/?name=" . urlencode($user['nama_lengkap']) . "&background=00a758&color=ffffff&rounded=true&bold=true";
$display_photo = (!empty($user_photo) && file_exists($user_photo)) ? $user_photo : $default_photo;
?>

<div class="row mb-3 mb-md-4">
    <div class="col-12">
        <div class="card card-custom p-3 p-md-4 bg-white" style="border-left: 5px solid #00a758;">
            <h5 class="fw-bold text-dark mb-1 fs-5"><i class="fa-solid fa-user-circle text-success me-2"></i>Profil Pengguna</h5>
            <p class="text-muted small mb-0">Kelola identitas akun agen, foto profil, dan pembaruan kata sandi</p>
        </div>
    </div>
</div>

<?= $pesan; ?>

<div class="row g-3 g-md-4">
    <!-- AVATAR CARD -->
    <div class="col-12 col-lg-4">
        <div class="card card-custom p-3 p-md-4 text-center h-100">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <img id="avatarPreview" src="<?= htmlspecialchars($display_photo); ?>" alt="Profil" class="rounded-circle shadow" style="width: 130px; height: 130px; object-fit: cover; border: 4px solid #e0f2e9;">
            </div>
            <h6 class="fw-bold mb-1 text-dark fs-6"><?= htmlspecialchars($user['nama_lengkap']); ?></h6>
            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 mb-2 small">
                <i class="fa-solid fa-id-badge me-1"></i> <?= htmlspecialchars($user['role']); ?>
            </span>
            <p class="text-muted small font-monospace mb-3" style="font-size: 0.75rem;">Lisensi: <?= htmlspecialchars($user['no_lisensi'] ?: '-'); ?></p>
            
            <?php if (!empty($user_photo) && file_exists($user_photo)): ?>
                <form action="index.php?page=profile" method="POST">
                    <button type="submit" name="hapus_foto" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Apakah Anda yakin ingin menghapus foto profil?');">
                        <i class="fa-solid fa-trash me-1"></i> Hapus Foto
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- FORM EDIT PROFIL & SANDI -->
    <div class="col-12 col-lg-8">
        <div class="card card-custom p-3 p-md-4">
            <form action="index.php?page=profile" method="POST" enctype="multipart/form-data">
                <h6 class="fw-bold mb-3 text-dark border-bottom pb-2 fs-6">
                    <i class="fa-solid fa-address-card text-success me-2"></i>Informasi Data Diri
                </h6>
                
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Nama Lengkap</label>
                        <input type="text" class="form-control form-control-sm" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" required>
                    </div>
                    
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-semibold small text-secondary">Alamat Email</label>
                        <input type="email" class="form-control form-control-sm" name="email" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" placeholder="nama@manulife-lkg.com">
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-semibold small text-secondary">Nomor Lisensi AAJI</label>
                        <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($user['no_lisensi'] ?? '-'); ?>" readonly disabled>
                        <small class="text-muted" style="font-size: 0.72rem;">*Nomor lisensi dikunci oleh Admin.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Unggah Foto Profil Baru</label>
                        <input type="file" class="form-control form-control-sm" id="inputFotoProfil" name="foto_profil" accept=".jpg, .jpeg, .png, .webp">
                        <div class="text-muted" style="font-size: 0.72rem;">Format didukung: JPG, PNG, WEBP. Maksimal 2MB. Pratinjau langsung terlihat di sebelah kiri.</div>
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3 text-dark border-bottom pb-2 mt-4 fs-6">
                    <i class="fa-solid fa-key text-success me-2"></i>Ubah Kata Sandi
                </h6>
                
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-semibold small text-secondary">Kata Sandi Baru</label>
                        <div class="input-group input-group-sm">
                            <input type="password" class="form-control" name="password" id="passInput" placeholder="Kosongkan jika tidak diubah">
                            <button class="btn btn-outline-secondary" type="button" id="togglePass1"><i class="fa-regular fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-semibold small text-secondary">Ulangi Kata Sandi Baru</label>
                        <div class="input-group input-group-sm">
                            <input type="password" class="form-control" name="confirm_password" id="passConfirmInput" placeholder="Ulangi kata sandi baru">
                            <button class="btn btn-outline-secondary" type="button" id="togglePass2"><i class="fa-regular fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" name="simpan_profil" class="btn btn-manulife px-4 py-2 shadow-sm w-100 w-sm-auto d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Live Image Preview for Profile Photo
    const inputFoto = document.getElementById('inputFotoProfil');
    const avatarPreview = document.getElementById('avatarPreview');

    if (inputFoto && avatarPreview) {
        inputFoto.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Toggle Password Visibility Helper
    function setupPassToggle(btnId, inputId) {
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        if (btn && input) {
            btn.addEventListener('click', function() {
                const isPass = input.type === 'password';
                input.type = isPass ? 'text' : 'password';
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
    }
    setupPassToggle('togglePass1', 'passInput');
    setupPassToggle('togglePass2', 'passConfirmInput');
</script>
