<?php
// admin.php —— 管理后台：登录 / 审核列表 / 通过 / 拒绝 / 删除
declare(strict_types=1);
require __DIR__ . '/lib.php';

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'login') {
    csrf_check();
    if (hash_equals((string)cfg('admin_password'), (string)($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
    } else {
        $err = '密码错误';
    }
}

if (is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = (string)($_POST['do'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $map = ['approve' => 'approved', 'reject' => 'rejected'];
    if (isset($map[$do])) {
        db()->prepare('UPDATE scripts SET status=? WHERE id=?')->execute([$map[$do], $id]);
    } elseif ($do === 'delete') {
        $st = db()->prepare('SELECT file_path FROM scripts WHERE id=?');
        $st->execute([$id]);
        if ($p = $st->fetchColumn()) @unlink(BASE_DIR . '/' . $p);
        db()->prepare('DELETE FROM scripts WHERE id=?')->execute([$id]);
    }
    header('Location: admin.php');
    exit;
}

html_head('管理后台');

if (!is_admin()): ?>
<section style="max-width:420px;margin:30px auto">
  <h2>管理员登录</h2>
  <?php show_msg(null, $err); ?>
  <form method="post" action="admin.php">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="do" value="login">
    <label>管理密码</label>
    <input type="password" name="password" required autofocus>
    <p style="margin-top:16px"><button class="btn" type="submit">登录</button></p>
  </form>
</section>
<?php else:

$status = (string)($_GET['status'] ?? 'all');
$where = in_array($status, ['pending','approved','rejected'], true) ? "WHERE status='$status'" : '';
$rows = db()->query("SELECT * FROM scripts $where ORDER BY id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
?>
<section>
  <h2>审核管理</h2>
  <nav class="top" style="margin-top:0">
    <?php foreach (['all'=>'全部','pending'=>'待审核','approved'=>'已上架','rejected'=>'已拒绝'] as $k => $label): ?>
    <a href="admin.php?status=<?= $k ?>" style="<?= $status === $k || ($k==='all'&&$status==='all') ? 'font-weight:700' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <a href="admin.php?logout=1" style="color:#ff4757">退出</a>
  </nav>

  <?php if (!$rows): ?><p style="text-align:center;color:#999;padding:20px 0">没有记录</p><?php endif; ?>
  <table class="list">
    <tr><th>ID</th><th>文件</th><th>简介摘要</th><th>IP</th><th>时间</th><th>状态</th><th>操作</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= e($r['filename']) ?></td>
      <td style="max-width:260px"><?= e(mb_substr($r['description'], 0, 60)) ?><?= mb_strlen($r['description']) > 60 ? '…' : '' ?></td>
      <td><?= e($r['uploader_ip']) ?></td>
      <td><?= fmt_ts((int)$r['ts']) ?></td>
      <td class="st-<?= e($r['status']) ?>"><?=
        ['pending'=>'待审核','approved'=>'已上架','rejected'=>'已拒绝'][$r['status']] ?? $r['status'] ?></td>
      <td>
        <?php if ($r['status'] !== 'approved'): ?>
        <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="do" value="approve"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-sm btn-green" type="submit">通过</button></form>
        <?php endif; ?>
        <?php if ($r['status'] !== 'rejected'): ?>
        <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="do" value="reject"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-sm" type="submit">拒绝</button></form>
        <?php endif; ?>
        <form method="post" style="display:inline" onsubmit="return confirm('确定删除该脚本及文件？')">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-sm btn-red" type="submit">删除</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</section>
<?php endif; html_foot();
