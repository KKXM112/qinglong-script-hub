<?php
// lib.php —— 公共函数、数据库初始化、页面布局（暗色极客风 UI）
declare(strict_types=1);

define('BASE_DIR', __DIR__);
$GLOBALS['config'] = require BASE_DIR . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function cfg(string $key) { return $GLOBALS['config'][$key] ?? null; }

/* ---------- 数据库 ---------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = BASE_DIR . '/data';
        ensure_private_dir($dir);
        $pdo = new PDO('sqlite:' . $dir . '/ql.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE IF NOT EXISTS scripts (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            filename     TEXT NOT NULL,
            type         TEXT NOT NULL,
            description  TEXT NOT NULL DEFAULT "",
            file_path    TEXT NOT NULL,
            downloads    INTEGER NOT NULL DEFAULT 0,
            status       TEXT NOT NULL DEFAULT "pending",
            uploader_ip  TEXT NOT NULL DEFAULT "",
            ts           INTEGER NOT NULL DEFAULT 0
        )');
    }
    return $pdo;
}

/* 建目录并放入 .htaccess 拒绝直接访问（Apache 生效；nginx 规则见 README） */
function ensure_private_dir(string $path): void {
    if (!is_dir($path)) mkdir($path, 0755, true);
    $ht = $path . '/.htaccess';
    if (!file_exists($ht)) file_put_contents($ht, "Require all denied\n");
}

/* ---------- 工具 ---------- */
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || $t === '' || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(403);
        exit('CSRF 校验失败，请返回刷新页面后重试');
    }
}

function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? ''; }

function fmt_ts(int $ts): string { return date('Y-m-d H:i', $ts); }

function has_ban_word(string $text): bool {
    foreach ((array)cfg('ban_words') as $w) {
        if ($w !== '' && stripos($text, $w) !== false) return true;
    }
    return false;
}

function is_admin(): bool { return !empty($_SESSION['admin']); }

/* ---------- 页面布局 ---------- */
function html_head(string $title): void {
    ?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0b0f14">
<title><?= e($title) ?></title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐉</text></svg>">
<style>
:root{
  --bg:#f6f8fb; --surface:#ffffff; --surface2:#f0f4f9; --border:#e3e9f1;
  --text:#1a2733; --muted:#5b6b7d; --dim:#8a99ab;
  --accent:#0ea87c; --accent-dim:#0b8a66; --accent-glow:rgba(14,168,124,.13);
  --blue:#2f7fe8; --red:#e5484d; --orange:#e8862f;
  --mono:'SF Mono','JetBrains Mono',Consolas,'Courier New',monospace;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:-apple-system,'PingFang SC','HarmonyOS Sans SC','Microsoft YaHei',sans-serif;
  background:var(--bg);color:var(--text);line-height:1.65;
  background-image:
    radial-gradient(ellipse 60% 40% at 15% -10%,rgba(14,168,124,.06),transparent),
    radial-gradient(ellipse 50% 35% at 85% -10%,rgba(47,127,232,.05),transparent);
  background-attachment:fixed;
}
.container{max-width:920px;margin:0 auto;padding:0 18px}

/* ── 顶栏 ── */
.topbar{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.85);backdrop-filter:blur(12px);
  border-bottom:1px solid var(--border)}
