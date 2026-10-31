<?php
require_once 'config/database.php';
$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Agen';

$alert_message = '';

if (isset($_POST['submit_presensi'])) {
    $tanggal_waktu = date('Y-m-d H:i:s');
    $status        = mysqli_real_escape_string($conn, $_POST['status_kehadiran']);
    $latitude      = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude     = mysqli_real_escape_string($conn, $_POST['longitude']);
    $keterangan    = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $foto_bukti    = $_POST['foto_bukti'];

    if (!empty($foto_bukti) && !empty($latitude) && !empty($longitude)) {
        $query = "INSERT INTO presensi (user_id, tanggal_waktu, status_kehadiran, foto_bukti, latitude, longitude, keterangan) 
                  VALUES ('$user_id', '$tanggal_waktu', '$status', '$foto_bukti', '$latitude', '$longitude', '$keterangan')";
        if (mysqli_query($conn, $query)) {
            $alert_message = "<div class='alert alert-success alert-dismissible fade show shadow-sm' role='alert'>
                <i class='fa-solid fa-circle-check me-2'></i><strong>Presensi Berhasil!</strong> Data kehadiran & koordinat GPS Anda telah tercatat ke sistem kantor.
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
        } else {
            $alert_message = "<div class='alert alert-danger'>Gagal menyimpan presensi: " . mysqli_error($conn) . "</div>";
        }
    } else {
        $alert_message = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <i class='fa-solid fa-triangle-exclamation me-2'></i><strong>Verifikasi Belum Lengkap!</strong> Mohon ambil foto selfie dan pastikan lokasi GPS terkunci sebelum mengirim absen.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}

// Filter data presensi
$filter_agen = $_GET['filter_agen'] ?? '';
$filter_tgl  = $_GET['filter_tgl'] ?? '';

if ($user_role === 'Admin') {
    $where = [];
    if (!empty($filter_agen)) {
        $where[] = "p.user_id = '" . mysqli_real_escape_string($conn, $filter_agen) . "'";
    }
    if (!empty($filter_tgl)) {
        $where[] = "DATE(p.tanggal_waktu) = '" . mysqli_real_escape_string($conn, $filter_tgl) . "'";
    }
    $where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";
    $data_presensi = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, u.no_lisensi FROM presensi p JOIN users u ON p.user_id = u.id $where_sql ORDER BY p.id DESC");
    
    // Ambil list agen untuk filter
    $list_agen = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE role = 'Agen' ORDER BY nama_lengkap ASC");
} else {
    // Agen hanya melihat datanya sendiri (Privasi & Keamanan Data)
    $data_presensi = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, u.no_lisensi FROM presensi p JOIN users u ON p.user_id = u.id WHERE p.user_id = '$user_id' ORDER BY p.id DESC");
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?= $alert_message; ?>

<div class="row g-3 g-md-4">
    <!-- FORM PRESENSI SELFIE & GPS -->
    <div class="col-12 col-lg-5">
        <div class="card card-custom p-3 p-md-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h6 class="fw-bold mb-0 text-dark fs-6">
                    <i class="fa-solid fa-camera text-success me-2"></i>Check-in Presensi
                </h6>
                <span class="badge bg-success bg-opacity-10 text-success font-monospace small px-2 py-1">
                    <?= date('d M Y'); ?>
                </span>
            </div>

            <form action="" method="POST" id="formPresensi">
                <!-- Status Kehadiran -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Status Kehadiran</label>
                    <select name="status_kehadiran" class="form-select form-select-sm" required>
                        <option value="Hadir">Hadir Kantor LKG MW</option>
                        <option value="Dinas Luar">Dinas Luar / Pertemuan Nasabah</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                    </select>
                </div>

                <!-- Foto Bukti Webcam -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold small text-secondary mb-0">Foto Selfie Bukti</label>
                        <button type="button" id="btnSwitchCam" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-camera-rotate me-1"></i> Putar Kamera
                        </button>
                    </div>
                    <div class="border rounded-3 p-2 text-center bg-light position-relative overflow-hidden">
                        <div id="camera-loading" class="text-muted small py-4">
                            <i class="fa-solid fa-spinner fa-spin text-success me-1"></i> Memuat kamera perangkat...
                        </div>
                        <video id="webcam" autoplay playsinline style="width: 100%; max-height: 220px; object-fit: cover; display: none;" class="rounded"></video>
                        <canvas id="canvas" class="d-none"></canvas>
                        <img id="photo-preview" src="" class="d-none rounded w-100" style="max-height: 220px; object-fit: cover;" alt="Preview Selfie" />
                        
                        <div class="mt-2 d-flex justify-content-center gap-2">
                            <button type="button" id="btnCapture" class="btn btn-sm btn-primary px-3 shadow-sm">
                                <i class="fa-solid fa-camera me-1"></i> Ambil Foto
                            </button>
                            <button type="button" id="btnRetake" class="btn btn-sm btn-secondary d-none px-3">
                                <i class="fa-solid fa-rotate-right me-1"></i> Foto Ulang
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="foto_bukti" id="foto_bukti" required>
                </div>

                <!-- Deteksi Geospasial GPS -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold small text-secondary mb-0">Lokasi Geospasial GPS</label>
                        <button type="button" id="btnRefreshGPS" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-location-crosshairs me-1"></i> Deteksi Ulang
                        </button>
                    </div>
                    <div id="map" style="height: 140px; border-radius: 8px; width: 100%;" class="mb-2 border"></div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted small">Lat</span>
                                <input type="text" name="latitude" id="latitude" class="form-control bg-light font-monospace small" placeholder="Latitude" readonly required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted small">Long</span>
                                <input type="text" name="longitude" id="longitude" class="form-control bg-light font-monospace small" placeholder="Longitude" readonly required>
                            </div>
                        </div>
                    </div>
                    <div id="gps-status-badge" class="mt-1 small text-muted" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-satellite-dish me-1 text-secondary"></i> Sedang mendeteksi sinyal GPS...
                    </div>
                </div>

                <!-- Keterangan -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Keterangan / Laporan Singkat</label>
                    <textarea name="keterangan" class="form-control form-control-sm" rows="2" placeholder="Contoh: Standby kantor / Meeting nasabah di Sun Plaza Medan..." required></textarea>
                </div>

                <button type="submit" name="submit_presensi" class="btn btn-manulife w-100 py-2 shadow-sm d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-paper-plane me-2"></i> Kirim Presensi Hari Ini
                </button>
            </form>
        </div>
    </div>

    <!-- TABEL RIWAYAT PRESENSI -->
    <div class="col-12 col-lg-7">
        <div class="card card-custom p-3 p-md-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark fs-6">
                        <i class="fa-solid fa-clock-rotate-left text-success me-2"></i>
                        <?= $user_role === 'Admin' ? 'Log Presensi Seluruh Agen' : 'Riwayat Presensi Saya'; ?>
                    </h6>
                    <small class="text-muted" style="font-size: 0.75rem;">
                        <?= $user_role === 'Admin' ? 'Audit kehadiran dan lokasi GPS tim agensi' : 'Rekam jejak absensi harian Anda'; ?>
                    </small>
                </div>
            </div>

            <!-- FILTER KHUSUS ADMIN RESPONSIVE -->
            <?php if ($user_role === 'Admin'): ?>
                <form action="index.php" method="GET" class="row g-2 mb-3 p-2 bg-light rounded-3 border">
                    <input type="hidden" name="page" value="presensi">
                    <div class="col-12 col-sm-6 col-md-5">
                        <select name="filter_agen" class="form-select form-select-sm">
                            <option value="">-- Semua Agen --</option>
                            <?php if ($list_agen): while ($a = mysqli_fetch_assoc($list_agen)): ?>
                                <option value="<?= $a['id']; ?>" <?= ($filter_agen == $a['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($a['nama_lengkap']); ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-6 col-md-4">
                        <input type="date" name="filter_tgl" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_tgl); ?>">
                    </div>
                    <div class="col-6 col-sm-12 col-md-3 d-flex gap-1">
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="fa-solid fa-filter"></i> Filter</button>
                        <a href="index.php?page=presensi" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
                    </div>
                </form>
            <?php endif; ?>

            <div class="table-responsive">
                <div class="d-block d-md-none text-muted mb-2 small"><i class="fa-solid fa-arrows-left-right text-success me-1"></i> Geser tabel ke samping untuk melihat log presensi lengkap</div>
                <table class="table table-hover align-middle mb-0" style="min-width: 500px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">Foto</th>
                            <th>Nama & Waktu</th>
                            <th>Status & Lokasi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($data_presensi && mysqli_num_rows($data_presensi) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($data_presensi)): ?>
                                <?php
                                    $st_badge = 'bg-success';
                                    if ($row['status_kehadiran'] === 'Dinas Luar') $st_badge = 'bg-primary';
                                    elseif ($row['status_kehadiran'] === 'Izin') $st_badge = 'bg-warning text-dark';
                                    elseif ($row['status_kehadiran'] === 'Sakit') $st_badge = 'bg-danger';
                                ?>
                                <tr>
                                    <td>
                                        <a href="#" class="view-photo-btn" data-photo="<?= $row['foto_bukti']; ?>" data-name="<?= htmlspecialchars($row['nama_lengkap']); ?>" data-time="<?= date('d M Y, H:i', strtotime($row['tanggal_waktu'])); ?>" data-status="<?= $row['status_kehadiran']; ?>" data-lat="<?= $row['latitude']; ?>" data-long="<?= $row['longitude']; ?>" data-ket="<?= htmlspecialchars($row['keterangan']); ?>">
                                            <img src="<?= $row['foto_bukti']; ?>" alt="Selfie" style="width: 46px; height: 46px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;" class="shadow-sm">
                                        </a>
                                    </td>
                                    <td>
                                        <span class="fw-bold d-block text-dark small"><?= htmlspecialchars($row['nama_lengkap']); ?></span>
                                        <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-regular fa-clock me-1"></i><?= date('d M Y &bull; H:i', strtotime($row['tanggal_waktu'])); ?> WIB</small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $st_badge; ?> mb-1 rounded-pill px-2 small"><?= $row['status_kehadiran']; ?></span><br>
                                        <a href="https://maps.google.com/?q=<?= $row['latitude']; ?>,<?= $row['longitude']; ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none py-1 small">
                                            <i class="fa-solid fa-map-location-dot me-1"></i> Google Maps
                                        </a>
                                    </td>
                                    <td><small class="text-secondary" style="font-size: 0.78rem;"><?= htmlspecialchars($row['keterangan']); ?></small></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-inbox fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                    Belum ada data riwayat presensi yang sesuai.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LIGHTBOX FOTO DETAIL PRESENSI -->
<div class="modal fade" id="modalViewPhoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm modal-md">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-2">
                <h6 class="modal-title fw-bold" id="modalPhotoTitle">Detail Bukti Presensi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="modalPhotoImage" src="" class="img-fluid rounded-3 mb-3 shadow-sm w-100" style="max-height: 280px; object-fit: cover;" alt="Foto Selfie">
                <div class="text-start bg-light p-3 rounded border">
                    <p class="mb-1 fw-bold text-dark small" id="modalPhotoName">-</p>
                    <p class="mb-1 text-muted" style="font-size: 0.75rem;" id="modalPhotoTime">-</p>
                    <p class="mb-2 text-secondary" style="font-size: 0.8rem;" id="modalPhotoKet">-</p>
                    <a id="modalPhotoMapBtn" href="#" target="_blank" class="btn btn-sm btn-outline-success w-100">
                        <i class="fa-solid fa-location-dot me-1"></i> Buka Titik Koordinat GPS
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const webcam = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const photoPreview = document.getElementById('photo-preview');
    const btnCapture = document.getElementById('btnCapture');
    const btnRetake = document.getElementById('btnRetake');
    const inputFoto = document.getElementById('foto_bukti');
    const camLoading = document.getElementById('camera-loading');
    const btnSwitchCam = document.getElementById('btnSwitchCam');
    const gpsBadge = document.getElementById('gps-status-badge');
    const btnRefreshGPS = document.getElementById('btnRefreshGPS');

    let currentStream = null;
    let useFacingMode = 'user'; // 'user' for front cam, 'environment' for back cam

    function startCamera(facingMode) {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
        }
        camLoading.style.display = 'block';
        webcam.style.display = 'none';

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: facingMode }
        }).then(stream => {
            currentStream = stream;
            webcam.srcObject = stream;
            camLoading.style.display = 'none';
            webcam.style.display = 'block';
        }).catch(err => {
            console.error(err);
            camLoading.innerHTML = '<span class="text-danger small"><i class="fa-solid fa-triangle-exclamation me-1"></i> Kamera tidak dapat diakses. Mohon izinkan akses kamera pada browser.</span>';
        });
    }

    startCamera(useFacingMode);

    if (btnSwitchCam) {
        btnSwitchCam.addEventListener('click', function() {
            useFacingMode = useFacingMode === 'user' ? 'environment' : 'user';
            startCamera(useFacingMode);
        });
    }

    btnCapture.addEventListener('click', function() {
        if (!webcam.videoWidth) {
            alert('Kamera belum siap!');
            return;
        }
        const context = canvas.getContext('2d');
        canvas.width = webcam.videoWidth; 
        canvas.height = webcam.videoHeight;
        context.drawImage(webcam, 0, 0, canvas.width, canvas.height);
        
        const dataURL = canvas.toDataURL('image/jpeg', 0.85);
        photoPreview.src = dataURL; 
        inputFoto.value = dataURL;

        webcam.classList.add('d-none'); 
        photoPreview.classList.remove('d-none');
        btnCapture.classList.add('d-none'); 
        btnRetake.classList.remove('d-none');
    });

    btnRetake.addEventListener('click', function() {
        webcam.classList.remove('d-none'); 
        photoPreview.classList.add('d-none');
        btnCapture.classList.remove('d-none'); 
        btnRetake.classList.add('d-none');
        inputFoto.value = '';
    });

    // MAP & GPS INITIALIZATION
    let map = L.map('map').setView([3.5952, 98.6722], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    let mapMarker = null;

    function detectLocation() {
        if (gpsBadge) {
            gpsBadge.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-primary me-1"></i> Mengunci titik GPS Anda...';
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
                
                map.setView([lat, lng], 16);
                if (mapMarker) { map.removeLayer(mapMarker); }
                mapMarker = L.marker([lat, lng]).addTo(map).bindPopup("Lokasi Presensi Anda").openPopup();

                setTimeout(function() { map.invalidateSize(); }, 300);

                if (gpsBadge) {
                    gpsBadge.innerHTML = '<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Koordinat GPS berhasil terkunci.</span>';
                }
            }, function(err) {
                console.warn(err);
                if (gpsBadge) {
                    gpsBadge.innerHTML = '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Gagal mendeteksi GPS. Pastikan izin lokasi diaktifkan.</span>';
                }
            }, {
                enableHighAccuracy: true,
                timeout: 10000
            });
        } else {
            if (gpsBadge) {
                gpsBadge.innerHTML = '<span class="text-danger">Browser tidak mendukung Geolocation.</span>';
            }
        }
    }

    detectLocation();

    window.addEventListener('resize', function() {
        setTimeout(function() { map.invalidateSize(); }, 300);
    });

    if (btnRefreshGPS) {
        btnRefreshGPS.addEventListener('click', detectLocation);
    }

    // LIGHTBOX PREVIEW MODAL
    const modalPhotoEl = document.getElementById('modalViewPhoto');
    let modalViewPhoto = null;
    if (modalPhotoEl && typeof bootstrap !== 'undefined') {
        modalViewPhoto = new bootstrap.Modal(modalPhotoEl);
    }
    document.querySelectorAll('.view-photo-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('modalPhotoImage').src = this.dataset.photo;
            document.getElementById('modalPhotoName').innerText = this.dataset.name + " (" + this.dataset.status + ")";
            document.getElementById('modalPhotoTime').innerText = "Waktu: " + this.dataset.time + " WIB";
            document.getElementById('modalPhotoKet').innerText = "Keterangan: " + this.dataset.ket;
            document.getElementById('modalPhotoMapBtn').href = "https://maps.google.com/?q=" + this.dataset.lat + "," + this.dataset.long;
            if (modalViewPhoto) {
                modalViewPhoto.show();
            }
        });
    });
</script>