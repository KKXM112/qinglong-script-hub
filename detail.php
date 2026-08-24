<?php
// detail.php —— 脚本详情 + 完整源码预览（带行号）
declare(strict_types=1);
require __DIR__ . '/lib.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT * FROM scripts WHERE id=? AND status='approved'");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) { http_response_code(404); exit('脚本不存在或未上架'); }

$lines = null;
$path = BASE_DIR . '/' . $row['file_path'];
if (is_file($path)) {
    $lines = file($path, FILE_IGNORE_NEW_LINES);
}
html_head('脚本详情 - ' . $row['filename']);
?>
<section>
  <h2><?= e($row['filename']) ?></h2>
  <div class="meta" style="justify-content:center">
    <span>类型 <?= e(strtoupper($row['type'])) ?></span>
    <span>⬇️ <?= (int)$row['downloads'] ?></span>
    <span>🕒 <?= fmt_ts((int)$row['ts']) ?></span>
    <span>📄 <?= $lines === null ? '未知' : count($lines) ?> 行</span>
  </div>
  <?php if ($row['description'] !== ''): ?>
    <div class="desc" style="margin-top:14px"><?= e($row['description']) ?></div>
  <?php endif; ?>
  <p style="text-align:center;margin:16px 0">
    <a class="btn" href="download.php?id=<?= (int)$row['id'] ?>">⬇️ 下载脚本</a>
  </p>
</section>

<?php if ($lines !== null): ?>
<section>
  <h2>源码预览 <small>完整 <?= count($lines) ?> 行</small></h2>
  <p class="hint" style="text-align:center;margin-bottom:12px">⚠️ 下载/运行前务必自行通读源码，警惕可疑外联与凭证窃取</p>
  <div class="codebox"><pre><code><?php
foreach ($lines as $i => $ln) {
    printf('<span class="ln">%d</span>%s%s', $i + 1, e($ln), "\n");
}
?></code></pre></div>
</section>
<?php else: ?>
<section><div class="empty"><span class="big">📭</span>源码文件已丢失，无法预览</div></section>
<?php endif; ?>
<?php html_foot();
