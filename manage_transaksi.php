<?php
// Only start session if one doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi untuk menambahkan transaksi
function addTransaction($kode_transaksi, $id_barang, $nama_barang, $quantity, $harga, $atas_nama = null, $kode_customer = 'CUSTOMER') {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $subtotal = $quantity * $harga;
        
        // Validasi atas_nama agar tidak null
        if (empty($atas_nama)) {
            $atas_nama = "Pelanggan";
        }
        
        error_log("Adding transaction: $kode_transaksi, Item: $nama_barang, Customer: $atas_nama, Code: $kode_customer");
        
        $stmt = $conn->prepare("INSERT INTO transaksi (kode_transaksi, kode_customer, atas_nama, id_barang, nama_barang, quantity, harga, subtotal) 
                               VALUES (:kode_transaksi, :kode_customer, :atas_nama, :id_barang, :nama_barang, :quantity, :harga, :subtotal)");
        
        $stmt->execute([
            ':kode_transaksi' => $kode_transaksi,
            ':kode_customer' => $kode_customer,
            ':atas_nama' => $atas_nama,
            ':id_barang' => $id_barang,
            ':nama_barang' => $nama_barang,
            ':quantity' => $quantity,
            ':harga' => $harga,
            ':subtotal' => $subtotal
        ]);
        
        // Verifikasi transaksi telah tersimpan
        $verify = $conn->prepare("SELECT COUNT(*) FROM transaksi WHERE kode_transaksi = :kode AND id_barang = :id");
        $verify->execute([
            ':kode' => $kode_transaksi,
            ':id' => $id_barang
        ]);
        $count = $verify->fetchColumn();
        
        if ($count == 0) {
            error_log("WARNING: Transaction item may not have been saved! $kode_transaksi, $nama_barang");
        } else {
            error_log("Transaction item saved successfully: $kode_transaksi, $nama_barang");
        }
        
        return true;
    } catch(PDOException $e) {
        error_log("Error in addTransaction: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk mendapatkan semua transaksi
function getAllTransactions() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cek apakah tabel diskon ada
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = :dbname 
            AND table_name = 'diskon'
        ");
        $stmt->execute([':dbname' => $dbname]);
        $diskonTableExists = (bool)$stmt->fetchColumn();
        
        // Jika tabel diskon ada, gunakan query JOIN lengkap
        if ($diskonTableExists) {
            $stmt = $conn->prepare("
                SELECT t.*, p.kode_diskon, p.diskon_amount, d.nama_diskon, d.persentase_diskon, t.atas_nama, p.atas_nama as pembeli,
                p.total_bayar, p.metode_pembayaran
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                LEFT JOIN diskon d ON p.kode_diskon = d.kode_diskon
                ORDER BY t.created_at DESC
            ");
        } else {
            // Jika tabel diskon tidak ada, gunakan query tanpa JOIN ke tabel diskon
            $stmt = $conn->prepare("
                SELECT t.*, p.kode_diskon, p.diskon_amount, NULL as nama_diskon, 0 as persentase_diskon, t.atas_nama, p.atas_nama as pembeli,
                p.total_bayar, p.metode_pembayaran
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                ORDER BY t.created_at DESC
            ");
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Log error for debugging
        error_log("Error in getAllTransactions: " . $e->getMessage());
        return [];
    }
}

// Fungsi untuk mendapatkan detail transaksi berdasarkan kode transaksi
function getTransactionsByCode($kode_transaksi) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Log untuk debugging
        error_log("Fetching transactions for code: " . $kode_transaksi);
        
        // Cek apakah tabel diskon ada
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = :dbname 
            AND table_name = 'diskon'
        ");
        $stmt->execute([':dbname' => $dbname]);
        $diskonTableExists = (bool)$stmt->fetchColumn();
        
        // Jika tabel diskon ada, gunakan query JOIN lengkap
        if ($diskonTableExists) {
            $stmt = $conn->prepare("
                SELECT t.*, p.kode_diskon, p.diskon_amount, d.nama_diskon, d.persentase_diskon, t.atas_nama, p.atas_nama as pembeli,
                p.total_bayar, p.metode_pembayaran
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                LEFT JOIN diskon d ON p.kode_diskon = d.kode_diskon
                WHERE t.kode_transaksi = :kode_transaksi
                ORDER BY t.created_at DESC
            ");
        } else {
            // Jika tabel diskon tidak ada, gunakan query tanpa JOIN ke tabel diskon
            $stmt = $conn->prepare("
                SELECT t.*, p.kode_diskon, p.diskon_amount, NULL as nama_diskon, 0 as persentase_diskon, t.atas_nama, p.atas_nama as pembeli,
                p.total_bayar, p.metode_pembayaran
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                WHERE t.kode_transaksi = :kode_transaksi
                ORDER BY t.created_at DESC
            ");
        }
        
        $stmt->execute([':kode_transaksi' => $kode_transaksi]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($results) . " transactions for code: " . $kode_transaksi);
        
        // Jika tidak kosong, coba dapatkan data pembayaran juga
        if (!empty($results)) {
            // Coba ambil data pembayaran
            $payment_stmt = $conn->prepare("SELECT * FROM pembayaran WHERE kode_transaksi = :kode_transaksi");
            $payment_stmt->execute([':kode_transaksi' => $kode_transaksi]);
            $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Jika ada data pembayaran, tambahkan ke setiap item transaksi
            if ($payment) {
                error_log("Found payment data for: " . $kode_transaksi);
                foreach ($results as &$result) {
                    $result['kode_diskon'] = $payment['kode_diskon'] ?? null;
                    $result['diskon_amount'] = $payment['diskon_amount'] ?? 0;
                    $result['pembeli'] = $payment['atas_nama'] ?? null;
                    
                    // Jika transaksi tidak punya nama pembeli, gunakan dari pembayaran
                    if (empty($result['atas_nama'])) {
                        $result['atas_nama'] = $payment['atas_nama'];
                    }
                }
            } else {
                error_log("No payment data found for: " . $kode_transaksi);
            }
        }
        
        return $results;
    } catch(PDOException $e) {
        // Log error for debugging
        error_log("Error in getTransactionsByCode: " . $e->getMessage());
        return [];
    }
}

// Fungsi untuk mendapatkan total nilai transaksi per kode transaksi
function getTransactionTotal($kode_transaksi) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $conn->prepare("SELECT SUM(subtotal) as total FROM transaksi WHERE kode_transaksi = :kode_transaksi");
        $stmt->execute([':kode_transaksi' => $kode_transaksi]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch(PDOException $e) {
        error_log("Error in getTransactionTotal: " . $e->getMessage());
        return 0;
    }
}

// Fungsi untuk mendapatkan detail diskon berdasarkan kode diskon
function getDiscountByCode($kode_diskon) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $conn->prepare("SELECT * FROM diskon WHERE kode_diskon = :kode_diskon");
        $stmt->execute([':kode_diskon' => $kode_diskon]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Log error for debugging
        error_log("Error in getDiscountByCode: " . $e->getMessage());
        return null;
    }
}

// Fungsi untuk mendapatkan kode transaksi berikutnya (KT1, KT2, KT3, dst)
function getNextTransactionCode() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cek apakah tabel next_transaction_code ada dan memiliki data
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = :dbname 
            AND table_name = 'next_transaction_code'
        ");
        $stmt->execute([':dbname' => $dbname]);
        $tableExists = (bool)$stmt->fetchColumn();
        
        if ($tableExists) {
            // Cek apakah ada nilai kode transaksi berikutnya
            $stmt = $conn->prepare("SELECT next_code FROM next_transaction_code LIMIT 1");
            $stmt->execute();
            $nextCodeResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($nextCodeResult) {
                // Gunakan nilai dari tabel next_transaction_code
                $nextNumber = $nextCodeResult['next_code'];
                
                // Update nilai next_code untuk transaksi berikutnya
                $stmt = $conn->prepare("UPDATE next_transaction_code SET next_code = next_code + 1");
                $stmt->execute();
                
                return 'KT' . $nextNumber;
            }
        }
        
        // Jika tabel next_transaction_code tidak ada atau tidak memiliki data,
        // gunakan cara lama untuk mendapatkan kode transaksi berikutnya
        $stmt = $conn->prepare("
            SELECT kode_transaksi 
            FROM pembayaran 
            WHERE kode_transaksi LIKE 'KT%' 
            ORDER BY CAST(SUBSTRING(kode_transaksi, 3) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Jika ada kode transaksi sebelumnya, ambil nomor dan tambahkan 1
            $lastCode = $result['kode_transaksi'];
            $lastNumber = (int)substr($lastCode, 2); // Ambil angka setelah 'KT'
            $nextNumber = $lastNumber + 1;
        } else {
            // Jika belum ada transaksi, mulai dari 30
            $nextNumber = 30;
        }
        
        return 'KT' . $nextNumber;
    } catch(PDOException $e) {
        // Log error untuk debugging
        error_log("Error in getNextTransactionCode: " . $e->getMessage());
        // Jika terjadi error, gunakan timestamp sebagai fallback
        return 'KT' . time();
    }
}

// Fungsi untuk mencari transaksi berdasarkan kode transaksi atau nama produk
function searchTransactions($search_term) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cek apakah tabel diskon ada
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = :dbname 
            AND table_name = 'diskon'
        ");
        $stmt->execute([':dbname' => $dbname]);
        $diskonTableExists = (bool)$stmt->fetchColumn();
        
        // Prepare search term
        $search_param = '%' . $search_term . '%';
        
        // Jika tabel diskon ada, gunakan query JOIN lengkap
        if ($diskonTableExists) {
            $stmt = $conn->prepare("
                SELECT t.*, p.kode_diskon, p.diskon_amount, d.nama_diskon, d.persentase_diskon, t.atas_nama, p.atas_nama as pembeli,
                p.total_bayar, p.metode_pembayaran
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                LEFT JOIN diskon d ON p.kode_diskon = d.kode_diskon
                WHERE t.kode_transaksi LIKE :search_term 
                OR t.nama_barang LIKE :search_term
                OR t.atas_nama LIKE :search_term
                OR p.atas_nama LIKE :search_term
                ORDER BY t.created_at DESC
            ");
        } else {
            // Jika tabel diskon tidak ada, gunakan query tanpa JOIN ke tabel diskon
            $stmt = $conn->prepare("
                SELECT t.*, p.kode_diskon, p.diskon_amount, NULL as nama_diskon, 0 as persentase_diskon, t.atas_nama, p.atas_nama as pembeli,
                p.total_bayar, p.metode_pembayaran
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                WHERE t.kode_transaksi LIKE :search_term 
                OR t.nama_barang LIKE :search_term
                OR t.atas_nama LIKE :search_term
                OR p.atas_nama LIKE :search_term
                ORDER BY t.created_at DESC
            ");
        }
        
        $stmt->execute([':search_term' => $search_param]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Log error for debugging
        error_log("Error in searchTransactions: " . $e->getMessage());
        return [];
    }
}

// Handle API requests jika file ini dipanggil langsung
if (basename($_SERVER['PHP_SELF']) === 'manage_transaksi.php') {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'getAll':
            echo json_encode(['status' => 'success', 'data' => getAllTransactions()]);
            break;
            
        case 'getByCode':
            $kode_transaksi = $_GET['kode_transaksi'] ?? '';
            if (!$kode_transaksi) {
                echo json_encode(['status' => 'error', 'message' => 'Kode transaksi diperlukan']);
                break;
            }
            echo json_encode(['status' => 'success', 'data' => getTransactionsByCode($kode_transaksi)]);
            break;
            
        case 'getTotal':
            $kode_transaksi = $_GET['kode_transaksi'] ?? '';
            if (!$kode_transaksi) {
                echo json_encode(['status' => 'error', 'message' => 'Kode transaksi diperlukan']);
                break;
            }
            echo json_encode(['status' => 'success', 'total' => getTransactionTotal($kode_transaksi)]);
            break;
            
        case 'search':
            $search_term = $_GET['search_term'] ?? '';
            if (!$search_term) {
                echo json_encode(['status' => 'error', 'message' => 'Term pencarian diperlukan']);
                break;
            }
            echo json_encode(['status' => 'success', 'data' => searchTransactions($search_term)]);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
    }
    
    exit;
}
?>
