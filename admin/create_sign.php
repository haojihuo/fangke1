<?php
require_once __DIR__ . '/../include/db.php';
$pdo = db();
$msg = '';
$type = 'notice';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tempLinkId = (int)($_POST['temp_link_id'] ?? 0);
    $total = max(1, (int)($_POST['total_times'] ?? 1));
    $globalStart = $_POST['start_at'] ?? '';
    $globalEnd = $_POST['end_at'] ?? '';

    if (!$tempLinkId || !$globalStart || !$globalEnd || strtotime($globalEnd) <= strtotime($globalStart)) {
        $msg = '基础信息不完整';
        $type = 'error';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO sign_tasks(temp_link_id, method, total_times, start_at, end_at) VALUES(?,?,?,?,?)');
            $stmt->execute([$tempLinkId, 'qrcode', $total, $globalStart, $globalEnd]);
            $taskId = (int)$pdo->lastInsertId();
            $slotStmt = $pdo->prepare('INSERT INTO sign_task_slots(sign_task_id, slot_index, start_at, end_at) VALUES(?,?,?,?)');

            for ($i = 1; $i <= $total; $i++) {
                $s = $_POST['slot_start_' . $i] ?? $globalStart;
                $e = $_POST['slot_end_' . $i] ?? $globalEnd;
                if (strtotime($e) <= strtotime($s)) {
                    throw new RuntimeException('第' . $i . '次时间区间无效');
                }
                $slotStmt->execute([$taskId, $i, $s, $e]);
            }
            $pdo->commit();
            $msg = '签到任务创建成功';
            $type = 'success';
        } catch (Throwable $e) {
            $pdo->rollBack();
            $msg = '创建失败：' . $e->getMessage();
            $type = 'error';
        }
    }
}

$links = $pdo->query('SELECT id, random_id FROM temp_links ORDER BY id DESC')->fetchAll();
$tasks = $pdo->query('SELECT st.*, tl.random_id FROM sign_tasks st JOIN temp_links tl ON st.temp_link_id=tl.id ORDER BY st.id DESC')->fetchAll();

$recordsStmt = $pdo->prepare("SELECT sr.id FROM sign_records sr WHERE sr.sign_task_id=? AND sr.visitor_id=? LIMIT 1");
$visitorsStmt = $pdo->prepare('SELECT id, name, company, phone FROM visitors WHERE temp_link_id=? ORDER BY id DESC');
$signDataMap = [];
foreach ($tasks as $t) {
    $visitorsStmt->execute([$t['temp_link_id']]);
    $rows = [];
    foreach ($visitorsStmt->fetchAll() as $v) {
        $recordsStmt->execute([$t['id'], $v['id']]);
        $isSigned = (bool)$recordsStmt->fetch();
        $rows[] = [
            'id' => $v['id'],
            'name' => $v['name'],
            'company' => $v['company'],
            'phone' => $v['phone'],
            'status' => $isSigned ? '已签到' : '未签到',
            'signed' => $isSigned,
        ];
    }
    $signDataMap[$t['id']] = $rows;
}
?>
<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>管理端-签到任务</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="container">
<div class="card"><h2>创建签到任务（二维码）</h2>
<?php if ($msg): ?><div class="notice <?= $type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" id="taskForm">
<label>选择访客ID</label><select name="temp_link_id" required><option value="">请选择</option><?php foreach ($links as $l): ?><option value="<?= $l['id'] ?>">ID <?= $l['id'] ?> / <?= htmlspecialchars($l['random_id']) ?></option><?php endforeach; ?></select>
<div class="grid"><div><label>签到开始时间</label><input type="datetime-local" name="start_at" required></div><div><label>签到结束时间</label><input type="datetime-local" name="end_at" required></div></div>
<label>签到方式</label><input value="只有二维码" disabled>
<label>签到次数</label><input type="number" name="total_times" id="totalTimes" min="1" value="1" required>
<div id="slotContainer"></div>
<button>确定并生成二维码</button>
</form>
</div>
<div class="card"><h2>签到任务列表</h2><table class="table"><thead><tr><th>任务ID</th><th>访客ID</th><th>次数</th><th>时间</th><th>扫码链接/二维码</th><th>操作</th></tr></thead><tbody>
<?php foreach ($tasks as $t): $url=app_url('user/sign_entry.php?task_id=' . $t['id']); $qr='https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($url); ?>
<tr><td><?= $t['id'] ?></td><td><?= $t['temp_link_id'] ?></td><td><?= $t['total_times'] ?></td><td><?= $t['start_at'] ?> ~ <?= $t['end_at'] ?></td><td><a href="<?= htmlspecialchars($url) ?>" target="_blank"><?= htmlspecialchars($url) ?></a><br><img src="<?= $qr ?>" width="80"></td><td><button type="button" class="secondary action-btn" onclick="openModal('sign-modal-<?= $t['id'] ?>')">签到数据</button></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</div>

<?php foreach ($tasks as $t): $rows = $signDataMap[$t['id']] ?? []; ?>
<div id="sign-modal-<?= $t['id'] ?>" class="modal-overlay">
  <div class="modal-card">
    <div class="modal-head"><h3>签到数据（任务ID：<?= $t['id'] ?>）</h3><button type="button" class="modal-close" onclick="closeModal(this)">×</button></div>
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
  </div>
</div>
<?php endforeach; ?>

<script>
const totalTimesEl = document.getElementById('totalTimes');
const slotContainer = document.getElementById('slotContainer');
function renderSlots(){
  const n = parseInt(totalTimesEl.value || '1',10);
  slotContainer.innerHTML='';
  if(n>2){
    for(let i=1;i<=n;i++){
      const box=document.createElement('div');
      box.className='grid';
      box.innerHTML=`<div><label>第${i}次开始时间</label><input type="datetime-local" name="slot_start_${i}" required></div><div><label>第${i}次结束时间</label><input type="datetime-local" name="slot_end_${i}" required></div>`;
      slotContainer.appendChild(box);
    }
  }
}
totalTimesEl.addEventListener('input',renderSlots);
renderSlots();

function openModal(id){
  var modal = document.getElementById(id);
  if (modal) modal.classList.add('show');
}
function closeModal(btn){
  var node = btn;
  while (node && !node.classList.contains('modal-overlay')) {
    node = node.parentNode;
  }
  if (node) node.classList.remove('show');
}
document.querySelectorAll('.modal-overlay').forEach(function(mask){
  mask.addEventListener('click', function(e){
    if (e.target === mask) mask.classList.remove('show');
  });
});
</script>
</body></html>
