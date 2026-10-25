<?php
// php/photo.php - Serves ouvrier photos stored as BLOB
require_once '../includes/session.php';
require_once '../includes/db.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { http_response_code(404); exit; }

$stmt = $pdo->prepare("SELECT photo FROM ouvrier WHERE id_ouvrier=?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || !$row['photo']) {
    http_response_code(404);
    exit;
}

// Detect actual image format so PNG/WebP/GIF are served correctly
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->buffer($row['photo']) ?: 'image/jpeg';
header('Content-Type: ' . $mime);
header('Cache-Control: max-age=86400');
echo $row['photo'];
?>
