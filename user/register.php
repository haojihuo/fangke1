<?php
require_once __DIR__ . '/../include/db.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$openid = $_GET['openid'] ?? '';
$msg = '';
$type = 'notice';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $hasCar = ($_POST['has_car'] ?? '') === '1' ? 1 : 0;
    $carNumber = trim($_POST['car_number'] ?? '');
    $openid = trim($_POST['openid'] ?? '');

    if (!$name || !$company || !$phone || !$openid || ($hasCar && !$carNumber)) {
        $msg = '请完整填写所有必填项';
        $type = 'error';
    } else {
        $sql = 'INSERT INTO visitors(temp_link_id, name, company, phone, has_car, car_number, openid) VALUES(?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE name=VALUES(name), company=VALUES(company), phone=VALUES(phone), has_car=VALUES(has_car), car_number=VALUES(car_number)';
        $st = $pdo->prepare($sql);
        $st->execute([$id, $name, $company, $phone, $hasCar, $hasCar ? $carNumber : null, $openid]);
        $msg = '保存成功';
        $type = 'success';
    }
}
?>
<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>访客登记</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<div class="container"><div class="card"><h2>访客登记</h2>
<?php if ($msg): ?><div class="notice <?= $type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" id="registerForm">
<label>姓名</label><input name="name" required>
<label>单位名称</label><input name="company" required>
<label>手机号</label><input name="phone" required>
<label>是否有车辆</label>
<select name="has_car" id="hasCar" required>
<option value="">请选择</option><option value="0">无</option><option value="1">有</option>
</select>
<div id="carBox" style="display:none;"><label>车牌号</label><input name="car_number" id="carNumber"></div>
<input type="hidden" name="openid" id="openid" value="<?= htmlspecialchars($openid) ?>">
<button type="button" id="wechatAuth" class="secondary">获取用户openid（授权微信登录）</button>
<br><br><button type="submit">确认提交</button>
</form></div></div>
<script>
document.getElementById('hasCar').addEventListener('change', function(){
  const show = this.value === '1';
  document.getElementById('carBox').style.display = show ? 'block' : 'none';
  document.getElementById('carNumber').required = show;
});

// 微信授权登录
document.getElementById('wechatAuth').addEventListener('click', function () {
  var appid = '<?php include "../include/config.php"; echo $wechat_appid; ?>';
  var redirect_uri = encodeURIComponent('http://' + window.location.host + '/user/wechat_callback.php?temp_link_id=<?php echo $_GET["id"]; ?>');
  var scope = 'snsapi_userinfo';
  var state = 'STATE';
  var authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' + appid + '&redirect_uri=' + redirect_uri + '&response_type=code&scope=' + scope + '&state=' + state + '#wechat_redirect';
  window.location.href = authUrl;
});

document.getElementById('registerForm').addEventListener('submit', function (e) {
  if (!document.getElementById('openid').value) {
    e.preventDefault();
    alert('请先点击“获取用户openid”完成微信授权');
  }
});
</script>
</body></html>
