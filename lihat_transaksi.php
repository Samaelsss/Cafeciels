<?php
session_start();
require_once 'manage_transaksi.php';

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$transactions = [];

// Get transactions based on search criteria
if (!empty($search_term)) {
    // Search by product name or transaction code
    $transactions = searchTransactions($search_term);
} else {
    // Get all transactions if no search term
    $transactions = getAllTransactions();
}

// Function to highlight search term in text
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

// Filter by kode_transaksi if provided in the URL
if (isset($_GET['kode']) && !empty($_GET['kode'])) {
    $kode_transaksi = $_GET['kode'];
    $transactions = getTransactionsByCode($kode_transaksi);
    
    // Debug: Log transaction data untuk troubleshooting
    if (empty($transactions)) {
        error_log("No transactions found for code: " . $kode_transaksi);
    } else {
        error_log("Found " . count($transactions) . " transactions for code: " . $kode_transaksi);
    }
}

// Group transactions by kode_transaksi
$grouped_transactions = [];
foreach ($transactions as $transaction) {
    $code = $transaction['kode_transaksi'];
    
    // Debug individual transaction
    error_log("Processing transaction: " . $code . " | Atas nama: " . $transaction['atas_nama'] . " | Pembeli: " . ($transaction['pembeli'] ?? 'NULL'));
    
    if (!isset($grouped_transactions[$code])) {
        // Prioritaskan nama pembeli dari tabel pembayaran jika tersedia
        $nama_pembeli = !empty($transaction['pembeli']) ? $transaction['pembeli'] : $transaction['atas_nama'];
        
        $grouped_transactions[$code] = [
            'kode_transaksi' => $code,
            'created_at' => $transaction['created_at'],
            'atas_nama' => $nama_pembeli,
            'items' => [],
            'total' => 0,
            'kode_diskon' => $transaction['kode_diskon'] ?? null,
            'diskon_amount' => $transaction['diskon_amount'] ?? 0,
            'nama_diskon' => $transaction['nama_diskon'] ?? null,
            'persentase_diskon' => $transaction['persentase_diskon'] ?? 0,
            'total_bayar' => $transaction['total_bayar'] ?? 0,
            'metode_pembayaran' => $transaction['metode_pembayaran'] ?? 'Tunai'
        ];
        
        // Debug transaksi baru yang di-group
        error_log("Added new transaction group: " . $code . " with customer: " . $nama_pembeli);
    }
    
    $grouped_transactions[$code]['items'][] = $transaction;
    $grouped_transactions[$code]['total'] += $transaction['subtotal'];
}

