<?php
// index.php —— 脚本列表 + 搜索 + 分页（仅展示已上架脚本）
declare(strict_types=1);
require __DIR__ . '/lib.php';

$q    = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$size = (int)cfg('page_size');
$off  = ($page - 1) * $size;

$where  = "status='approved'";
$params = [];
if ($q !== '') {
    $where .= ' AND (filename LIKE :q OR description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$total = (int)db()->prepare("SELECT COUNT(*) FROM scripts WHERE $where")->execute() ? 0 : 0;
$cst = db()->prepare("SELECT COUNT(*) FROM scripts WHERE $where");
foreach ($params as $k => $v) $cst->bindValue($k, $v, PDO::PARAM_STR);
$cst->execute();
$total = (int)$cst->fetchColumn();

$stmt = db()->prepare("SELECT * FROM scripts WHERE $where ORDER BY id DESC LIMIT $size OFFSET $off");
foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pages = max(1, (int)ceil($total / $size));

html_head(cfg('site_name'));
hero();
show_msg($_GET['ok'] ?? null, $_GET['err'] ?? null);
?>
<section>
  <h2>脚本列表 <small style="font-size:13px;color:#888">(共 <?= $total ?> 个)</small></h2>
  <form class="searchbar" method="get" action="index.php">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="搜索脚本名称或简介关键词…">
    <button class="btn" type="submit">搜索</button>
  </form>

<?php if (!$rows): ?>
  <div class="empty"><span class="big">🗂️</span>暂无脚本<?= $q !== '' ? '，换个关键词试试' : '，快来上传第一个吧' ?></div>
<?php else: foreach ($rows as $r):
    $t = strtolower($r['type']);
    $cls = in_array($t, ['py','js','ts','sh'], true) ? "t-$t" : 't-other';
    $len = (int)cfg('desc_preview_len');
    $desc = mb_strlen($r['description']) > $len ? mb_substr($r['description'], 0, $len) . '…' : $r['description'];
?>
  <div class="card">
    <div class="card-top">
      <span class="badge <?= $cls ?>"><?= e(strtoupper($r['type'])) ?></span>
      <span class="card-title"><?= e($r['filename']) ?></span>
    </div>
    <?php if ($desc !== ''): ?><div class="desc"><?= e($desc) ?></div><?php endif; ?>
    <div class="meta">
      <span>⬇️ <?= (int)$r['downloads'] ?> 次下载</span>
      <span>🕒 <?= fmt_ts((int)$r['ts']) ?></span>
      <a href="download.php?id=<?= (int)$r['id'] ?>">下载脚本</a>
      <a href="detail.php?id=<?= (int)$r['id'] ?>">查看详情</a>
    </div>
  </div>
<?php endforeach; endif; ?>

  <div class="pager">
<?php
function plink(int $p, string $label, string $q, bool $cur = false): void {
    $u = 'index.php?page=' . $p . ($q !== '' ? '&q=' . urlencode($q) : '');
    printf('<%s href="%s">%s</%s>', $cur ? 'span class="cur"' : 'a', e($u), e($label), $cur ? 'span' : 'a');
}
if ($pages > 1) {
    if ($page > 1)     plink($page - 1, '‹ 上一页', $q);
    for ($i = 1; $i <= $pages; $i++) plink($i, (string)$i, $q, $i === $page);
    if ($page < $pages) plink($page + 1, '下一页 ›', $q);
}
?>
  </div>
</section>
<?php html_foot();
