<?php
require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/../include/config.php';
$pdo = db();

$taskId = (int)($_GET['task_id'] ?? 0);
$code = $_GET['code'] ?? '';
$resultText = '签到失败';
$detailText = '请联系现场工作人员处理。';
$type = 'error';
$visitorName = '';

if ($taskId && $code) {
    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$wechat_appid&secret=$wechat_secret&code=$code&grant_type=authorization_code";
    $response = file_get_contents($url);
    $result = json_decode($response, true);

    if (isset($result['openid'])) {
        $openid = $result['openid'];

        $st = $pdo->prepare('SELECT * FROM sign_tasks WHERE id=?');
        $st->execute([$taskId]);
        $task = $st->fetch();

        if ($task) {
            $v = $pdo->prepare('SELECT * FROM visitors WHERE temp_link_id=? AND openid=?');
            $v->execute([$task['temp_link_id'], $openid]);
            $visitor = $v->fetch();

            if ($visitor) {
                $visitorName = $visitor['name'];
                $slots = $pdo->prepare('SELECT * FROM sign_task_slots WHERE sign_task_id=? ORDER BY slot_index ASC');
                $slots->execute([$taskId]);
                $slotRows = $slots->fetchAll();
                $now = date('Y-m-d H:i:s');

                foreach ($slotRows as $slot) {
                    if ($now < $slot['start_at'] || $now > $slot['end_at']) {
                        continue;
                    }
                    $c = $pdo->prepare('SELECT id FROM sign_records WHERE sign_task_id=? AND visitor_id=? AND slot_index=?');
                    $c->execute([$taskId, $visitor['id'], $slot['slot_index']]);
                    if ($c->fetch()) {
                        continue;
                    }
                    $ins = $pdo->prepare('INSERT INTO sign_records(sign_task_id, visitor_id, slot_index) VALUES(?,?,?)');
                    $ins->execute([$taskId, $visitor['id'], $slot['slot_index']]);
                    $resultText = '签到成功！';
                    $detailText = '欢迎您 ' . $visitorName . '。';
                    $type = 'success';
                    break;
                }

                if ($type !== 'success') {
                    $resultText = '签到失败';
                    $detailText = '不在可签到时间内，或当前时段已签到。';
                }
            } else {
                $resultText = '签到失败';
                $detailText = '未找到预约登记信息。';
            }
        } else {
            $resultText = '签到失败';
            $detailText = '签到任务不存在。';
        }
    } else {
        $resultText = '签到失败';
        $detailText = '微信静默授权失败。';
    }
} else {
    $resultText = '签到失败';
    $detailText = '请求参数无效。';
}
?>
<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>签到结果</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<div class="container success-page">
  <div class="card">
    <div class="success-icon <?= $type === 'success' ? '' : 'error' ?>"><?= $type === 'success' ? '✓' : '!' ?></div>
    <h1 class="success-title"><?= htmlspecialchars($resultText) ?></h1>
    <p class="success-desc"><?= htmlspecialchars($detailText) ?></p>
  </div>
</div>
</body></html>
