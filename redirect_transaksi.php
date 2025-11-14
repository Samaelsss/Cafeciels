<?php
session_start();
// File ini akan menjadi perantara untuk pengalihan ke halaman transaksi

// Jika ada parameter kode transaksi
if (isset($_GET['kode']) && !empty($_GET['kode'])) {
    $kode_transaksi = trim($_GET['kode']);
    
    // Log untuk debugging
    error_log("Redirecting to transaction page with code from parameter: $kode_transaksi");
    
    // Simpan kode transaksi di session untuk berjaga-jaga
    $_SESSION['last_transaction_code'] = $kode_transaksi;
} 
// Jika tidak ada kode transaksi dari parameter tetapi ada di session
elseif (isset($_SESSION['last_transaction_code']) && !empty($_SESSION['last_transaction_code'])) {
    $kode_transaksi = $_SESSION['last_transaction_code'];
    error_log("Using last transaction code from session: $kode_transaksi");
} 
// Jika sama sekali tidak ada kode transaksi
else {
    error_log("No transaction code provided, redirecting to user page");
    header('Location: user.php');
    exit;
}

// Tambahkan logging untuk membantu debugging
error_log("REDIRECT_TRANSAKSI: Kode transaksi yang akan digunakan: '$kode_transaksi'");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mengalihkan...</title>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="1;url=transaksi_simple.php?kode=<?php echo urlencode($kode_transaksi); ?>&t=<?php echo time(); ?>">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
        }
        .loader {
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loader"></div>
    <h2>Memuat halaman transaksi...</h2>
    <p>Kode Transaksi: <strong><?php echo htmlspecialchars($kode_transaksi); ?></strong></p>
    <p>Jika halaman tidak otomatis teralihkan, <a href="transaksi_simple.php?kode=<?php echo urlencode($kode_transaksi); ?>&t=<?php echo time(); ?>">klik di sini</a>.</p>
    
    <script>
        // Pastikan pengalihan tetap terjadi meskipun meta refresh tidak bekerja
        setTimeout(function() {
            window.location.href = "transaksi_simple.php?kode=<?php echo urlencode($kode_transaksi); ?>&t=" + new Date().getTime();
        }, 1000);
    </script>
</body>
</html>
