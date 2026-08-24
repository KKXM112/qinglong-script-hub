<?php
// api.php —— 只读开放接口，返回 JSON（与参考站风格一致）
// GET api.php?action=list&page=1&size=10&q=关键词
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');

$action = (string)($_GET['action'] ?? 'list');
if ($action !== 'list') { http_response_code(404); exit(json_encode(['error' => 'unknown action'])); }

$page = max(1, (int)($_GET['page'] ?? 1));
$size = min(50, max(1, (int)($_GET['size'] ?? 10)));
$q    = trim((string)($_GET['q'] ?? ''));

$where = "status='approved'"; $params = [];
if ($q !== '') {
    $where .= ' AND (filename LIKE :q OR description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$cst = db()->prepare("SELECT COUNT(*) FROM scripts WHERE $where");
foreach ($params as $k => $v) $cst->bindValue($k, $v, PDO::PARAM_STR);
$cst->execute();
$total = (int)$cst->fetchColumn();

$stmt = db()->prepare("SELECT id,filename,type,description,downloads,ts
                       FROM scripts WHERE $where ORDER BY id DESC LIMIT $size OFFSET " . (($page-1)*$size));
foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
$stmt->execute();

$list = array_map(function (array $r): array {
    $r['id']         = (int)$r['id'];
    $r['downloads']  = (int)$r['downloads'];
    $r['ts']         = (int)$r['ts'];
    $r['created_at'] = date('Y-m-d H:i:s', (int)$r['ts']);
    return $r;
}, $stmt->fetchAll(PDO::FETCH_ASSOC));

echo json_encode([
    'total'    => $total,
    'page'     => $page,
    'pageSize' => $size,
    'scripts'  => $list,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
