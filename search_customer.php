<?php
// File ini menangani pencarian customer untuk fitur autocomplete
header('Content-Type: application/json');

// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    // Ambil parameter pencarian
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($search) || strlen($search) < 2) {
        // Jika pencarian kosong atau terlalu pendek, kembalikan array kosong
        echo json_encode([]);
        exit;
    }
    
    // Buat koneksi
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cari customer berdasarkan nama atau email
    $stmt = $conn->prepare("
        SELECT id, kode_customer, nama, email, telepon, alamat 
        FROM customer 
        WHERE nama LIKE :search 
        OR email LIKE :search 
        OR telepon LIKE :search
        LIMIT 10
    ");
    
    $searchParam = "%{$search}%";
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kembalikan hasil dalam format JSON
    echo json_encode($results);
    
} catch(PDOException $e) {
    // Tangani error tetapi tetap kembalikan format JSON
    error_log("Error pada search_customer.php: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => 'Terjadi kesalahan saat mencari customer'
    ]);
}
?>
