<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../frontend/login_admin.html");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_GET['id']) && !isset($_POST['id'])) {
    die("Hotel tidak dipilih.");
}

$hotel_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_POST['id'];

$pesan_error  = '';
$pesan_sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_hotel      = mysqli_real_escape_string($koneksi, trim($_POST['nama_hotel']));
    $deskripsi       = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi']));
    $alamat          = mysqli_real_escape_string($koneksi, trim($_POST['alamat']));
    $harga_per_malam = (float)$_POST['harga_per_malam'];
    $total_kamar     = (int)$_POST['total_kamar'];
    $kamar_tersedia  = (int)$_POST['kamar_tersedia'];
    $latitude        = trim($_POST['latitude']);
    $longitude       = trim($_POST['longitude']);

    if ($nama_hotel === '' || $alamat === '') {
        $pesan_error = "Nama hotel dan alamat wajib diisi.";
    } elseif ($harga_per_malam <= 0 || $total_kamar <= 0) {
        $pesan_error = "Harga per malam dan total kamar harus lebih besar dari 0.";
    } elseif ($kamar_tersedia < 0 || $kamar_tersedia > $total_kamar) {
        $pesan_error = "Kamar tersedia tidak boleh negatif dan tidak boleh lebih besar dari total kamar.";
    } else {
        $lat_sql = ($latitude !== '' ? (float)$latitude : null);
        $lon_sql = ($longitude !== '' ? (float)$longitude : null);

        $sql_update = "
            UPDATE hotel
            SET nama_hotel      = '$nama_hotel',
                deskripsi       = '$deskripsi',
                alamat          = '$alamat',
                harga_per_malam = $harga_per_malam,
                total_kamar     = $total_kamar,
                kamar_tersedia  = $kamar_tersedia,
                latitude        = " . ($lat_sql !== null ? $lat_sql : "NULL") . ",
                longitude       = " . ($lon_sql !== null ? $lon_sql : "NULL") . "
            WHERE id = $hotel_id AND pemilik_id = $user_id
        ";

        if (mysqli_query($koneksi, $sql_update)) {
            $pesan_sukses = "Data hotel berhasil diperbarui.";
        } else {
            $pesan_error = "Gagal memperbarui hotel: " . mysqli_error($koneksi);
        }
    }
}

$sql_hotel = "
    SELECT *
    FROM hotel
    WHERE id = $hotel_id AND pemilik_id = $user_id
    LIMIT 1
";
$hasil_hotel = mysqli_query($koneksi, $sql_hotel);

if (mysqli_num_rows($hasil_hotel) === 0) {
    die("Hotel tidak ditemukan atau bukan milik Anda.");
}
$hotel = mysqli_fetch_assoc($hasil_hotel);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Hotel</title>
  <link rel="stylesheet" href="../frontend/aset/css/style.css">
  <link rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        crossorigin=""/>
</head>
<body>
  <div class="container">
    <h2>Edit Data Hotel</h2>
    <p class="info-kecil">
      Perbarui informasi hotel Anda pada form berikut.  
      Untuk mengubah lokasi, klik posisi baru pada peta.
    </p>

    <?php if ($pesan_sukses): ?>
      <div class="pesan-sukses"><?php echo $pesan_sukses; ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
      <div class="pesan-error"><?php echo $pesan_error; ?></div>
    <?php endif; ?>

    <form method="POST" action="edit_hotel.php">
      <input type="hidden" name="id" value="<?php echo (int)$hotel['id']; ?>">

      <label for="nama_hotel">Nama Hotel</label>
      <input type="text" id="nama_hotel" name="nama_hotel"
             value="<?php echo htmlspecialchars($hotel['nama_hotel']); ?>" required>

      <label for="alamat">Alamat</label>
      <textarea id="alamat" name="alamat" rows="3" required><?php
        echo htmlspecialchars($hotel['alamat']);
      ?></textarea>

      <label for="deskripsi">Deskripsi</label>
      <textarea id="deskripsi" name="deskripsi" rows="3"><?php
        echo htmlspecialchars($hotel['deskripsi']);
      ?></textarea>

      <label for="harga_per_malam">Harga per Malam (Rp)</label>
      <input type="number" id="harga_per_malam" name="harga_per_malam"
             min="0" step="1000"
             value="<?php echo (float)$hotel['harga_per_malam']; ?>" required>

      <label for="total_kamar">Total Kamar</label>
      <input type="number" id="total_kamar" name="total_kamar"
             min="1" step="1"
             value="<?php echo (int)$hotel['total_kamar']; ?>" required>

      <label for="kamar_tersedia">Kamar Tersedia</label>
      <input type="number" id="kamar_tersedia" name="kamar_tersedia"
             min="0" step="1"
             value="<?php echo (int)$hotel['kamar_tersedia']; ?>" required>

      <label for="latitude">Latitude (klik di peta)</label>
      <input type="text" id="latitude" name="latitude"
             value="<?php echo htmlspecialchars($hotel['latitude']); ?>" readonly>

      <label for="longitude">Longitude (klik di peta)</label>
      <input type="text" id="longitude" name="longitude"
             value="<?php echo htmlspecialchars($hotel['longitude']); ?>" readonly>

      <label>Pilih lokasi hotel pada peta:</label>
      <div id="mapEdit"></div>

      <button type="submit" style="margin-top:12px;">Simpan Perubahan</button>
    </form>

    <p style="margin-top:10px; font-size:0.9rem;">
      <a href="dashboard_admin.php">Kembali ke Dashboard Pemilik Hotel</a>
    </p>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
          crossorigin=""></script>
  <script>
    const latAwal = "<?php echo $hotel['latitude'] !== null ? $hotel['latitude'] : ''; ?>";
    const lngAwal = "<?php echo $hotel['longitude'] !== null ? $hotel['longitude'] : ''; ?>";

    let startLat = -6.2;
    let startLng = 106.8;
    let startZoom = 11;

    if (latAwal !== '' && lngAwal !== '') {
      startLat = parseFloat(latAwal);
      startLng = parseFloat(lngAwal);
      startZoom = 14;
    }

    var map = L.map('mapEdit').setView([startLat, startLng], startZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var marker = null;

    if (latAwal !== '' && lngAwal !== '') {
      marker = L.marker([parseFloat(latAwal), parseFloat(lngAwal)]).addTo(map);
    }

    map.on('click', function(e) {
      var lat = e.latlng.lat.toFixed(6);
      var lng = e.latlng.lng.toFixed(6);

      document.getElementById('latitude').value = lat;
      document.getElementById('longitude').value = lng;

      if (marker) {
        map.removeLayer(marker);
      }
      marker = L.marker(e.latlng).addTo(map);
    });
  </script>
</body>
</html>
