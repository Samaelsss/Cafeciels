<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        nama VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    
    // Check if there are any users in the table
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    // If no users exist, create default admin and user accounts
    if ($userCount == 0) {
        // Create default admin account (password: admin123)
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role, nama, email) 
                VALUES ('admin', :admin_password, 'admin', 'Administrator', 'admin@cafeciels.com')";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':admin_password', $adminPassword);
        $stmt->execute();
        
        // Create default user account (password: user123)
        $userPassword = password_hash('kasir123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role, nama, email) 
                VALUES ('user', :user_password, 'user', 'Regular User', 'user@cafeciels.com')";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_password', $userPassword);
        $stmt->execute();
        

    }
    
    echo "Users table created successfully.<br>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
