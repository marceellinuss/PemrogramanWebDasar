<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../frontend/login.html");
    exit;
}

$tamu_id = (int)$_SESSION['user_id'];

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pesan';
$tab = in_array($tab, ['pesan', 'list', 'riwayat']) ? $tab : 'pesan';

$sql_profil = "
    SELECT u.username, t.nama_lengkap, t.foto_profil
    FROM users u
    LEFT JOIN tamu t ON t.user_id = u.id
    WHERE u.id = $tamu_id
    LIMIT 1
";
$hasil_profil = mysqli_query($koneksi, $sql_profil);
$data_profil  = mysqli_fetch_assoc($hasil_profil);

$nama_tamu = $data_profil && !empty($data_profil['nama_lengkap'])
    ? $data_profil['nama_lengkap']
    : ($data_profil['username'] ?? 'Tamu');

if ($data_profil && !empty($data_profil['foto_profil'])) {
    $foto_tamu = "../" . ltrim($data_profil['foto_profil'], '/');
} else {
    $foto_tamu = "../upload/default_black.png";
}

$sql_hotel = "
    SELECT id, nama_hotel, alamat, harga_per_malam,
           kamar_tersedia, latitude, longitude
    FROM hotel
    WHERE kamar_tersedia > 0
    ORDER BY nama_hotel ASC
";
$hasil_hotel = mysqli_query($koneksi, $sql_hotel);

$sql_pesanan_aktif = "
    SELECT r.*, h.nama_hotel, h.harga_per_malam
    FROM reservasi r
    JOIN hotel h ON r.hotel_id = h.id
    WHERE r.tamu_id = $tamu_id AND r.status = 'dipesan'
    ORDER BY r.dibuat_pada DESC
";
$hasil_pesanan_aktif = mysqli_query($koneksi, $sql_pesanan_aktif);

$sql_riwayat = "
    SELECT r.*, h.nama_hotel, h.harga_per_malam
    FROM reservasi r
    JOIN hotel h ON r.hotel_id = h.id
    WHERE r.tamu_id = $tamu_id AND r.status = 'dibayar'
    ORDER BY r.dibuat_pada DESC
";
$hasil_riwayat = mysqli_query($koneksi, $sql_riwayat);


?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Tamu</title>
  <link rel="stylesheet" href="../frontend/aset/css/style.css">
  <script src="../frontend/aset/js/dashboard_tamu.js"></script>
