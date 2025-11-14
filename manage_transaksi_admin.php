<?php
session_start();
require_once 'manage_transaksi.php';

// Fungsi untuk highlight hasil pencarian
function highlightText($text, $search_term) {
    if (empty($search_term)) {
        return htmlspecialchars($text);
    }
    
    // Case-insensitive replacement
    $pattern = '/' . preg_quote($search_term, '/') . '/i';
    $replacement = '<span class="highlight">$0</span>';
    
    // Apply highlighting to the text after escaping HTML
    return preg_replace($pattern, $replacement, htmlspecialchars($text));
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get search term
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get transactions based on search
$transactions = [];
if (!empty($search_term)) {
    $transactions = searchTransactions($search_term);
} else {
    $transactions = getAllTransactions();
}

// Filter by kode_transaksi if provided in the URL
if (isset($_GET['kode']) && !empty($_GET['kode'])) {
    $kode_transaksi = $_GET['kode'];
    $transactions = getTransactionsByCode($kode_transaksi);
}

// Group transactions by kode_transaksi
$grouped_transactions = [];
foreach ($transactions as $transaction) {
    $code = $transaction['kode_transaksi'];
    if (!isset($grouped_transactions[$code])) {
        $grouped_transactions[$code] = [
            'kode_transaksi' => $code,
            'created_at' => $transaction['created_at'],
            'items' => [],
            'total' => 0,
            'kode_diskon' => $transaction['kode_diskon'] ?? null,
            'diskon_amount' => $transaction['diskon_amount'] ?? 0,
            'nama_diskon' => $transaction['nama_diskon'] ?? null,
            'persentase_diskon' => $transaction['persentase_diskon'] ?? 0
        ];
    }
    
    $grouped_transactions[$code]['items'][] = $transaction;
    $grouped_transactions[$code]['total'] += $transaction['subtotal'];
}

// Sort by most recent first
usort($grouped_transactions, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Pagination for grouped transactions
$total_groups = count($grouped_transactions);
$total_pages = ceil($total_groups / $records_per_page);
$paginated_groups = array_slice($grouped_transactions, $offset, $records_per_page);

// Get unique transaction codes for filtering
$transaction_codes = array_keys($grouped_transactions);

// Connect to database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Transaksi - Cafe Ciels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            margin-bottom: 0;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .search-container {
            width: 100%;
            max-width: 500px;
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .search-button {
            padding: 12px 25px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background-color: white;
            min-width: 200px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #3498db;
        }

        .table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f9f9f9;
            font-weight: 500;
            color: #2c3e50;
        }

        tr:hover {
            background-color: #f5f9ff;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #ffeaa7;
            color: #d35400;
        }

        .status-completed {
            background-color: #d5f5e3;
            color: #27ae60;
        }

        .status-cancelled {
            background-color: #fadbd8;
            color: #c0392b;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-view {
            background-color: #3498db;
            color: white;
        }

        .btn-view:hover {
            background-color: #2980b9;
        }

        .btn-print {
            background-color: #27ae60;
            color: white;
        }

        .btn-print:hover {
            background-color: #219653;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 8px;
            background-color: white;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .pagination a:hover {
            background-color: #3498db;
            color: white;
            transform: translateY(-2px);
        }

        .pagination .active {
            background-color: #3498db;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state i {
            font-size: 50px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-container {
                width: 100%;
                max-width: none;
            }
            
            th, td {
                padding: 10px 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                background-color: white;
            }
            
            .container {
                width: 100%;
                max-width: none;
                padding: 0;
            }
            
            .table-container {
                box-shadow: none;
                margin-bottom: 0;
            }
            
            table {
                border: 1px solid #ddd;
            }
            
            th, td {
                border: 1px solid #ddd;
            }
        }
        
        /* Highlighted text */
        .highlight {
            background-color: #ffff00;
        }
        
        .transaction-group {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            padding: 20px;
        }
        
        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .transaction-date {
            color: #777;
            font-size: 14px;
        }
        
        .transaction-customer {
            color: #555;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .transaction-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header no-print">
            <h1>Manajemen Transaksi</h1>
            <div>
                <a href="admin_home.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Dashboard
                </a>
                <a href="manage_barang.php" class="back-button" style="background-color: #27ae60;">
                    <i class="fas fa-box"></i>
                    Kelola Barang
                </a>
            </div>
        </div>

        <div class="search-container no-print">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Cari transaksi, produk, atau atas nama..." class="search-input" 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                    Cari
                </button>
            </form>
        </div>

        <div class="filter-container no-print">
            <select id="kodeTransaksiFilter" class="filter-select" onchange="filterByCode(this.value)">
                <option value="">Semua Kode Transaksi</option>
                <?php foreach ($transaction_codes as $code): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>"
                            <?php echo (isset($_GET['kode']) && $_GET['kode'] === $code) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($code); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($paginated_groups)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>Tidak ada transaksi ditemukan</h3>
                    <p>Coba ubah filter pencarian atau tambahkan transaksi baru</p>
                </div>
            <?php else: ?>
                <?php foreach ($paginated_groups as $group): ?>
                    <div class="transaction-group">
                        <div class="transaction-header">
                            <div>
                                <h2>Transaksi #<?php echo highlightText($group['kode_transaksi'], $search_term); ?></h2>
                                <?php if (!empty($group['items'][0]['atas_nama'])): ?>
                                <p class="transaction-customer">Atas Nama: <?php echo highlightText($group['items'][0]['atas_nama'], $search_term); ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="transaction-date"><?php echo htmlspecialchars(date('d M Y', strtotime($group['created_at']))); ?></span>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Jumlah</th>
                                    <th>Harga</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group['items'] as $item): ?>
                                    <tr>
                                        <td><?php echo highlightText($item['nama_barang'], $search_term); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                        <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="3">Total</td>
                                    <td>Rp <?php echo number_format($group['total'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php if (!empty($group['kode_diskon'])): ?>
                                    <tr>
                                        <td colspan="3">Diskon</td>
                                        <td>
                                            <?php echo htmlspecialchars($group['nama_diskon'] ?? $group['kode_diskon']); ?> 
                                            (<?php echo $group['persentase_diskon']; ?>%)
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3">Diskon Amount</td>
                                        <td>Rp <?php echo number_format($group['diskon_amount'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="transaction-actions">
                            <a href="lihat_transaksi.php?kode=<?php echo urlencode($group['kode_transaksi']); ?>" class="btn btn-view">
                                <i class="fas fa-eye"></i> Lihat
                            </a>
                            <button onclick="printTransaction('<?php echo $group['kode_transaksi']; ?>')" class="btn btn-print">
                                <i class="fas fa-print"></i> Cetak
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination no-print">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<a href="?page=1' . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '">1</a>';
                    if ($start_page > 2) {
                        echo '<span>...</span>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active_class = $i == $page ? 'active' : '';
                    echo '<a href="?page=' . $i . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '" class="' . $active_class . '">' . $i . '</a>';
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span>...</span>';
                    }
                    echo '<a href="?page=' . $total_pages . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterByCode(code) {
            if (code) {
                window.location.href = 'manage_transaksi_admin.php?kode=' + encodeURIComponent(code);
            } else {
                window.location.href = 'manage_transaksi_admin.php';
            }
        }

        function printTransaction(kode_transaksi) {
            // Redirect to transaction view with print parameter
            window.open('lihat_transaksi.php?kode=' + encodeURIComponent(kode_transaksi) + '&print=true', '_blank');
        }
    </script>
</body>
</html>
<?php $conn = null; ?>