// Debug: Log grouped transactions untuk troubleshooting
error_log("Grouped transactions count: " . count($grouped_transactions));
if (count($grouped_transactions) == 0 && isset($_GET['kode'])) {
    error_log("WARNING: No transactions found for kode: " . $_GET['kode']);
    
    // Coba dapatkan data mentah untuk debugging
    $raw_data = [];
    try {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "cafeciels";
        
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cek tabel transaksi langsung
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transaksi WHERE kode_transaksi = :kode");
        $stmt->execute([':kode' => $_GET['kode']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        error_log("Direct DB check: Found " . $count . " records in transaksi table for kode: " . $_GET['kode']);
        
        // Cek tabel pembayaran langsung
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pembayaran WHERE kode_transaksi = :kode");
        $stmt->execute([':kode' => $_GET['kode']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        error_log("Direct DB check: Found " . $count . " records in pembayaran table for kode: " . $_GET['kode']);
    } catch(PDOException $e) {
        error_log("Error in direct DB check: " . $e->getMessage());
    }
}

// Sort by most recent first
usort($grouped_transactions, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Cafe Ciels</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .search-form {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }

        .search-input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px 0 0 8px;
            width: 70%;
            font-size: 16px;
        }

        .search-button {
            padding: 10px 15px;
            border: none;
            border-radius: 0 8px 8px 0;
            background-color: #3498db;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }

        .transactions-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .transaction-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .transaction-header {
            background-color: #3498db;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .transaction-header-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .transaction-header-info small {
            font-size: 14px;
            color: #ecf0f1;
        }

        .transaction-body {
            padding: 20px;
        }

        .transaction-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .transaction-items th, 
        .transaction-items td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .transaction-items th {
            background-color: #f9f9f9;
            font-weight: 500;
        }

        .transaction-total {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            font-weight: 500;
            font-size: 18px;
            color: #2c3e50;
        }
        
        .subtotal-row {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .discount-row {
            font-size: 16px;
            color: #e74c3c;
            margin-bottom: 8px;
        }
        
        .final-total-row {
            font-size: 18px;
            font-weight: 600;
            color: #27ae60;
        }

        .accordion {
            cursor: pointer;
            padding: 15px 20px;
            width: 100%;
            text-align: left;
            border: none;
            outline: none;
            transition: 0.4s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #3498db;
            color: white;
            font-weight: 500;
            font-size: 16px;
            border-radius: 10px 10px 0 0;
        }

        .active, .accordion:hover {
            background-color: #2980b9;
        }

        .panel {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background-color: white;
            border-radius: 0 0 10px 10px;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        .search-results {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .highlight {
            background-color: #ffff00;
        }

        .transaction-summary-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .payment-info {
            margin-top: 5px;
            font-weight: 500;
        }

        .payment-info .label {
            color: #2c3e50;
        }
        
        .summary-row:last-child {
            margin-bottom: 0;
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
        }

        @media print {
            .no-print {
                display: none;
            }
            body {
                background-color: white;
                padding: 0;
                font-size: 12px;
            }
            .container {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 10px;
            }
            .transaction-card {
                page-break-inside: avoid;
                box-shadow: none;
                margin-bottom: 20px;
                border: 1px solid #ddd;
            }
            .page-title {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title">
                <h1>
                    <?php if (isset($_GET['kode'])): ?>
                        Detail Transaksi #<?php echo htmlspecialchars($_GET['kode']); ?>
                    <?php else: ?>
                        Riwayat Transaksi
                    <?php endif; ?>
                </h1>
            </div>
            <div class="header-actions">
                <a href="user.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Menu
                </a>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
               
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!isset($_GET['kode'])): ?>
        <div class="search-container no-print">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Cari transaksi, produk, atau atas nama..." class="search-input"
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                    Cari
                </button>
            </form>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <p class="search-results-info">
                    Hasil pencarian untuk: <strong><?php echo htmlspecialchars($_GET['search']); ?></strong>
                    <a href="lihat_transaksi.php" class="reset-search">Reset</a>
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="transactions-container">
            <?php if (empty($grouped_transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <?php if (!empty($search_term)): ?>
                        <h3>Tidak ada hasil untuk "<?php echo htmlspecialchars($search_term); ?>"</h3>
                        <p>Coba dengan kata kunci yang berbeda</p>
                    <?php else: ?>
                        <h3>Belum ada transaksi</h3>
                        <p>Transaksi yang sudah selesai akan ditampilkan di sini</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if (!empty($search_term)): ?>
                    <div class="search-results">
                        <p>Menampilkan hasil pencarian untuk: <strong><?php echo htmlspecialchars($search_term); ?></strong> (<?php echo count($grouped_transactions); ?> transaksi ditemukan)</p>
                    </div>
                <?php endif; ?>
                
                <?php foreach ($grouped_transactions as $transaction): ?>
                    <div class="transaction-card">
                        <button class="accordion">
                            <div class="transaction-header-info">
                                <span>
                                    <i class="fas fa-receipt"></i>
                                    <?php echo highlightText($transaction['kode_transaksi'], $search_term); ?>
                                </span>
                                <?php if (!empty($transaction['atas_nama'])): ?>
                                <small class="transaction-customer">
                                    <i class="fas fa-user"></i>
                                    <?php echo highlightText($transaction['atas_nama'], $search_term); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <span>
                                <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?>
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </button>
                        <div class="panel">
                            <div class="transaction-body">
                                <table class="transaction-items">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Jumlah</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transaction['items'] as $item): ?>
                                            <tr>
                                                <td class="product-name"><?php echo highlightText($item['nama_barang'], $search_term); ?></td>
                                                <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php 
                                    // Menghitung total setelah diskon
                                    $total_after_discount = $transaction['total'];
                                    if (!empty($transaction['diskon_amount']) && $transaction['diskon_amount'] > 0) {
                                        $total_after_discount = $transaction['total'] - $transaction['diskon_amount'];
                                    }
                                    
                                    // Menghitung kembalian
                                    $kembalian = 0;
                                    if (!empty($transaction['total_bayar']) && $transaction['total_bayar'] > 0) {
                                        $kembalian = $transaction['total_bayar'] - $total_after_discount;
                                    }
                                ?>
                                <div class="transaction-summary-box">
                                    <div class="summary-row">
                                        <span class="label">Total</span>
                                        <span class="value">Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?></span>
                                    </div>
                                    
                                    <?php if (!empty($transaction['diskon_amount']) && $transaction['diskon_amount'] > 0): ?>
                                    <div class="summary-row">
                                        <span class="label">Diskon</span>
                                        <span class="value">- Rp <?php echo number_format($transaction['diskon_amount'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="label">Total Setelah Diskon</span>
                                        <span class="value">Rp <?php echo number_format($total_after_discount, 0, ',', '.'); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($transaction['total_bayar']) && $transaction['total_bayar'] > 0): ?>
                                    <div class="summary-row payment-info">
                                        <span class="label">Jumlah Bayar</span>
                                        <span class="value">Rp <?php echo number_format($transaction['total_bayar'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="summary-row payment-info">
                                        <span class="label">Metode Pembayaran</span>
                                        <span class="value"><?php echo htmlspecialchars($transaction['metode_pembayaran']); ?></span>
                                    </div>
                                    <div class="summary-row payment-info">
                                        <span class="label">Kembalian</span>
                                        <span class="value">Rp <?php echo number_format($kembalian, 0, ',', '.'); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="transaction-actions no-print" style="margin-top: 20px; text-align: right;">
                                    <button onclick="printTransaction('<?php echo $transaction['kode_transaksi']; ?>')" class="back-button" style="margin:0; background-color: #27ae60;">
                                        <i class="fas fa-print"></i> Cetak
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle accordion
        document.addEventListener('DOMContentLoaded', function() {
            const accordions = document.querySelectorAll('.accordion');
            
            accordions.forEach(acc => {
                acc.addEventListener('click', function() {
                    this.classList.toggle('active');
                    const panel = this.nextElementSibling;
                    if (panel.style.maxHeight) {
                        panel.style.maxHeight = null;
                    } else {
                        panel.style.maxHeight = panel.scrollHeight + "px";
                    }
                });
            });
            
            // Auto-expand and print if print parameter is set
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'true') {
                // If kode parameter is set, only expand that transaction
                const kodeParam = urlParams.get('kode');
                if (kodeParam) {
                    const selectedAccordion = Array.from(document.querySelectorAll('.accordion')).find(
                        acc => acc.textContent.includes(kodeParam)
                    );
                    
                    if (selectedAccordion) {
                        selectedAccordion.classList.add('active');
                        const panel = selectedAccordion.nextElementSibling;
                        panel.style.maxHeight = panel.scrollHeight + "px";
                    }
                } else {
                    // Expand all transactions
                    accordions.forEach(acc => {
                        acc.classList.add('active');
                        const panel = acc.nextElementSibling;
                        panel.style.maxHeight = panel.scrollHeight + "px";
                    });
                }
                
                // Print after a short delay to ensure panels are expanded
                setTimeout(() => {
                    window.print();
                }, 500);
            }
            
            // Print specific transaction
            function printTransaction(transactionCode) {
                // Hide all panels
                const panels = document.querySelectorAll('.panel');
                panels.forEach(panel => {
                    panel.style.maxHeight = null;
                });
                
                // Show only the selected panel
                const selectedAccordion = Array.from(document.querySelectorAll('.accordion')).find(
                    acc => acc.textContent.includes(transactionCode)
                );
                
                if (selectedAccordion) {
                    selectedAccordion.classList.add('active');
                    const panel = selectedAccordion.nextElementSibling;
                    panel.style.maxHeight = panel.scrollHeight + "px";
                    
                    // Print
                    window.print();
                }
            }
        });
    </script>
</body>
</html>
