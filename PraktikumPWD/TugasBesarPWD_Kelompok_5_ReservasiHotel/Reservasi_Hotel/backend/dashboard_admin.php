<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../frontend/login_admin.html");
    exit;
}

$admin_id = (int)$_SESSION['user_id'];

$pesan_error  = '';
$pesan_sukses = '';

if (isset($_SESSION['pesan_sukses'])) {
    $pesan_sukses = $_SESSION['pesan_sukses'];
    unset($_SESSION['pesan_sukses']);
}

$sql_profil = "
    SELECT u.username, u.email, a.nama_lengkap, a.foto_profil
    FROM users u
    LEFT JOIN admins a ON a.user_id = u.id
    WHERE u.id = $admin_id
    LIMIT 1
";
$hasil_profil = mysqli_query($koneksi, $sql_profil);
$data_profil  = mysqli_fetch_assoc($hasil_profil);

$nama_admin = $data_profil && !empty($data_profil['nama_lengkap'])
    ? $data_profil['nama_lengkap']
    : ($data_profil['username'] ?? 'Admin');

if ($data_profil && !empty($data_profil['foto_profil'])) {
    $foto_admin = "../" . ltrim($data_profil['foto_profil'], '/');
} else {
    $foto_admin = "../upload/default_black.png";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_tambah_hotel'])) {
    $nama_hotel      = mysqli_real_escape_string($koneksi, trim($_POST['nama_hotel']));
    $deskripsi       = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi']));
    $alamat          = mysqli_real_escape_string($koneksi, trim($_POST['alamat']));
    $harga_per_malam = (float)$_POST['harga_per_malam'];
    $total_kamar     = (int)$_POST['total_kamar'];
    $latitude        = trim($_POST['latitude']);
    $longitude       = trim($_POST['longitude']);

    if ($nama_hotel === '' || $alamat === '') {
        $pesan_error = "Nama hotel dan alamat wajib diisi.";
    } elseif ($harga_per_malam <= 0 || $total_kamar <= 0) {
        $pesan_error = "Harga per malam dan total kamar harus lebih besar dari 0.";
    } else {
        $lat_sql = ($latitude !== '' ? (float)$latitude : null);
        $lon_sql = ($longitude !== '' ? (float)$longitude : null);

        $sql_insert = "
            INSERT INTO hotel
            (nama_hotel, deskripsi, alamat, harga_per_malam,
             total_kamar, kamar_tersedia, latitude, longitude, pemilik_id)
            VALUES (
                '$nama_hotel',
                '$deskripsi',
                '$alamat',
                $harga_per_malam,
                $total_kamar,
                $total_kamar,
                " . ($lat_sql !== null ? $lat_sql : "NULL") . ",
                " . ($lon_sql !== null ? $lon_sql : "NULL") . ",
                $admin_id
            )
        ";

        if (mysqli_query($koneksi, $sql_insert)) {
            $_SESSION['pesan_sukses'] = "Hotel berhasil ditambahkan.";
            header("Location: dashboard_admin.php");
            exit;
        } else {
            $pesan_error = "Gagal menambahkan hotel: " . mysqli_error($koneksi);
        }
    }
}

$sql_hotel = "
    SELECT id, nama_hotel, alamat, harga_per_malam,
           total_kamar, kamar_tersedia, latitude, longitude
    FROM hotel
    WHERE pemilik_id = $admin_id
    ORDER BY nama_hotel ASC
";
$hasil_hotel = mysqli_query($koneksi, $sql_hotel);

$sql_tamu = "
    SELECT
        r.id AS id_reservasi,
        h.nama_hotel,
        r.tanggal_checkin,
        r.tanggal_checkout,
        r.jumlah_malam,
        r.status,
        r.total_biaya,
        t.nama_lengkap AS nama_tamu,
        u.email AS email_tamu
    FROM reservasi r
    JOIN hotel h ON r.hotel_id = h.id
    JOIN tamu t ON r.tamu_id = t.user_id
    JOIN users u ON t.user_id = u.id
    WHERE h.pemilik_id = $admin_id
    ORDER BY r.dibuat_pada DESC
";
$hasil_tamu = mysqli_query($koneksi, $sql_tamu);

$sql_total = "
    SELECT COALESCE(SUM(r.total_biaya), 0) AS total_pendapatan
    FROM reservasi r
    JOIN hotel h ON r.hotel_id = h.id
    WHERE h.pemilik_id = $admin_id
      AND r.status = 'dibayar'
