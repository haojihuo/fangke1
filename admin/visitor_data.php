<?php
require_once __DIR__ . '/../include/db.php';
$pdo = db();
$tempLinkId = (int)($_GET['temp_link_id'] ?? 0);
if (!$tempLinkId) {
    die('参数错误：缺少 temp_link_id');
}
$linkStmt = $pdo->prepare('SELECT * FROM temp_links WHERE id=?');
$linkStmt->execute([$tempLinkId]);
$link = $linkStmt->fetch();
if (!$link) {
    die('未找到对应访客链接');
}
$visitorStmt = $pdo->prepare('SELECT id, name, company, phone, has_car, car_number, created_at FROM visitors WHERE temp_link_id=? ORDER BY id DESC');
$visitorStmt->execute([$tempLinkId]);
$visitors = $visitorStmt->fetchAll();
?>
<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>访客数据</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<div class="container">
  <div class="card">
    <h2>访客数据（链接ID：<?= $tempLinkId ?>）</h2>
    <p class="hero-sub">随机ID：<?= htmlspecialchars($link['random_id']) ?> ｜ 有效期：<?= $link['start_at'] ?> ~ <?= $link['end_at'] ?></p>
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
    <br><a href="create_link.php"><button type="button" class="secondary action-btn">返回访客链接管理</button></a>
  </div>
</div>
</body></html>