.topbar-in{max-width:920px;margin:0 auto;padding:0 18px;height:58px;display:flex;align-items:center;gap:22px}
.logo{display:flex;align-items:center;gap:9px;font-weight:700;font-size:17px;color:var(--text);text-decoration:none;letter-spacing:.5px}
.logo .dragon{font-size:21px}
.logo em{font-style:normal;background:linear-gradient(120deg,var(--accent),var(--blue));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
nav.top{display:flex;gap:4px;margin-left:auto;flex-wrap:wrap}
nav.top a{color:var(--muted);text-decoration:none;font-size:13.5px;padding:6px 12px;border-radius:8px;transition:.18s}
nav.top a:hover{color:var(--text);background:var(--surface2)}

/* ── hero ── */
.hero{text-align:center;padding:44px 0 30px}
.hero h1{font-size:26px;font-weight:800;letter-spacing:1px}
.hero h1 em{font-style:normal;background:linear-gradient(120deg,var(--accent) 20%,var(--blue));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero p{color:var(--muted);font-size:13.5px;margin-top:8px}
.hero .tagline{display:inline-flex;gap:8px;margin-top:14px;flex-wrap:wrap;justify-content:center}
.hero .tagline span{font-size:12px;color:var(--accent-dim);background:var(--accent-glow);
  border:1px solid rgba(14,168,124,.22);padding:3px 12px;border-radius:99px}

footer{margin-top:48px;border-top:1px solid var(--border);padding:22px 0 34px;text-align:center;color:var(--dim);font-size:12px}

/* ── 卡片 / 区块 ── */
section{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px;margin:20px 0;box-shadow:0 1px 3px rgba(16,42,67,.04)}
h2{font-size:16px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
h2::before{content:'';width:3px;height:15px;border-radius:2px;background:linear-gradient(var(--accent),var(--blue))}
h2 small{font-weight:400;color:var(--muted);font-size:12px}

/* ── 按钮 ── */
.btn{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,var(--accent),#0c9a72);
  color:#fff;border:none;padding:9px 22px;font-size:14px;font-weight:600;border-radius:9px;cursor:pointer;text-decoration:none;transition:.2s}
.btn:hover{filter:brightness(1.06);box-shadow:0 4px 18px rgba(14,168,124,.32);transform:translateY(-1px)}
.btn-sm{padding:4px 12px;font-size:12.5px;border-radius:7px}
.btn-red{background:linear-gradient(135deg,#f25b60,#e5484d);color:#fff}
.btn-red:hover{box-shadow:0 4px 18px rgba(229,72,77,.3)}
.btn-ghost{background:var(--surface2);color:var(--text);border:1px solid var(--border)}
.btn-ghost:hover{border-color:var(--accent-dim);box-shadow:none}

/* ── 搜索 ── */
.searchbar{display:flex;gap:10px;margin-bottom:18px}
.searchbar input[type=text]{flex:1;background:var(--bg);border:1px solid var(--border);color:var(--text);
  padding:10px 15px;border-radius:9px;font-size:14px;transition:.2s;outline:none}
.searchbar input[type=text]:focus{border-color:var(--accent-dim);box-shadow:0 0 0 3px var(--accent-glow)}

/* ── 脚本卡片 ── */
.card{background:var(--surface);border:1px solid var(--border);border-radius:11px;padding:15px 17px;margin-bottom:12px;transition:.2s}
.card:hover{border-color:#bcd4e8;transform:translateY(-2px);box-shadow:0 8px 24px rgba(16,42,67,.09)}
.card-top{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.card-title{font-weight:600;font-size:14.5px;word-break:break-all;font-family:var(--mono)}
.badge{font-family:var(--mono);font-size:10.5px;font-weight:700;padding:2px 9px;border-radius:6px;letter-spacing:.5px}
.t-py{background:rgba(47,127,232,.1);color:#2f6fd0;border:1px solid rgba(47,127,232,.22)}
.t-js{background:rgba(197,160,10,.12);color:#92700a;border:1px solid rgba(197,160,10,.25)}
.t-ts{background:rgba(49,120,198,.1);color:#2864ad;border:1px solid rgba(49,120,198,.22)}
.t-sh{background:rgba(58,150,60,.1);color:#2e7d31;border:1px solid rgba(58,150,60,.22)}
.t-other{background:var(--surface2);color:var(--muted);border:1px solid var(--border)}
.meta{font-size:12.5px;color:var(--muted);margin-top:10px;display:flex;gap:16px;flex-wrap:wrap;align-items:center}
.meta a{color:var(--accent-dim);text-decoration:none}
.meta a:hover{text-decoration:underline}
.desc{font-size:13px;color:var(--muted);margin-top:10px;white-space:pre-wrap;word-break:break-word;
  background:var(--bg);border:1px dashed var(--border);border-radius:9px;padding:10px 13px;max-height:120px;overflow:hidden}

/* ── 分页 ── */
.pager{text-align:center;margin-top:22px;display:flex;gap:6px;justify-content:center;flex-wrap:wrap}
.pager a,.pager span{display:inline-block;padding:5px 13px;border-radius:8px;font-size:13px;
  text-decoration:none;color:var(--muted);border:1px solid var(--border);transition:.15s;font-family:var(--mono)}
.pager a:hover{color:var(--accent-dim);border-color:var(--accent-dim)}
.pager .cur{background:var(--accent-glow);color:var(--accent-dim);border-color:var(--accent-dim);font-weight:700}

/* ── 表单 ── */
form label{display:block;font-size:13.5px;font-weight:600;margin:16px 0 7px;color:var(--text)}
form input[type=text],form textarea,form input[type=password],form input[type=file]{width:100%;
  background:var(--surface);border:1px solid var(--border);color:var(--text);padding:10px 14px;
  border-radius:9px;font-size:14px;font-family:inherit;outline:none;transition:.2s}
form input:focus,form textarea:focus{border-color:var(--accent-dim);box-shadow:0 0 0 3px var(--accent-glow)}
form input[type=file]{color:var(--muted)} form input[type=file]::file-selector-button{
  background:var(--surface2);color:var(--accent-dim);border:1px solid var(--border);border-radius:7px;
  padding:6px 14px;margin-right:12px;cursor:pointer;font-size:13px}
form textarea{min-height:120px;resize:vertical}
.hint{font-size:12px;color:var(--dim);margin-top:5px}
.captcha-line{display:flex;align-items:center;gap:12px}
.captcha-line svg,.captcha-line img{border-radius:9px;border:1px solid var(--border);cursor:pointer}

/* ── 提示条 ── */
.msg{padding:12px 17px;border-radius:10px;margin:14px 0;font-size:13.5px}
.msg-ok{background:rgba(14,168,124,.09);color:#0b7d5d;border:1px solid rgba(14,168,124,.28)}
.msg-err{background:rgba(229,72,77,.07);color:#c5363b;border:1px solid rgba(229,72,77,.25)}

/* ── 表格（后台）── */
table.list{width:100%;border-collapse:collapse;font-size:13px}
table.list th{color:var(--dim);font-weight:600;text-align:left;padding:9px 8px;border-bottom:1px solid var(--border);white-space:nowrap;background:var(--surface2)}
table.list td{padding:10px 8px;border-bottom:1px solid #eef2f7;vertical-align:top}
.st-pending{color:var(--orange);font-weight:700}.st-approved{color:var(--accent-dim);font-weight:700}.st-rejected{color:var(--red);font-weight:700}

/* ── 代码预览（亮色编辑器风）── */
.codebox{overflow:auto;max-height:640px;background:#fbfcfe;border:1px solid var(--border);border-radius:11px;padding:16px}
.codebox pre{font-family:var(--mono);font-size:12.5px;line-height:1.7;color:#243447;margin:0}
.codebox .ln{display:inline-block;width:3em;color:#a8b6c6;user-select:none;text-align:right;padding-right:14px}

/* ── 空状态 ── */
.empty{text-align:center;color:var(--dim);padding:46px 0;font-size:14px}
.empty .big{font-size:38px;display:block;margin-bottom:10px;opacity:.75}

/* ── 手机端适配 ── */
.menu-btn{display:none;margin-left:auto;background:none;border:1px solid var(--border);border-radius:8px;
  padding:7px 11px;font-size:17px;line-height:1;cursor:pointer;color:var(--text)}
.drawer-mask{display:none;position:fixed;inset:0;background:rgba(16,42,67,.4);z-index:90}
.drawer{position:fixed;top:0;right:-270px;width:260px;height:100%;background:var(--surface);z-index:95;
  box-shadow:-6px 0 24px rgba(16,42,67,.15);transition:right .25s ease;display:flex;flex-direction:column}
.drawer.open{right:0} .drawer-mask.open{display:block}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--border);font-weight:700}
.drawer-close{background:none;border:none;font-size:20px;color:var(--muted);cursor:pointer;padding:2px 8px;border-radius:6px}
.drawer-close:hover{background:var(--surface2)}
.drawer nav{display:flex;flex-direction:column;padding:10px}
.drawer nav a{text-decoration:none;color:var(--text);font-size:15px;padding:13px 14px;border-radius:9px;display:flex;align-items:center;gap:10px}
.drawer nav a:hover,.drawer nav a:active{background:var(--surface2)}
.drawer-foot{margin-top:auto;padding:14px 18px;border-top:1px solid var(--border);font-size:11.5px;color:var(--dim)}

@media (max-width:720px){
  .topbar-in{height:52px;gap:12px}
  .logo span:last-child{font-size:16px}
  nav.top{display:none}          /* 桌面导航隐藏，改用抽屉菜单 */
  .menu-btn{display:inline-flex;align-items:center}
  .container{padding:0 12px}
  section{padding:16px 14px;margin:14px 0;border-radius:12px}
  .hero{padding:28px 0 18px}
  .hero h1{font-size:21px}
  .hero p{font-size:12.5px}
  .hero .tagline span{font-size:11px;padding:2.5px 10px}
  h2{font-size:15px}
  .searchbar{flex-wrap:wrap}
  .searchbar input[type=text]{min-width:0;width:100%}
  .searchbar .btn{width:100%;justify-content:center;margin-top:2px}
  .card{padding:13px 13px}
  .card-title{font-size:13.5px}
  .meta{gap:12px;font-size:12px}
  .desc{max-height:96px;font-size:12.5px}
  form input[type=text],form textarea,form input[type=password]{font-size:16px} /* 防 iOS 聚焦缩放 */
  .captcha-line{flex-wrap:wrap}
  .btn{padding:9px 18px}
  table.list{font-size:12px;display:block;overflow-x:auto;white-space:nowrap}
  .codebox{max-height:70vh;padding:12px;border-radius:9px}
  .codebox pre{font-size:11.5px}
  .codebox .ln{width:2.3em;padding-right:9px}
  footer{font-size:11px;padding:18px 0 26px}
}
</style>
</head>
<body>
<div class="topbar"><div class="topbar-in">
  <a class="logo" href="index.php"><span class="dragon">🐉</span><span>青龙<em>脚本库</em></span></a>
  <nav class="top">
    <a href="index.php">脚本列表</a>
    <a href="upload.php">上传</a>
    <a href="api.php?action=list">API</a>
  </nav>
  <button class="menu-btn" onclick="openDrawer()" aria-label="菜单">☰</button>
</div></div>

<!-- 手机端抽屉导航（不含管理入口，管理走独立页面 admin.php） -->
<div class="drawer-mask" id="drawerMask" onclick="closeDrawer()"></div>
<aside class="drawer" id="drawer">
  <div class="drawer-head"><span>🐉 青龙脚本库</span><button class="drawer-close" onclick="closeDrawer()">✕</button></div>
  <nav>
    <a href="index.php" onclick="closeDrawer()">📜 脚本列表</a>
    <a href="upload.php" onclick="closeDrawer()">⬆️ 上传脚本</a>
    <a href="api.php?action=list" onclick="closeDrawer()">🔌 开放接口</a>
  </nav>
  <div class="drawer-foot">脚本均来自网友上传 · 注意安全</div>
</aside>
<script>
function openDrawer(){document.getElementById('drawer').classList.add('open');document.getElementById('drawerMask').classList.add('open')}
function closeDrawer(){document.getElementById('drawer').classList.remove('open');document.getElementById('drawerMask').classList.remove('open')}
</script>
<div class="container">
<?php
}

function html_foot(): void {
    ?></div>
<footer>所有脚本均来自网友上传 · 未做安全审计 · 运行前请自行检查代码 · 请在 24 小时内删除</footer>
</body>
</html><?php
}

/* 列表页 hero 标语（仅首页用） */
function hero(): void {
    echo '<div class="hero"><h1>青龙面板 · <em>脚本共享库</em></h1>'
       . '<p>不再为寻找脚本而烦恼 —— 汇聚网友上传的定时任务脚本</p>'
       . '<div class="tagline"><span>🐍 Python</span><span>🟨 JavaScript</span><span>🐚 Shell</span><span>⚡ 免费分享</span></div></div>';
}

function show_msg(?string $ok, ?string $err): void {
    if ($ok)  echo '<div class="msg msg-ok">✅ ' . e($ok) . '</div>';
    if ($err) echo '<div class="msg msg-err">❌ ' . e($err) . '</div>';
}
