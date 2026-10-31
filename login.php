<?php
session_start();
require_once 'config/database.php';

// Jika sudah login, langsung lempar ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Ambil data pengaturan situs untuk logo dan judul
$app_site_name = 'GA LKG MW';
$app_site_desc = 'Manulife Medan';
$app_logo_url  = '';
$app_favicon   = '';

$q_settings = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
if ($q_settings && mysqli_num_rows($q_settings) > 0) {
    $set = mysqli_fetch_assoc($q_settings);
    $app_site_name = $set['site_name'] ?: 'GA LKG MW';
    $app_site_desc = $set['site_description'] ?: 'Manulife Medan';
    $app_logo_url  = $set['logo_url'] ?: '';
    $app_favicon   = $set['favicon_url'] ?: '';
}

$error = '';

// Pesan jika diarahkan karena akun dinonaktifkan saat sedang login
if (isset($_GET['error']) && $_GET['error'] === 'inactive') {
    $error = 'Sesi Anda telah dihentikan karena akun Anda berstatus Non-Aktif. Silakan hubungi Staf Administrasi GA LKG MW.';
}

if (isset($_POST['login'])) {
    $identifier = mysqli_real_escape_string($conn, trim($_POST['identifier']));
    $password   = $_POST['password'];

    $query  = "SELECT * FROM users WHERE no_lisensi = '$identifier' OR email = '$identifier' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // VERIFIKASI STATUS: HANYA AKUN 'Aktif' YANG BOLEH LOGIN
        if (trim($user['status'] ?? '') !== 'Aktif') {
            $error = 'Akun Anda berstatus Non-Aktif. Akses masuk ke portal ditolak. Silakan hubungi Staf Administrasi Kantor.';
        } 
        // VERIFIKASI KATA SANDI
        elseif (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'] ?? $user['nama'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['no_lisensi']   = $user['no_lisensi'];
            $_SESSION['status']       = $user['status'] ?? 'Aktif';

            header("Location: index.php?page=dashboard");
            exit;
        } else {
            $error = 'Kata sandi yang Anda masukkan salah. Silakan coba lagi.';
        }
    } else {
        $error = 'Nomor Lisensi AAJI atau Email tidak ditemukan dalam sistem.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Login Portal - <?= htmlspecialchars($app_site_name); ?></title>
    <?php if (!empty($app_favicon) && file_exists($app_favicon)): ?>
        <link rel="icon" href="<?= htmlspecialchars($app_favicon); ?>" type="image/x-icon">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --manulife-green: #00a758;
            --manulife-dark: #007a3e;
            --manulife-deep: #003b1e;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
        body { 
            background: linear-gradient(135deg, #007a3e 0%, #003b1e 100%); 
            font-family: 'Poppins', sans-serif;
            min-height: 100vh; 
            min-height: 100dvh;
            display: flex; 
            align-items: center; 
            justify-content: center;
            padding: 16px;
            margin: 0;
            overflow-x: hidden;
        }
        .login-card { 
            max-width: 440px; 
            width: 100%;
            border: none; 
            border-radius: 18px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
            overflow: hidden;
            background: #ffffff;
        }
        .login-header { 
            background: #ffffff; 
            text-align: center;
            padding: 30px 20px 18px;
            border-bottom: 1px solid #f1f5f9;
        }
        .btn-manulife {
            background-color: var(--manulife-green);
            color: #ffffff;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            min-height: 44px;
        }
        .btn-manulife:hover {
            background-color: var(--manulife-dark);
            color: #ffffff;
            box-shadow: 0 6px 15px rgba(0,167,88,0.35);
        }
        .form-control {
            border-radius: 10px;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            font-size: 0.95rem;
            min-height: 44px;
        }
        .form-control:focus {
            border-color: var(--manulife-green);
            box-shadow: 0 0 0 4px rgba(0, 167, 88, 0.15);
        }
        .input-group-text {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <?php if (!empty($app_logo_url) && file_exists($app_logo_url)): ?>
            <img src="<?= htmlspecialchars($app_logo_url); ?>" alt="Logo" class="img-fluid mb-2" style="max-height: 60px; object-fit: contain;">
        <?php else: ?>
            <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-2" style="width: 52px; height: 52px;">
                <i class="fa-solid fa-leaf fs-3"></i>
            </div>
            <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($app_site_name); ?></h4>
        <?php endif; ?>
        <p class="text-muted small mb-0 mt-1"><?= htmlspecialchars($app_site_desc); ?> &bull; Portal Agen</p>
    </div>

    <div class="p-3 p-sm-4 pt-3">
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-3 rounded-3" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2 fs-5 flex-shrink-0"></i>
                <div><?= htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small mb-1">Nomor Lisensi AAJI / Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                    <input type="text" name="identifier" class="form-control" placeholder="Contoh: AAJI12345" required autofocus value="<?= htmlspecialchars($_POST['identifier'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small mb-1">Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Masukkan kata sandi..." required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" style="border: 1.5px solid #e2e8f0; border-left: none; border-radius: 0 10px 10px 0;">
                        <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-manulife w-100 py-2 mt-2 d-flex align-items-center justify-content-center">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk ke Portal
            </button>
        </form>

        <div class="text-center mt-3 pt-3 border-top">
            <small class="text-muted d-block" style="font-size: 0.78rem;">
                <i class="fa-solid fa-headset me-1 text-success"></i> Butuh bantuan login atau reset sandi?
            </small>
            <small class="text-secondary fw-semibold" style="font-size: 0.8rem;">Hubungi Staf Administrasi (Dwy / Tabita)</small>
        </div>
    </div>
</div>

<script>
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('loginPassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (togglePasswordBtn && passwordInput && togglePasswordIcon) {
        togglePasswordBtn.addEventListener('click', function() {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            togglePasswordIcon.classList.toggle('fa-eye');
            togglePasswordIcon.classList.toggle('fa-eye-slash');
        });
    }
</script>

</body>
</html>