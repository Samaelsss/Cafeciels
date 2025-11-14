<?php
session_start();
require_once 'manage_transaksi.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

// Create laporan table if not exists
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create laporan table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS laporan_transaksi (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(255) NOT NULL,
        tanggal_mulai DATE NOT NULL,
        tanggal_akhir DATE NOT NULL,
        tanggal_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        total_transaksi INT(11) NOT NULL,
        total_pendapatan DECIMAL(10,2) NOT NULL,
        file_path VARCHAR(255) NULL
    )";
    
    $conn->exec($sql);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Process form submission
$laporan = null;
$message = "";
$start_date = "";
$end_date = "";
$title = "Laporan Transaksi";
$total_pendapatan = 0;

// Handle viewing a saved report
if (isset($_GET['view_report']) && is_numeric($_GET['view_report'])) {
    try {
        // Get report data
        $stmt = $conn->prepare("SELECT * FROM laporan_transaksi WHERE id = :id");
        $stmt->execute([':id' => $_GET['view_report']]);
        $report_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($report_data) {
            $start_date = $report_data['tanggal_mulai'];
            $end_date = $report_data['tanggal_akhir'];
            $title = $report_data['judul'];
            $total_pendapatan = $report_data['total_pendapatan'];
            
            // Get transactions for this date range
            $stmt = $conn->prepare("
                SELECT t.*, p.total_bayar, p.metode_pembayaran, p.status_pembayaran, p.created_at as tanggal_pembayaran
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                WHERE DATE(t.created_at) BETWEEN :start_date AND :end_date
                ORDER BY t.created_at DESC
            ");
            
            $stmt->execute([
                ':start_date' => $start_date,
                ':end_date' => $end_date
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by kode_transaksi
            $grouped_transactions = [];
            
            foreach ($results as $item) {
                $kode = $item['kode_transaksi'];
                if (!isset($grouped_transactions[$kode])) {
                    $grouped_transactions[$kode] = [
                        'kode_transaksi' => $kode,
                        'atas_nama' => $item['atas_nama'],
                        'tanggal' => $item['created_at'],
                        'total_bayar' => $item['total_bayar'],
                        'metode_pembayaran' => $item['metode_pembayaran'],
                        'status_pembayaran' => $item['status_pembayaran'],
                        'items' => []
                    ];
                }
                
                $grouped_transactions[$kode]['items'][] = [
                    'nama_barang' => $item['nama_barang'],
                    'quantity' => $item['quantity'],
                    'harga' => $item['harga'],
                    'subtotal' => $item['subtotal']
                ];
            }
            
            $laporan = $grouped_transactions;
            $message = "Menampilkan laporan: " . $report_data['judul'];
        } else {
            $message = "Laporan tidak ditemukan";
        }
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $judul_laporan = $_POST['judul_laporan'] ?? 'Laporan Transaksi ' . date('d/m/Y');
    $title = $judul_laporan;
    
    // Validate dates
    if (empty($start_date) || empty($end_date)) {
        $message = "Silakan pilih tanggal mulai dan tanggal akhir";
    } else {
        try {
            // Query to get transactions between dates
            $stmt = $conn->prepare("
                SELECT t.*, p.total_bayar, p.metode_pembayaran, p.status_pembayaran, p.created_at as tanggal_pembayaran
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                WHERE DATE(t.created_at) BETWEEN :start_date AND :end_date
                ORDER BY t.created_at DESC
            ");
            
            $stmt->execute([
                ':start_date' => $start_date,
                ':end_date' => $end_date
            ]);
            
            $laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by kode_transaksi
            $grouped_transactions = [];
            $total_pendapatan = 0;
            
            foreach ($laporan as $item) {
                $kode = $item['kode_transaksi'];
                if (!isset($grouped_transactions[$kode])) {
                    $grouped_transactions[$kode] = [
                        'kode_transaksi' => $kode,
                        'atas_nama' => $item['atas_nama'],
                        'tanggal' => $item['created_at'],
                        'total_bayar' => $item['total_bayar'],
                        'metode_pembayaran' => $item['metode_pembayaran'],
                        'status_pembayaran' => $item['status_pembayaran'],
                        'items' => []
                    ];
                }
                
                $grouped_transactions[$kode]['items'][] = [
                    'nama_barang' => $item['nama_barang'],
                    'quantity' => $item['quantity'],
                    'harga' => $item['harga'],
                    'subtotal' => $item['subtotal']
                ];
                
                // Add to total if not already counted
                if (count($grouped_transactions[$kode]['items']) === 1) {
                    $total_pendapatan += $item['total_bayar'] ?? $item['subtotal'];
                }
            }
            
            // If save report is checked, save to database
            if (isset($_POST['save_report']) && $_POST['save_report'] === 'yes') {
                $stmt = $conn->prepare("INSERT INTO laporan_transaksi 
                    (judul, tanggal_mulai, tanggal_akhir, total_transaksi, total_pendapatan) 
                    VALUES (:judul, :tanggal_mulai, :tanggal_akhir, :total_transaksi, :total_pendapatan)");
                
                $stmt->execute([
                    ':judul' => $judul_laporan,
                    ':tanggal_mulai' => $start_date,
                    ':tanggal_akhir' => $end_date,
                    ':total_transaksi' => count($grouped_transactions),
                    ':total_pendapatan' => $total_pendapatan
                ]);
                
                $message = "Laporan berhasil disimpan!";
            }
            
            // Return the grouped transactions for display
            $laporan = $grouped_transactions;
            
            if (empty($laporan)) {
                $message = "Tidak ada transaksi dalam rentang tanggal yang dipilih";
            }
            
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Get saved reports
$saved_reports = [];
try {
    $stmt = $conn->prepare("SELECT * FROM laporan_transaksi ORDER BY tanggal_dibuat DESC");
    $stmt->execute();
    $saved_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message .= " Error mengambil laporan tersimpan: " . $e->getMessage();
}

// Handle report deletion
if (isset($_GET['delete_report']) && is_numeric($_GET['delete_report'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM laporan_transaksi WHERE id = :id");
        $stmt->execute([':id' => $_GET['delete_report']]);
        
        $message = "Laporan berhasil dihapus!";
        
        // Redirect to remove the delete_report parameter from URL
        header("Location: laporan_transaksi.php?deleted=true");
        exit;
    } catch(PDOException $e) {
        $message = "Error menghapus laporan: " . $e->getMessage();
    }
}

// Set message if redirected after deletion
if (isset($_GET['deleted']) && $_GET['deleted'] === 'true') {
    $message = "Laporan berhasil dihapus!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Cafe Ciels</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .report-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .transaction-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .transaction-items {
            margin-left: 20px;
        }
        .print-button {
            margin-top: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
            .container {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="admin_home.php" class="btn btn-secondary no-print">
                        <i class="bi bi-arrow-left"></i> Kembali ke Menu
                    </a>
                    <h2 class="text-center mb-0"><?php echo htmlspecialchars($title); ?></h2>
                    <div style="width: 100px;"></div> <!-- Spacer for alignment -->
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Date Range Form -->
                <div class="card no-print">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Pilih Rentang Tanggal</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="judul_laporan" class="form-label">Judul Laporan</label>
                                <input type="text" class="form-control" id="judul_laporan" name="judul_laporan" 
                                    value="<?php echo isset($_POST['judul_laporan']) ? htmlspecialchars($_POST['judul_laporan']) : 'Laporan Transaksi ' . date('d/m/Y'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                    value="<?php echo $start_date; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                    value="<?php echo $end_date; ?>" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" name="generate_report" class="btn btn-primary">Buat Laporan</button>
                            </div>
                            <div class="col-md-12 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="save_report" name="save_report" value="yes" checked>
                                    <label class="form-check-label" for="save_report">
                                        Simpan laporan ini
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Saved Reports -->
                <div class="card no-print">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Laporan Tersimpan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="savedReportsTable">
                                <thead>
                                    <tr>
                                        <th>Judul Laporan</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Tanggal Akhir</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Jumlah Transaksi</th>
                                        <th>Total Pendapatan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($saved_reports as $report): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report['judul']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($report['tanggal_mulai'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($report['tanggal_akhir'])); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($report['tanggal_dibuat'])); ?></td>
                                        <td><?php echo $report['total_transaksi']; ?></td>
                                        <td>Rp <?php echo number_format($report['total_pendapatan'], 0, ',', '.'); ?></td>
                                        <td>
                                            <a href="?view_report=<?php echo $report['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Lihat
                                            </a>
                                            <a href="?delete_report=<?php echo $report['id']; ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');">
                                                <i class="bi bi-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Report Result -->
                <?php if ($laporan): ?>
                <div class="card mt-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Hasil Laporan</h5>
                        <button onclick="window.print()" class="btn btn-light btn-sm no-print">
                            <i class="bi bi-printer"></i> Cetak Laporan
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="report-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4><?php echo htmlspecialchars($title); ?></h4>
                                    <p>Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p>Tanggal Cetak: <?php echo date('d/m/Y H:i:s'); ?></p>
                                    <p>Total Transaksi: <?php echo count($laporan); ?></p>
                                    <p>Total Pendapatan: Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="reportTable">
                                <thead>
                                    <tr>
                                        <th>Kode Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Pembeli</th>
                                        <th>Metode Pembayaran</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th class="no-print">Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($laporan as $trans): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trans['kode_transaksi']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($trans['tanggal'])); ?></td>
                                        <td><?php echo htmlspecialchars($trans['atas_nama']); ?></td>
                                        <td><?php echo htmlspecialchars($trans['metode_pembayaran'] ?? 'Tunai'); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($trans['status_pembayaran'] ?? 'Completed') === 'Completed' ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo htmlspecialchars($trans['status_pembayaran'] ?? 'Completed'); ?>
                                            </span>
                                        </td>
                                        <td>Rp <?php echo number_format($trans['total_bayar'] ?? array_sum(array_column($trans['items'], 'subtotal')), 0, ',', '.'); ?></td>
                                        <td class="no-print">
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" 
                                                    data-bs-target="#details-<?php echo $trans['kode_transaksi']; ?>">
                                                <i class="bi bi-list-ul"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="no-print">
                                        <td colspan="7" class="p-0">
                                            <div class="collapse" id="details-<?php echo $trans['kode_transaksi']; ?>">
                                                <div class="card card-body m-2">
                                                    <h6>Detail Item</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Item</th>
                                                                    <th>Quantity</th>
                                                                    <th>Harga</th>
                                                                    <th>Subtotal</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($trans['items'] as $item): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                                                    <td><?php echo $item['quantity']; ?></td>
                                                                    <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                                                    <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#reportTable').DataTable({
                "order": [[ 1, "desc" ]],
                "pageLength": 25,
                "language": {
                    "search": "Cari:",
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Tidak ada data yang ditemukan",
                    "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    "infoEmpty": "Tidak ada data yang tersedia",
                    "infoFiltered": "(difilter dari _MAX_ total data)",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });
            
            $('#savedReportsTable').DataTable({
                "order": [[ 3, "desc" ]],
                "pageLength": 10,
                "language": {
                    "search": "Cari:",
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Tidak ada laporan tersimpan",
                    "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    "infoEmpty": "Tidak ada laporan tersimpan",
                    "infoFiltered": "(difilter dari _MAX_ total laporan)",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });
            
            // Set default date range (last 30 days)
            if (!$('#start_date').val()) {
                let thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                $('#start_date').val(thirtyDaysAgo.toISOString().split('T')[0]);
            }
            
            if (!$('#end_date').val()) {
                let today = new Date();
                $('#end_date').val(today.toISOString().split('T')[0]);
            }
        });
    </script>
</body>
</html> 