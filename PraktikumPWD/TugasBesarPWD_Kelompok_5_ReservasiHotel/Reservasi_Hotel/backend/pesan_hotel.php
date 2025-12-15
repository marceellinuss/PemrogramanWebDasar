<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../frontend/login.html");
    exit;
}

$tamu_id = (int)$_SESSION['user_id'];

if (!isset($_GET['hotel_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Hotel tidak dipilih.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotel_id        = (int)$_POST['hotel_id'];
    $tanggal_checkin = trim($_POST['tanggal_checkin']);
    $tanggal_checkout= trim($_POST['tanggal_checkout']);

    $sql_hotel = "SELECT harga_per_malam FROM hotel WHERE id = $hotel_id LIMIT 1";
    $hasil_h = mysqli_query($koneksi, $sql_hotel);
    if (mysqli_num_rows($hasil_h) === 0) {
        die("Hotel tidak ditemukan.");
    }
    $data_hotel = mysqli_fetch_assoc($hasil_h);
    $harga_per_malam = (float)$data_hotel['harga_per_malam'];

    if ($tanggal_checkin === '' || $tanggal_checkout === '') {
        die("Tanggal check-in dan check-out wajib diisi.");
    }

    $d1 = new DateTime($tanggal_checkin);
    $d2 = new DateTime($tanggal_checkout);
    $diff = $d1->diff($d2)->days;

    if ($diff <= 0) {
        die("Tanggal check-out harus setelah tanggal check-in.");
    }

    $jumlah_malam = $diff;
    $total_biaya  = $jumlah_malam * $harga_per_malam;

    $sql_insert = "
        INSERT INTO reservasi
        (tamu_id, hotel_id, tanggal_checkin, tanggal_checkout,
         jumlah_malam, total_biaya, status)
        VALUES (
            $tamu_id,
            $hotel_id,
            '$tanggal_checkin',
            '$tanggal_checkout',
            $jumlah_malam,
            $total_biaya,
            'dipesan'
        )
    ";

    if (mysqli_query($koneksi, $sql_insert)) {
        header("Location: dashboard_tamu.php");
        exit;
    } else {
        die("Gagal menyimpan pesanan: " . mysqli_error($koneksi));
    }

} else {
    $hotel_id = (int)$_GET['hotel_id'];

    $sql_hotel = "
        SELECT id, nama_hotel, alamat, harga_per_malam, kamar_tersedia
        FROM hotel
        WHERE id = $hotel_id
        LIMIT 1
    ";
    $hasil_h = mysqli_query($koneksi, $sql_hotel);
    if (mysqli_num_rows($hasil_h) === 0) {
        die("Hotel tidak ditemukan.");
    }
    $hotel = mysqli_fetch_assoc($hasil_h);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pesan Hotel</title>
  <link rel="stylesheet" href="../frontend/aset/css/style.css">
</head>
<body>
  <div class="container">
    <h2>Pesan Hotel</h2>
    <p class="info-kecil">
      Hotel: <strong><?php echo htmlspecialchars($hotel['nama_hotel']); ?></strong><br>
      Alamat: <?php echo htmlspecialchars($hotel['alamat']); ?><br>
      Harga per malam: <strong>Rp <?php echo number_format($hotel['harga_per_malam'], 0, ',', '.'); ?></strong><br>
      Kamar tersedia: <?php echo (int)$hotel['kamar_tersedia']; ?>
    </p>

    <form method="POST" action="pesan_hotel.php">
      <input type="hidden" name="hotel_id" value="<?php echo (int)$hotel['id']; ?>">

      <label for="tanggal_checkin">Tanggal Check-in</label>
      <input type="date" id="tanggal_checkin" name="tanggal_checkin" required>

      <label for="tanggal_checkout">Tanggal Check-out</label>
      <input type="date" id="tanggal_checkout" name="tanggal_checkout" required>

      <label for="jumlah_malam">Jumlah Malam</label>
      <input type="text" id="jumlah_malam" class="field-readonly" readonly>

      <label for="total_biaya">Total Biaya (Rp)</label>
      <input type="text" id="total_biaya" class="field-readonly" readonly>

      <button type="submit" style="margin-top:10px;">Simpan Pesanan</button>
    </form>

    <p style="margin-top:10px; font-size:0.9rem;">
      <a href="dashboard_tamu.php">Kembali ke Dashboard</a>
    </p>
  </div>

  <script>
    const inputCheckin  = document.getElementById('tanggal_checkin');
    const inputCheckout = document.getElementById('tanggal_checkout');
    const inputMalam    = document.getElementById('jumlah_malam');
    const inputTotal    = document.getElementById('total_biaya');
    const hargaPerMalam = <?php echo (float)$hotel['harga_per_malam']; ?>;

    function hitungTotal() {
      const ci = inputCheckin.value;
      const co = inputCheckout.value;
      if (!ci || !co) {
        inputMalam.value = '';
        inputTotal.value = '';
        return;
      }
      const t1 = new Date(ci);
      const t2 = new Date(co);
      const ms = t2 - t1;
      const hari = ms / (1000 * 60 * 60 * 24);
      if (hari <= 0 || isNaN(hari)) {
        inputMalam.value = '';
        inputTotal.value = '';
        return;
      }
      inputMalam.value = hari;
      inputTotal.value = (hari * hargaPerMalam).toLocaleString('id-ID');
    }

    inputCheckin.addEventListener('change', hitungTotal);
    inputCheckout.addEventListener('change', hitungTotal);
  </script>
</body>
</html>
