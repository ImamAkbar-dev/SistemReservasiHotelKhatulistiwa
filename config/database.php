<?php
$host = "localhost";
$dbname = "hoteldb_1"; 
$username = "root";
$password = "ZNVia243165ARVin#++";
try {
$conn = new PDO(
"mysql:host=$host;dbname=$dbname;charset=utf8",
$username,
$password
);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
die("Koneksi database gagal: " . $e->getMessage());}