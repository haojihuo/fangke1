<?php
require_once __DIR__ . '/../include/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$msg = '';
$type = 'notice';
$openid = '';
$canShowForm = false;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isWechatBrowser = stripos($ua, 'MicroMessenger') !== false;

$stmt = $pdo->prepare('SELECT * FROM temp_links WHERE id=?');
$stmt->execute([$id]);
$link = $stmt->fetch();
if (!$link) {
    die('无效链接');
}
$now = date('Y-m-d H:i:s');
if ($now < $link['start_at'] || $now > $link['end_at']) {
    die('该登记链接不在有效期内');
}

$sessionKey = 'visitor_openid_' . $id;

$oauthRedirect = app_url('user/register.php?id=' . $id);
$oauthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . urlencode($wechat_appid)
    . '&redirect_uri=' . urlencode($oauthRedirect)
    . '&response_type=code&scope=snsapi_userinfo&state=register#wechat_redirect';

if ($isWechatBrowser) {
    if (!empty($_GET['error']) && $_GET['error'] === 'access_denied') {
        $msg = '请点击下方按钮授权后方可填写表单哦';
        $type = 'error';
    } elseif (!empty($_GET['code'])) {
        $code = trim($_GET['code']);
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$wechat_appid&secret=$wechat_secret&code=$code&grant_type=authorization_code";
        $response = @file_get_contents($url);
        $result = $response ? json_decode($response, true) : [];

        if (!empty($result['openid'])) {
            $_SESSION[$sessionKey] = $result['openid'];
            header('Location: ' . app_url('user/register.php?id=' . $id));
            exit;
        }
        $msg = '微信授权失败，请点击下方按钮重新授权';
        $type = 'error';
    } elseif (!empty($_SESSION[$sessionKey])) {
        $openid = $_SESSION[$sessionKey];
        $canShowForm = true;
    } else {
        header('Location: ' . $oauthUrl);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isWechatBrowser && $canShowForm) {
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $hasCar = ($_POST['has_car'] ?? '') === '1' ? 1 : 0;
    $carNumber = trim($_POST['car_number'] ?? '');

    if (!$name || !$company || !$phone || !$openid || ($hasCar && !$carNumber)) {
        $msg = '请完整填写所有必填项';
        $type = 'error';
    } else {
        $sql = 'INSERT INTO visitors(temp_link_id, name, company, phone, has_car, car_number, openid) VALUES(?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE name=VALUES(name), company=VALUES(company), phone=VALUES(phone), has_car=VALUES(has_car), car_number=VALUES(car_number)';
        $st = $pdo->prepare($sql);
        $st->execute([$id, $name, $company, $phone, $hasCar, $hasCar ? $carNumber : null, $openid]);
        header('Location: ' . app_url('user/register_success.php?name=' . urlencode($name)));
        exit;
    }
}
?>
<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>访客登记</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<div class="container mobile-wrap"><div class="card">
<h2 class="hero-title">访客预约登记</h2>
<p class="hero-sub">请填写完整信息，提交后将用于现场签到核验。</p>
<?php if ($msg): ?><div class="notice <?= $type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<?php if (!$isWechatBrowser): ?>
  <div class="notice error">请使用微信扫描访问</div>
<?php elseif (!$canShowForm): ?>
  <div class="notice error">请点击下方按钮授权后方可填写表单哦</div>
  <button type="button" onclick="window.location.href='<?= htmlspecialchars($oauthUrl) ?>'">微信授权后继续填写</button>
<?php else: ?>
<form method="post" id="registerForm">
<div class="form-group"><label>姓名</label><input name="name" required></div>
<div class="form-group"><label>单位名称</label><input name="company" required></div>
<div class="form-group"><label>手机号</label><input name="phone" required></div>
<div class="form-group"><label>是否有车辆</label>
<select name="has_car" id="hasCar" required>
<option value="">请选择</option><option value="0">无</option><option value="1">有</option>
</select></div>
<div id="carBox" class="form-group" style="display:none;"><label>车牌号</label><input name="car_number" id="carNumber"></div>
<button type="submit">确认预约</button>
</form>
<?php endif; ?>
</div></div>
<script>
var hasCarEl = document.getElementById('hasCar');
if (hasCarEl) {
  hasCarEl.addEventListener('change', function(){
    const show = this.value === '1';
    document.getElementById('carBox').style.display = show ? 'block' : 'none';
    document.getElementById('carNumber').required = show;
  });
}
</script>
</body></html>
