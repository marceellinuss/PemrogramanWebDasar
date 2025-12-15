<?php
session_start();
require 'koneksi.php';
function tampil_halaman_pesan($judul, $isi_html) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title><?php echo htmlspecialchars($judul); ?></title>
        <link rel="stylesheet" href="../frontend/aset/css/style.css">
    </head>
    <body>
        <div class="container">
            <h2><?php echo htmlspecialchars($judul); ?></h2>
            <p><?php echo $isi_html; ?></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../frontend/login.html");
    exit;
}

$tamu_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard_tamu.php");
    exit;
}

$id_pesanan = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id_pesanan <= 0) {
    tampil_halaman_pesan("Pesanan Tidak Valid",
        "ID pesanan tidak dikenali.<br><br>" .
        "<a href=\"dashboard_tamu.php\" class=\"tautan-tombol\">Kembali ke Dashboard</a>");
}

$sql = "
    SELECT r.*, h.nama_hotel, h.kamar_tersedia, h.id AS hotel_id
    FROM reservasi r
    JOIN hotel h ON r.hotel_id = h.id
    WHERE r.id = $id_pesanan
      AND r.tamu_id = $tamu_id
      AND r.status = 'dipesan'
    LIMIT 1
";
$hasil = mysqli_query($koneksi, $sql);
$data = mysqli_fetch_assoc($hasil);

if (!$data) {
    tampil_halaman_pesan("Pesanan Tidak Ditemukan",
        "Pesanan ini tidak ditemukan atau sudah dibayar/dibatalkan.<br><br>" .
        "<a href=\"dashboard_tamu.php\" class=\"tautan-tombol\">Kembali ke Dashboard</a>");
}

$kamar_tersedia = (int)$data['kamar_tersedia'];
$nama_hotel     = $data['nama_hotel'];
$hotel_id       = (int)$data['hotel_id'];

if ($kamar_tersedia <= 0) {
    $isi  = "Maaf, kamar di hotel <b>" . htmlspecialchars($nama_hotel) . "</b> sudah habis.<br><br>";
    $isi .= "<a href=\"dashboard_tamu.php\" class=\"tautan-tombol\">Kembali ke Dashboard</a>";
    tampil_halaman_pesan("Kamar Habis", $isi);
}

$sql_update_hotel = "
    UPDATE hotel
    SET kamar_tersedia = kamar_tersedia - 1
    WHERE id = $hotel_id AND kamar_tersedia > 0
";
mysqli_query($koneksi, $sql_update_hotel);

if (mysqli_affected_rows($koneksi) === 0) {
    $isi  = "Maaf, kamar di hotel <b>" . htmlspecialchars($nama_hotel) . "</b> sudah habis.<br><br>";
    $isi .= "<a href=\"dashboard_tamu.php\" class=\"tautan-tombol\">Kembali ke Dashboard</a>";
    tampil_halaman_pesan("Kamar Habis", $isi);
}

$sql_update_reservasi = "
    UPDATE reservasi
    SET status = 'dibayar'
    WHERE id = $id_pesanan
";
if (!mysqli_query($koneksi, $sql_update_reservasi)) {
    $isi  = "Terjadi kesalahan saat menyimpan status pembayaran.<br>";
    $isi .= "Silakan hubungi admin sistem.<br><br>";
    $isi .= "<a href=\"dashboard_tamu.php\" class=\"tautan-tombol\">Kembali ke Dashboard</a>";
    tampil_halaman_pesan("Kesalahan Sistem", $isi);
}

$isi  = "Pembayaran untuk hotel <b>" . htmlspecialchars($nama_hotel) . "</b> berhasil dicatat.<br><br>";
$isi .= "<a href=\"dashboard_tamu.php\" class=\"tautan-tombol\">Kembali ke Dashboard</a>";
$isi .= " <a href=\"dashboard_tamu.php?tab=riwayat\" class=\"tautan-tombol secunder\">Lihat Riwayat Pesanan</a>";
tampil_halaman_pesan("Pembayaran Berhasil", $isi);

