<?php
// download.php —— 计数下载（防直链，文件在受保护目录里）
declare(strict_types=1);
require __DIR__ . '/lib.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT * FROM scripts WHERE id=? AND status='approved'");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) { http_response_code(404); exit('脚本不存在或未上架'); }

$path = BASE_DIR . '/' . $row['file_path'];
if (!is_file($path)) { http_response_code(404); exit('文件已丢失'); }

db()->prepare('UPDATE scripts SET downloads=downloads+1 WHERE id=?')->execute([$id]);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . rawurlencode($row['filename']) . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
