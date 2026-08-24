<?php
// captcha.php —— 输出 SVG 算术验证码，答案存 session
declare(strict_types=1);
require __DIR__ . '/lib.php';

$a = random_int(2, 9);
$b = random_int(1, 9);
$ops = ['+', '-', '×'];
$op = $ops[array_rand($ops)];
switch ($op) {
    case '+': $ans = $a + $b; break;
    case '-': $ans = $a - $b; $b = min($a, $b) === $b ? $b : $a; break; // 保证非负
    default:  $ans = $a * $b;
}
if ($op === '-' && $b > $a) { [$a, $b] = [$b, $a]; }
$_SESSION['captcha'] = (string)$ans;

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-store');
$x1 = 20; $x2 = 55; $x3 = 90;
$j1 = random_int(-6, 6); $j2 = random_int(-6, 6); $j3 = random_int(-6, 6);
$c1 = sprintf('#%06x', random_int(0x222222, 0x999999));
$c2 = sprintf('#%06x', random_int(0x222222, 0x999999));
$c3 = sprintf('#%06x', random_int(0x222222, 0x999999));
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" width="130" height="44" viewBox="0 0 130 44">
  <rect width="130" height="44" fill="#f6f8fb"/>
  <?php for ($i = 0; $i < 3; $i++): ?>
  <line x1="<?= random_int(0,130) ?>" y1="<?= random_int(0,44) ?>" x2="<?= random_int(0,130) ?>" y2="<?= random_int(0,44) ?>" stroke="#d5dbe3" stroke-width="1"/>
  <?php endfor; ?>
  <text x="<?= $x1 ?>" y="<?= 29 + $j1 ?>" font-size="22" font-family="monospace" fill="<?= $c1 ?>"><?= $a ?></text>
  <text x="<?= $x2 ?>" y="<?= 29 + $j2 ?>" font-size="22" font-family="monospace" fill="<?= $c2 ?>"><?= $op ?></text>
  <text x="<?= $x3 ?>" y="<?= 29 + $j3 ?>" font-size="22" font-family="monospace" fill="<?= $c3 ?>"><?= $b ?></text>
  <text x="104" y="29" font-size="20" fill="#666">= ?</text>
</svg>
