<?php
// includes/db.php - Database connection
$host = '127.0.0.1';
$dbname = 'uber_cueillette';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;port=3307;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Connexion échouée: ' . $e->getMessage()]));
}
?>
