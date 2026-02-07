<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "surat_app_db";

// Set MySQLi to throw exceptions for easier debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1. Connect to MySQL Server (without DB first)
    $koneksi = mysqli_connect($host, $user, $pass);

    // 2. Check if Database exists
    $result = mysqli_query($koneksi, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db'");
    
    if (mysqli_num_rows($result) == 0) {
        // 3. Create Database
        mysqli_query($koneksi, "CREATE DATABASE $db");
        mysqli_select_db($koneksi, $db);

        // 4. Create Tables
        $queries = [
            "CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100),
                role ENUM('admin', 'user') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE surat_masuk (
                id INT AUTO_INCREMENT PRIMARY KEY,
                no_surat VARCHAR(50) NOT NULL,
                tgl_surat DATE NOT NULL,
                tgl_terima DATE NOT NULL,
                asal_surat VARCHAR(100) NOT NULL,
                perihal TEXT NOT NULL,
                file_surat VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE surat_keluar (
                id INT AUTO_INCREMENT PRIMARY KEY,
                no_surat VARCHAR(50) NOT NULL,
                tgl_surat DATE NOT NULL,
                tgl_kirim DATE NOT NULL,
                tujuan_surat VARCHAR(100) NOT NULL,
                perihal TEXT NOT NULL,
                file_surat VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];

        foreach ($queries as $sql) {
            mysqli_query($koneksi, $sql);
        }

        // 5. Insert Default Admin
        $admin_pass = password_hash("admin123", PASSWORD_DEFAULT);
        mysqli_query($koneksi, "INSERT INTO users (username, password, full_name, role) VALUES ('admin', '$admin_pass', 'Administrator', 'admin')");
    } else {
        // Just select it if it exists
        mysqli_select_db($koneksi, $db);
    }

} catch (mysqli_sql_exception $e) {
    die("Kesalahan database: " . $e->getMessage());
}
?>