</head>
<body>
  <div class="layout-dashboard">
    <aside class="sidebar-kiri">
      <div class="sidebar-logo">
        <img src="../frontend/aset/icon/logo_hotel.png" alt="Logo NginapID" class="info-logo-dashboard">
        <span>Tamu</span>
      </div>

      <nav class="menu-dashboard">
        <a href="#" class="menu-item <?php echo ($tab === 'pesan' ? 'aktif' : ''); ?>"
          data-panel="panel-pesan">
          <img src="../frontend/aset/icon/logo_pesan_hotel.png" class="icon-menu">
          <h3>Pesan Hotel</h3>
        </a>
        <a href="#" class="menu-item <?php echo ($tab === 'list' ? 'aktif' : ''); ?>"
          data-panel="panel-list">
            <img src="../frontend/aset/icon/logo_list_pesanan.png" class="icon-menu">
            <h3>List Pesanan</h3>
        </a>
        <a href="#" class="menu-item <?php echo ($tab === 'riwayat' ? 'aktif' : ''); ?>"
          data-panel="panel-riwayat">
            <img src="../frontend/aset/icon/logo_riwayat.png" class="icon-menu">
            <h3>Riwayat Pesanan</h3>
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
          <img src="<?php echo htmlspecialchars($foto_tamu); ?>" alt="Foto Tamu" class="foto-akun">
          <div class="teks-akun">
            <span class="label-akun">Tamu Terdaftar</span>
            <span class="nama-akun"><?php echo htmlspecialchars($nama_tamu); ?></span>
          </div>
        </div>

        <a href="pengaturan_tamu.php" class="tombol-pengaturan" title="Ubah profil dan foto">
          <span class="ikon">⚙</span>
          <span>Pengaturan Profil</span>
        </a>
      </header>

      <main class="konten-kanan">
        <div class="konten-dalam">
          <h2 class="judul-halaman">Dashboard Tamu</h2>
          <p class="subjudul">
            Halo, ini adalah dashboard yang bisa kamu gunakan untuk memesan Hotel, melihat daftar Hotel yang telah kamu pesan, dan juga melihat riwayat pesanan Hotel kamu.
          </p>

          <section id="panel-pesan" class="panel-konten <?php echo ($tab === 'pesan' ? 'aktif' : ''); ?>">
            <h3 class="judul-halaman" style="font-size:1.1rem;">Pesan Hotel</h3>
            <p class="subjudul">
              Daftar hotel yang tersedia. Klik tombol <b>Pesan</b> untuk memilih tanggal check-in dan check-out.
            </p>

            <div class="tabel-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>Nama Hotel</th>
                    <th>Alamat</th>
                    <th>Harga/Malam</th>
                    <th>Kamar Tersedia</th>
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
                          <form method="GET" action="pesan_hotel.php">
                            <input type="hidden" name="hotel_id" value="<?php echo (int)$h['id']; ?>">
                            <button type="submit">Pesan</button>
                          </form>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6">Belum ada hotel yang tersedia.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <section id="panel-list" class="panel-konten <?php echo ($tab === 'list' ? 'aktif' : ''); ?>">
            <h3 class="judul-halaman" style="font-size:1.1rem;">List Pesanan Aktif</h3>
            <p class="subjudul">
              Pesanan yang masih berstatus <b>dipesan</b>. Di sini Anda dapat mengubah tanggal, menghapus, atau melakukan pembayaran.
            </p>

            <div class="tabel-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>Hotel</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Jumlah Malam</th>
                    <th>Total Biaya</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (mysqli_num_rows($hasil_pesanan_aktif) > 0): ?>
                    <?php while ($r = mysqli_fetch_assoc($hasil_pesanan_aktif)): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($r['nama_hotel']); ?></td>
                        <td><?php echo htmlspecialchars($r['tanggal_checkin']); ?></td>
                        <td><?php echo htmlspecialchars($r['tanggal_checkout']); ?></td>
                        <td><?php echo (int)$r['jumlah_malam']; ?></td>
                        <td>Rp <?php echo number_format($r['total_biaya'], 0, ',', '.'); ?></td>
                        <td class="aksi-tabel">
                          <form method="GET" action="edit_pesanan.php">
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                            <button type="submit">Edit</button>
                          </form>

                          <form method="POST" action="hapus_pesanan.php"
                                onsubmit="return confirm('Yakin ingin menghapus pesanan ini?');">
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                            <button type="submit" class="hapus">Hapus</button>
                          </form>

                          <form method="POST" action="bayar_pesanan.php"
                                onsubmit="return confirm('Lanjutkan pembayaran pesanan ini?');">
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                            <button type="submit" class="bayar">Bayar</button>
                          </form>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6">Belum ada pesanan aktif.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <section id="panel-riwayat" class="panel-konten <?php echo ($tab === 'riwayat' ? 'aktif' : ''); ?>">
            <h3 class="judul-halaman" style="font-size:1.1rem;">Riwayat Pesanan</h3>
            <p class="subjudul">
              Pesanan yang sudah <b>dibayar</b> akan muncul di sini sebagai riwayat.
            </p>

            <div class="tabel-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>Hotel</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Jumlah Malam</th>
                    <th>Total Biaya</th>
                    <th>Tanggal Pemesanan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (mysqli_num_rows($hasil_riwayat) > 0): ?>
                    <?php while ($r = mysqli_fetch_assoc($hasil_riwayat)): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($r['nama_hotel']); ?></td>
                        <td><?php echo htmlspecialchars($r['tanggal_checkin']); ?></td>
                        <td><?php echo htmlspecialchars($r['tanggal_checkout']); ?></td>
                        <td><?php echo (int)$r['jumlah_malam']; ?></td>
                        <td>Rp <?php echo number_format($r['total_biaya'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($r['dibuat_pada']); ?></td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6">Riwayat masih kosong.</td>
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
</body>
</html>