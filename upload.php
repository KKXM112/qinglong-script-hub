<?php
// upload.php —— 上传脚本（验证码 + CSRF + 类型/大小校验 + 敏感词审核）
declare(strict_types=1);
require __DIR__ . '/lib.php';

$err = null; $ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $cap = trim((string)($_POST['captcha'] ?? ''));
    if (!preg_match('/^-?\d+$/', $cap)) {
        $err = '验证码错误';
    } else {
        $expect = (string)($_SESSION['captcha'] ?? 'gone');
        unset($_SESSION['captcha']);
        if ($cap === '' || !hash_equals($expect, $cap)) $err = '验证码错误';
    }

    // 文件基础校验
    if (!$err) {
        $f = $_FILES['script'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $err = '请选择脚本文件';
        } elseif ($f['size'] > (int)cfg('max_upload_bytes')) {
            $err = '文件超过大小限制（' . round(cfg('max_upload_bytes')/1024) . 'KB）';
        } else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, (array)cfg('allowed_ext'), true)) {
                $err = '仅支持 ' . implode('/', (array)cfg('allowed_ext')) . ' 文件';
            }
        }
    }

    // 简介长度
    $desc = trim((string)($_POST['description'] ?? ''));
    if (!$err && ($desc === '' || mb_strlen($desc) > 1000)) $err = '请填写 1000 字以内的脚本介绍（用法、环境变量等）';

    if (!$err) {
        $name  = $f['name'];
        $type  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $scan  = $desc . "\n" . $name;

        // 敏感词 → 强制人工审核
        $status = has_ban_word($scan) ? 'pending' : 'approved';

        $dir = BASE_DIR . '/uploads/' . date('Ymd');
        ensure_private_dir($dir);
        $newname = date('His') . '-' . bin2hex(random_bytes(4)) . '.' . $type;
        $rel = 'uploads/' . date('Ymd') . '/' . $newname;
        if (!move_uploaded_file($f['tmp_name'], BASE_DIR . '/' . $rel)) {
            $err = '保存文件失败';
        } else {
            $st = db()->prepare('INSERT INTO scripts (filename,type,description,file_path,downloads,status,uploader_ip,ts)
                                 VALUES (?,?,?,?,0,?,?,?)');
            $st->execute([$name, $type, $desc, $rel, $status, client_ip(), time()]);
            $ok = $status === 'approved'
                ? '上传成功，脚本已上架！'
                : '上传成功，但简介中包含需人工审核的内容，已进入待审核队列。';
        }
    }
}

html_head('上传脚本');
show_msg($ok, $err);
?>
<section>
  <h2>上传脚本</h2>
  <form method="post" action="upload.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>脚本文件（<?= e(implode(' / ', (array)cfg('allowed_ext'))) ?>，≤<?= round(cfg('max_upload_bytes')/1024) ?>KB）</label>
    <input type="file" name="script" required>
    <label>脚本介绍</label>
    <textarea name="description" maxlength="1000" required
      placeholder="填写用法、环境变量、抓包说明等，让其他人知道脚本怎么用…"></textarea>
    <label>验证码</label>
    <div class="captcha-line">
      <input type="text" name="captcha" required placeholder="计算结果" style="width:130px" autocomplete="off">
      <img src="captcha.php" alt="验证码" title="点击刷新"
           onclick="this.src='captcha.php?r='+Math.random()">
      <span class="hint">点击图片可刷新</span>
    </div>
    <p class="hint">⚠️ 禁止上传含引流、代刷、博彩等信息的脚本；所有上传记录 IP。</p>
    <p style="margin-top:20px"><button class="btn" type="submit">⬆️ 提交上传</button></p>
  </form>
</section>
<?php html_foot();
