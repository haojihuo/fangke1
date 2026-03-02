<?php
require_once __DIR__ . '/../include/db.php';
$pdo = db();
$taskId = (int)($_GET['task_id'] ?? 0);
if (!$taskId) {
    die('参数错误：缺少 task_id');
}
$taskStmt = $pdo->prepare('SELECT st.*, tl.random_id FROM sign_tasks st JOIN temp_links tl ON st.temp_link_id=tl.id WHERE st.id=?');
$taskStmt->execute([$taskId]);
$task = $taskStmt->fetch();
if (!$task) {
    die('未找到对应签到任务');
}
$visitorsStmt = $pdo->prepare('SELECT id, name, company, phone FROM visitors WHERE temp_link_id=? ORDER BY id DESC');
$recordStmt = $pdo->prepare('SELECT id FROM sign_records WHERE sign_task_id=? AND visitor_id=? LIMIT 1');
$visitorsStmt->execute([$task['temp_link_id']]);
$rows = [];
foreach ($visitorsStmt->fetchAll() as $v) {
    $recordStmt->execute([$taskId, $v['id']]);
    $signed = (bool)$recordStmt->fetch();
    $rows[] = [
        'id' => $v['id'],
        'name' => $v['name'],
        'company' => $v['company'],
        'phone' => $v['phone'],
        'status' => $signed ? '已签到' : '未签到',
        'signed' => $signed,
    ];
}
?>
<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>签到数据</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<div class="container">
  <div class="card">
    <h2>签到数据（任务ID：<?= $taskId ?>）</h2>
    <p class="hero-sub">关联访客ID：<?= $task['temp_link_id'] ?> ｜ 签到区间：<?= $task['start_at'] ?> ~ <?= $task['end_at'] ?> ｜ 次数：<?= $task['total_times'] ?></p>
    <table class="table"><thead><tr><th>访客ID</th><th>姓名</th><th>单位</th><th>手机号</th><th>状态</th></tr></thead><tbody>
      <?php if (!$rows): ?>
      <tr><td colspan="5">暂无访客数据</td></tr>
      <?php else: foreach ($rows as $r): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['company']) ?></td>
        <td><?= htmlspecialchars($r['phone']) ?></td>
        <td><span class="status-text <?= $r['signed'] ? 'status-ok' : 'status-no' ?>"><?= $r['status'] ?></span></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody></table>
    <br><a href="create_sign.php"><button type="button" class="secondary action-btn">返回签到任务管理</button></a>
  </div>
</div>
</body></html>
