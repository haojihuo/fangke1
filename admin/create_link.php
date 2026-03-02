<?php
require_once __DIR__ . '/../include/db.php';
$pdo = db();
$msg = '';
$type = 'notice';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startAt = $_POST['start_at'] ?? '';
    $endAt = $_POST['end_at'] ?? '';
    if (!$startAt || !$endAt || strtotime($endAt) <= strtotime($startAt)) {
        $msg = '开始结束时间不正确';
        $type = 'error';
    } else {
        $randomId = random_token(12);
        $stmt = $pdo->prepare('INSERT INTO temp_links(random_id, start_at, end_at) VALUES(?,?,?)');
        $stmt->execute([$randomId, $startAt, $endAt]);
        $msg = '创建成功';
        $type = 'success';
    }
}
$links = $pdo->query('SELECT * FROM temp_links ORDER BY id DESC')->fetchAll();
$visitorStmt = $pdo->prepare('SELECT id, name, company, phone, has_car, car_number, created_at FROM visitors WHERE temp_link_id=? ORDER BY id DESC');
$visitorMap = [];
foreach ($links as $row) {
    $visitorStmt->execute([$row['id']]);
    $visitorMap[$row['id']] = $visitorStmt->fetchAll();
}
?>
<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>管理端-访客链接</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<div class="container">
<div class="card">
<h2>创建访客登记临时链接</h2>
<?php if ($msg): ?><div class="notice <?= $type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post">
<div class="grid">
<div><label>开始时间</label><input type="datetime-local" name="start_at" required></div>
<div><label>结束时间</label><input type="datetime-local" name="end_at" required></div>
</div>
<button type="submit">保存并生成临时链接</button>
</form>
</div>
<div class="card">
<h2>已生成链接</h2>
<table class="table"><thead><tr><th>ID</th><th>随机ID</th><th>有效期</th><th>临时链接</th><th>二维码</th><th>操作</th></tr></thead><tbody>
<?php foreach ($links as $row):
    $url = app_url('user/register.php?id=' . $row['id']);
    $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($url);
?>
<tr>
<td><?= $row['id'] ?></td><td><?= htmlspecialchars($row['random_id']) ?></td><td><?= $row['start_at'] ?> ~ <?= $row['end_at'] ?></td>
<td><a href="<?= htmlspecialchars($url) ?>" target="_blank"><?= htmlspecialchars($url) ?></a></td>
<td><img src="<?= $qr ?>" alt="qr" width="80"></td>
<td><button type="button" class="secondary data-btn" data-modal="visitor-modal-<?= $row['id'] ?>">访客数据</button></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div>

<?php foreach ($links as $row): $visitors = $visitorMap[$row['id']] ?? []; ?>
<div id="visitor-modal-<?= $row['id'] ?>" class="modal-overlay">
  <div class="modal-card">
    <div class="modal-head"><h3>访客数据（链接ID：<?= $row['id'] ?>）</h3><button type="button" class="modal-close">×</button></div>
    <table class="table"><thead><tr><th>ID</th><th>姓名</th><th>单位</th><th>手机号</th><th>是否有车</th><th>车牌号</th><th>登记时间</th></tr></thead><tbody>
      <?php if (!$visitors): ?>
      <tr><td colspan="7">暂无访客数据</td></tr>
      <?php else: foreach ($visitors as $v): ?>
      <tr>
        <td><?= $v['id'] ?></td>
        <td><?= htmlspecialchars($v['name']) ?></td>
        <td><?= htmlspecialchars($v['company']) ?></td>
        <td><?= htmlspecialchars($v['phone']) ?></td>
        <td><?= $v['has_car'] ? '有' : '无' ?></td>
        <td><?= htmlspecialchars((string)($v['car_number'] ?? '')) ?></td>
        <td><?= $v['created_at'] ?></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody></table>
  </div>
</div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.data-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    var modal = document.getElementById(btn.dataset.modal);
    if (modal) modal.classList.add('show');
  });
});
document.querySelectorAll('.modal-close').forEach(function(btn){
  btn.addEventListener('click', function(){
    btn.closest('.modal-overlay').classList.remove('show');
  });
});
document.querySelectorAll('.modal-overlay').forEach(function(mask){
  mask.addEventListener('click', function(e){
    if (e.target === mask) mask.classList.remove('show');
  });
});
</script>
</body></html>
