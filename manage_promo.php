<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

function autokode($conn) {
    try {
        // Query untuk mendapatkan kode terbesar
        $stmt = $conn->query("SELECT kode_promo FROM promo WHERE kode_promo LIKE 'P%' ORDER BY kode_promo DESC LIMIT 1");
        $row = $stmt->fetch();
        
        if ($row) {
            // Jika ada data, ambil nomor dari kode terakhir
            $last_code = $row['kode_promo'];
            $number = intval(substr($last_code, 1));
            $next_number = $number + 1;
        } else {
            // Jika tidak ada data, mulai dari 1
            $next_number = 1;
        }
        
        // Format kode baru dengan padding 3 digit
        $kode_promo = 'P' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
        
        // Periksa apakah kode baru sudah ada
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM promo WHERE kode_promo = ?");
        $check_stmt->execute([$kode_promo]);
        $exists = $check_stmt->fetchColumn();
        
        if ($exists) {
            // Jika kode sudah ada, cari nomor berikutnya yang tersedia
            do {
                $next_number++;
                $kode_promo = 'P' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
                $check_stmt->execute([$kode_promo]);
                $exists = $check_stmt->fetchColumn();
            } while ($exists);
        }
        
        return $kode_promo;
    } catch(PDOException $e) {
        return 'P001';
    }
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create promo table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS promo (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kode_promo VARCHAR(50) NOT NULL,
        nama_promo VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        jenis_diskon ENUM('Persentase', 'Nominal') NOT NULL,
        nilai_diskon DECIMAL(10,2) NOT NULL,
        min_pembelian DECIMAL(10,2) DEFAULT 0,
        tanggal_mulai DATE NOT NULL,
        tanggal_selesai DATE NOT NULL,
        status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // HANDLE ADD NEW PROMO
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_id'])) {
        // Validasi field wajib
        $required = ['nama_promo', 'jenis_diskon', 'nilai_diskon', 'tanggal_mulai', 'tanggal_selesai', 'status'];
        foreach($required as $field) {
            if(empty($_POST[$field])) {
                throw new Exception("Field $field harus diisi!");
            }
        }

        // Validasi tanggal
        $tanggal_mulai = new DateTime($_POST['tanggal_mulai']);
        $tanggal_selesai = new DateTime($_POST['tanggal_selesai']);
        if ($tanggal_mulai > $tanggal_selesai) {
            throw new Exception("Tanggal selesai harus lebih besar dari tanggal mulai!");
        }

        $kode_promo = autokode($conn);
        $nama_promo = $_POST['nama_promo'];
        $deskripsi = $_POST['deskripsi'] ?? '';
        $jenis_diskon = $_POST['jenis_diskon'];
        $nilai_diskon = $_POST['nilai_diskon'];
        $min_pembelian = $_POST['min_pembelian'] ?? 0;
        $status = $_POST['status'];

        // Validasi nilai diskon
        if ($jenis_diskon == 'Persentase' && $nilai_diskon > 100) {
            throw new Exception("Nilai diskon persentase tidak boleh lebih dari 100%!");
        }

        // Insert data
        $sql = "INSERT INTO promo (kode_promo, nama_promo, deskripsi, jenis_diskon, nilai_diskon, min_pembelian, tanggal_mulai, tanggal_selesai, status) 
                VALUES (:kode_promo, :nama_promo, :deskripsi, :jenis_diskon, :nilai_diskon, :min_pembelian, :tanggal_mulai, :tanggal_selesai, :status)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':kode_promo' => $kode_promo,
            ':nama_promo' => $nama_promo,
            ':deskripsi' => $deskripsi,
            ':jenis_diskon' => $jenis_diskon,
            ':nilai_diskon' => $nilai_diskon,
            ':min_pembelian' => $min_pembelian,
            ':tanggal_mulai' => $_POST['tanggal_mulai'],
            ':tanggal_selesai' => $_POST['tanggal_selesai'],
            ':status' => $status
        ]);
        $success_message = "Data promo berhasil ditambahkan dengan kode: " . $kode_promo;
    }

    // HANDLE DELETE
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
        try {
            $delete_id = $_POST['delete_id'];
            
            // Delete the record
            $stmt = $conn->prepare("DELETE FROM promo WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            $success_message = "Data promo berhasil dihapus!";
        } catch(PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }

} catch(PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
} catch(Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Promo - Cafe Ciels</title>
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
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h2 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        .col-table {
            flex: 1;
            min-width: 600px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #34495e;
            margin-bottom: 8px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        th {
            background-color: #3498db;
            color: white;
            font-weight: 500;
            text-align: left;
            padding: 15px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background-color: #f39c12;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background-color: #d68910;
            transform: translateY(-2px);
        }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-left: 8px;
            cursor: pointer;
        }

        .btn-delete:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn-delete i {
            font-size: 12px;
        }

        .notification-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            min-width: 300px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .alert i {
            font-size: 18px;
        }

        .alert-success i {
            color: #28a745;
        }

        .alert-danger i {
            color: #dc3545;
        }

        .btn-create {
            background-color: #2ecc71;
            margin-bottom: 30px;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .btn-create:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .create-form {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 0;
        }

        .create-form.active {
            max-height: 1000px;
            opacity: 1;
            margin-bottom: 30px;
        }

        .create-form .card {
            transform: translateY(-20px);
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .create-form.active .card {
            transform: translateY(0);
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: #34495e;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
        }

        .back-button:hover {
            background-color: #2c3e50;
            transform: translateY(-2px);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-aktif {
            background-color: #d4edda;
            color: #155724;
        }

        .status-nonaktif {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 0 10px;
            }

            .col-table {
                min-width: 100%;
            }

            .card {
                padding: 15px;
            }

            th, td {
                padding: 10px;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        .delay-3 {
            animation-delay: 0.3s;
        }

        .delay-4 {
            animation-delay: 0.4s;
        }
    </style>
</head>
<body>
    <a href="admin_home.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Kembali ke Dashboard
    </a>

    <div class="container">
        <h2 class="animate-in">Manage Promo</h2>

        <button class="btn-create animate-in delay-1" onclick="toggleCreateForm()">
            <i class="fas fa-plus"></i> Tambah Promo Baru
        </button>
        
        <div id="createForm" class="create-form animate-in delay-2">
            <div class="card">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nama Promo</label>
                        <input type="text" class="form-input" name="nama_promo" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-input" name="deskripsi" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Diskon</label>
                        <select class="form-select" name="jenis_diskon" required onchange="toggleDiskonInput(this.value)">
                            <option value="Persentase">Persentase</option>
                            <option value="Nominal">Nominal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nilai Diskon</label>
                        <input type="number" class="form-input" name="nilai_diskon" required step="0.01" min="0">
                        <small id="diskonHelp" style="color: #666;">Masukkan nilai dalam persentase (0-100)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Minimum Pembelian</label>
                        <input type="number" class="form-input" name="min_pembelian" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-input" name="tanggal_mulai" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-input" name="tanggal_selesai" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Simpan</button>
                </form>
            </div>
        </div>

        <div class="row animate-in delay-3">
            <div class="col-table">
                <div class="card">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Jenis</th>
                                    <th>Nilai</th>
                                    <th>Min. Pembelian</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $conn->query("SELECT * FROM promo ORDER BY created_at DESC");
                                    while ($row = $stmt->fetch()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['kode_promo']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_promo']) . 
                                             "<br><small style='color: #666;'>" . htmlspecialchars($row['deskripsi']) . "</small></td>";
                                        echo "<td>" . htmlspecialchars($row['jenis_diskon']) . "</td>";
                                        echo "<td>" . ($row['jenis_diskon'] == 'Persentase' ? 
                                             number_format($row['nilai_diskon'], 0) . "%" : 
                                             "Rp " . number_format($row['nilai_diskon'], 0, ',', '.')) . "</td>";
                                        echo "<td>" . ($row['min_pembelian'] > 0 ? "Rp " . 
                                             number_format($row['min_pembelian'], 0, ',', '.') : "-") . "</td>";
                                        echo "<td>" . date('d/m/Y', strtotime($row['tanggal_mulai'])) . 
                                             "<br>s/d<br>" . date('d/m/Y', strtotime($row['tanggal_selesai'])) . "</td>";
                                        echo "<td><span class='status-badge status-" . strtolower($row['status']) . "'>" . 
                                             htmlspecialchars($row['status']) . "</span></td>";
                                        echo "<td class='action-buttons'>";
                                        echo "<a href='edit_promo.php?id=" . $row['id'] . "' class='btn-edit'><i class='fas fa-edit'></i> Edit</a>";
                                        echo "<form method='POST' style='display: inline;' onsubmit='return confirmDelete()'>";
                                        echo "<input type='hidden' name='delete_id' value='" . $row['id'] . "'>";
                                        echo "<button type='submit' class='btn-delete'><i class='fas fa-trash'></i> Hapus</button>";
                                        echo "</form>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } catch(PDOException $e) {
                                    echo "<tr><td colspan='8'>Error: " . $e->getMessage() . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="notification-container">
        <?php
        if (isset($success_message)) {
            echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i>$success_message</div>";
        }
        if (isset($error_message)) {
            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i>$error_message</div>";
        }
        ?>
    </div>

    <script>
        function toggleCreateForm() {
            const form = document.getElementById('createForm');
            form.classList.toggle('active');
        }

        function toggleDiskonInput(jenisDiskon) {
            const nilaiInput = document.querySelector('input[name="nilai_diskon"]');
            const helpText = document.getElementById('diskonHelp');
            
            if (jenisDiskon === 'Persentase') {
                nilaiInput.max = "100";
                helpText.textContent = "Masukkan nilai dalam persentase (0-100)";
            } else {
                nilaiInput.removeAttribute('max');
                helpText.textContent = "Masukkan nilai dalam Rupiah";
            }
        }

        function confirmDelete() {
            return confirm('Apakah Anda yakin ingin menghapus data promo ini?');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Existing notification code...

            // Add animation to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animation = 'none';
                row.style.opacity = '0';
                
                setTimeout(() => {
                    row.style.animation = `fadeInUp 0.6s ease forwards ${0.4 + (index * 0.1)}s`;
                }, 0);
            });
        });
    </script>
</body>
</html>
<?php $conn = null; ?> 