";
$hasil_total = mysqli_query($koneksi, $sql_total);
$data_total  = mysqli_fetch_assoc($hasil_total);
$total_pendapatan = (float)$data_total['total_pendapatan'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Pemilik Hotel</title>
  <link rel="stylesheet" href="../frontend/aset/css/style.css">
  <link rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        crossorigin=""/>
  <script src="../frontend/aset/js/dashboard_admin.js"></script>
</head>
<body>
  <div class="layout-dashboard">

    <aside class="sidebar-kiri">
      <div class="sidebar-logo">
        <img src="../frontend/aset/icon/logo_hotel.png" alt="Logo NginapID" class="info-logo-dashboard">
        <span>Pemilik Hotel</span>
      </div>

      <nav class="menu-dashboard">
        <a href="#" class="menu-item aktif" data-panel="panel-tambah">
          <img src="../frontend/aset/icon/logo_tambah_hotel.png" class="icon-menu" alt="Tambah Hotel">
          <h3>Tambah Hotel</h3>
        </a>

        <a href="#" class="menu-item" data-panel="panel-daftar">
          <img src="../frontend/aset/icon/logo_daftar_hotel.png" class="icon-menu" alt="Daftar Hotel">
          <h3>Daftar Hotel</h3>
        </a>

        <a href="#" class="menu-item" data-panel="panel-tamu">
          <img src="../frontend/aset/icon/logo_data_tamu.png" class="icon-menu" alt="Data Tamu">
          <h3>Data Tamu</h3>
        </a>
      </nav>

      <a href="../frontend/login.html" class="menu-item keluar">
        <img src="../frontend/aset/icon/logo_keluar.png" class="icon-menu">
        <h4>KELUAR</h4>
      </a>
    </aside>

    <div class="area-kanan">
      <header class="navbar-atas">
        <div class="info-akun">
          <img src="<?php echo htmlspecialchars($foto_admin); ?>" alt="Foto Admin" class="foto-akun">
          <div class="teks-akun">
            <span class="label-akun">Pemilik Hotel</span>
            <span class="nama-akun"><?php echo htmlspecialchars($nama_admin); ?></span>
          </div>
        </div>

        <a href="pengaturan_admin.php" class="tombol-pengaturan" title="Ubah profil dan foto">
          <span class="ikon">⚙</span>
          <span>Pengaturan Profil</span>
        </a>
      </header>

      <main class="konten-kanan">
        <div class="konten-dalam">
          <h2 class="judul-halaman">Dashboard Pemilik Hotel</h2>
          <p class="subjudul">
            Kelola data hotel dan pantau tamu yang melakukan reservasi di hotel Anda.
          </p>

          <?php if ($pesan_sukses): ?>
            <div class="pesan-sukses"><?php echo $pesan_sukses; ?></div>
          <?php endif; ?>
          <?php if ($pesan_error): ?>
            <div class="pesan-error"><?php echo $pesan_error; ?></div>
          <?php endif; ?>

          <section id="panel-tambah" class="panel-konten aktif">
            <h3 class="judul-halaman" style="font-size:1.1rem;">Tambah Hotel Baru</h3>
            <p class="subjudul">
              Isi informasi hotel dan pilih titik lokasi pada peta untuk menyimpan koordinat hotel.
            </p>

            <form method="POST" action="dashboard_admin.php">
              <input type="hidden" name="form_tambah_hotel" value="1">

              <label for="nama_hotel">Nama Hotel</label>
              <input type="text" id="nama_hotel" name="nama_hotel" required>

              <label for="alamat">Alamat</label>
              <textarea id="alamat" name="alamat" rows="3" required></textarea>

              <label for="deskripsi">Deskripsi</label>
              <textarea id="deskripsi" name="deskripsi" rows="3"></textarea>

              <label for="harga_per_malam">Harga per Malam (Rp)</label>
              <input type="number" id="harga_per_malam" name="harga_per_malam" min="0" step="1000" required>

              <label for="total_kamar">Total Kamar</label>
              <input type="number" id="total_kamar" name="total_kamar" min="1" step="1" required>

              <label for="latitude">Latitude (klik di peta)</label>
              <input type="text" id="latitude" name="latitude" readonly>

              <label for="longitude">Longitude (klik di peta)</label>
              <input type="text" id="longitude" name="longitude" readonly>

              <label>Pilih lokasi hotel pada peta:</label>
              <div id="mapTambah"></div>

              <button type="submit" style="margin-top:12px;">Simpan Hotel</button>
            </form>
          </section>

          <section id="panel-daftar" class="panel-konten">
            <h3 class="judul-halaman" style="font-size:1.1rem;">Daftar Hotel Saya</h3>
            <p class="subjudul">
              Berikut adalah hotel-hotel yang terdaftar atas nama Anda. Anda dapat mengubah atau menghapus data hotel.
            </p>

            <div class="tabel-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>Nama Hotel</th>
                    <th>Alamat</th>
                    <th>Harga/Malam</th>
                    <th>Total Kamar</th>
                    <th>Tersedia</th>
                    <th>Lokasi</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (mysqli_num_rows($hasil_hotel) > 0): ?>
                    <?php while ($h = mysqli_fetch_assoc($hasil_hotel)): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($h['nama_hotel']); ?></td>
                        <td><?php echo htmlspecialchars($h['alamat']); ?></td>
                        <td>Rp <?php echo number_format($h['harga_per_malam'], 0, ',', '.'); ?></td>
                        <td><?php echo (int)$h['total_kamar']; ?></td>
                        <td><?php echo (int)$h['kamar_tersedia']; ?></td>
                        <td>
                          <?php if (!is_null($h['latitude']) && !is_null($h['longitude'])): ?>
                            <a href="https://www.google.com/maps?q=<?php
                              echo $h['latitude'] . ',' . $h['longitude'];
                            ?>" target="_blank" style="font-size:0.8rem;">
                              Lihat Peta
                            </a>
                          <?php else: ?>
                            -
                          <?php endif; ?>
                        </td>
                        <td class="aksi-tabel">
                          <form method="GET" action="edit_hotel.php">
                            <input type="hidden" name="id" value="<?php echo (int)$h['id']; ?>">
                            <button type="submit">Edit</button>
                          </form>

                          <form method="POST" action="hapus_hotel.php"
                                onsubmit="return confirm('Yakin ingin menghapus hotel ini?');">
                            <input type="hidden" name="id" value="<?php echo (int)$h['id']; ?>">
                            <button type="submit" class="hapus">Hapus</button>
                          </form>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7">Belum ada hotel yang Anda daftarkan.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <section id="panel-tamu" class="panel-konten">
                    
            <div class="kotak-pendapatan">
              <div class="label-pendapatan">
                Total Pendapatan (reservasi yang sudah dibayar)
              </div>
              <div class="angka-pendapatan">
                Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
              </div>
            </div>
            
            <h3 class="judul-halaman" style="font-size:1.1rem;">Data Tamu yang Memesan Hotel Anda</h3>
            <p class="subjudul">
              Di bawah ini adalah tamu-tamu yang telah melakukan reservasi pada hotel-hotel milik Anda.
            </p>

            <div class="tabel-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>ID Reservasi</th>
                    <th>Nama Tamu</th>
                    <th>Email Tamu</th>
                    <th>Hotel</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Malam</th>
                    <th>Status</th>
                    <th>Total Biaya</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (mysqli_num_rows($hasil_tamu) > 0): ?>
                    <?php while ($r = mysqli_fetch_assoc($hasil_tamu)): ?>
                      <tr>
                        <td><?php echo (int)$r['id_reservasi']; ?></td>
                        <td><?php echo htmlspecialchars($r['nama_tamu']); ?></td>
                        <td><?php echo htmlspecialchars($r['email_tamu']); ?></td>
                        <td><?php echo htmlspecialchars($r['nama_hotel']); ?></td>
                        <td><?php echo htmlspecialchars($r['tanggal_checkin']); ?></td>
                        <td><?php echo htmlspecialchars($r['tanggal_checkout']); ?></td>
                        <td><?php echo (int)$r['jumlah_malam']; ?></td>
                        <td><?php echo htmlspecialchars($r['status']); ?></td>
                        <td>Rp <?php echo number_format($r['total_biaya'], 0, ',', '.'); ?></td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="9">Belum ada tamu yang melakukan reservasi di hotel Anda.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

        </div>
      </main>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
          crossorigin=""></script>

</body>
</html>
