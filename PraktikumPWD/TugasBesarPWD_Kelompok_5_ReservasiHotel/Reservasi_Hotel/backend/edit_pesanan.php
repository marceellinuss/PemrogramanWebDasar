<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../frontend/login.html");
    exit;
}

$tamu_id = (int)$_SESSION['user_id'];

if (!isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Pesanan tidak dipilih.");
}

$pesan_error  = '';
$pesan_sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = (int)$_POST['id'];
    $tanggal_checkin = trim($_POST['tanggal_checkin']);
    $tanggal_checkout= trim($_POST['tanggal_checkout']);

    $sql = "
        SELECT r.*, h.harga_per_malam
        FROM reservasi r
        JOIN hotel h ON r.hotel_id = h.id
        WHERE r.id = $id AND r.tamu_id = $tamu_id AND r.status = 'dipesan'
        LIMIT 1
    ";
    $hasil = mysqli_query($koneksi, $sql);
    if (mysqli_num_rows($hasil) === 0) {
        die("Pesanan tidak ditemukan atau sudah dibayar.");
    }
    $data = mysqli_fetch_assoc($hasil);
    $harga_per_malam = (float)$data['harga_per_malam'];

    if ($tanggal_checkin === '' || $tanggal_checkout === '') {
        $pesan_error = "Tanggal check-in dan check-out wajib diisi.";
    } else {
        $d1 = new DateTime($tanggal_checkin);
        $d2 = new DateTime($tanggal_checkout);
        $diff = $d1->diff($d2)->days;
        if ($diff <= 0) {
            $pesan_error = "Tanggal check-out harus setelah tanggal check-in.";
        } else {
            $jumlah_malam = $diff;
            $total_biaya  = $jumlah_malam * $harga_per_malam;

            $sql_up = "
                UPDATE reservasi
                SET tanggal_checkin = '$tanggal_checkin',
                    tanggal_checkout = '$tanggal_checkout',
                    jumlah_malam    = $jumlah_malam,
                    total_biaya     = $total_biaya
                WHERE id = $id AND tamu_id = $tamu_id AND status = 'dipesan'
            ";
            if (mysqli_query($koneksi, $sql_up)) {
                $pesan_sukses = "Pesanan berhasil diperbarui.";
            } else {
                $pesan_error = "Gagal memperbarui pesanan: " . mysqli_error($koneksi);
            }
        }
    }

    $sql2 = "
        SELECT r.*, h.nama_hotel, h.harga_per_malam
        FROM reservasi r
        JOIN hotel h ON r.hotel_id = h.id
        WHERE r.id = $id AND r.tamu_id = $tamu_id AND r.status = 'dipesan'
        LIMIT 1
    ";
    $hasil2 = mysqli_query($koneksi, $sql2);
    if (mysqli_num_rows($hasil2) === 0) {
        die("Pesanan tidak ditemukan setelah update.");
    }
    $reservasi = mysqli_fetch_assoc($hasil2);

} else {
    $id = (int)$_GET['id'];

    $sql = "
        SELECT r.*, h.nama_hotel, h.harga_per_malam
        FROM reservasi r
        JOIN hotel h ON r.hotel_id = h.id
        WHERE r.id = $id AND r.tamu_id = $tamu_id AND r.status = 'dipesan'
        LIMIT 1
    ";
    $hasil = mysqli_query($koneksi, $sql);
    if (mysqli_num_rows($hasil) === 0) {
        die("Pesanan tidak ditemukan atau sudah dibayar.");
    }
    $reservasi = mysqli_fetch_assoc($hasil);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Pesanan Hotel</title>
  <link rel="stylesheet" href="../frontend/aset/css/style.css">
</head>
<body>
  <div class="container">
    <h2>Edit Pesanan Hotel</h2>
    <p class="info-kecil">
      Hotel: <strong><?php echo htmlspecialchars($reservasi['nama_hotel']); ?></strong><br>
      Harga per malam: <strong>Rp <?php echo number_format($reservasi['harga_per_malam'], 0, ',', '.'); ?></strong>
    </p>

    <?php if ($pesan_sukses): ?>
      <div class="pesan-sukses"><?php echo $pesan_sukses; ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
      <div class="pesan-error"><?php echo $pesan_error; ?></div>
    <?php endif; ?>

    <form method="POST" action="edit_pesanan.php">
      <input type="hidden" name="id" value="<?php echo (int)$reservasi['id']; ?>">

      <label for="tanggal_checkin">Tanggal Check-in</label>
      <input type="date" id="tanggal_checkin" name="tanggal_checkin"
             value="<?php echo htmlspecialchars($reservasi['tanggal_checkin']); ?>" required>

      <label for="tanggal_checkout">Tanggal Check-out</label>
      <input type="date" id="tanggal_checkout" name="tanggal_checkout"
             value="<?php echo htmlspecialchars($reservasi['tanggal_checkout']); ?>" required>

      <label for="jumlah_malam">Jumlah Malam</label>
      <input type="text" id="jumlah_malam" class="field-readonly"
             value="<?php echo (int)$reservasi['jumlah_malam']; ?>" readonly>

      <label for="total_biaya">Total Biaya (Rp)</label>
      <input type="text" id="total_biaya" class="field-readonly"
             value="<?php echo number_format($reservasi['total_biaya'], 0, ',', '.'); ?>" readonly>

      <button type="submit" style="margin-top:10px;">Simpan Perubahan</button>
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
    const hargaPerMalam = <?php echo (float)$reservasi['harga_per_malam']; ?>;

    function hitungTotal() {
      const ci = inputCheckin.value;
      const co = inputCheckout.value;
      if (!ci || !co) return;

      const t1 = new Date(ci);
      const t2 = new Date(co);
      const ms = t2 - t1;
      const hari = ms / (1000*60*60*24);
      if (hari <= 0 || isNaN(hari)) return;

      inputMalam.value = hari;
      inputTotal.value = (hari * hargaPerMalam).toLocaleString('id-ID');
    }

    inputCheckin.addEventListener('change', hitungTotal);
    inputCheckout.addEventListener('change', hitungTotal);
  </script>
</body>
</html>
