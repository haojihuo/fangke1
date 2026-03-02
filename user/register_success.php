<?php
$name = trim($_GET['name'] ?? '访客');
?>
<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>预约成功</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<div class="container success-page">
  <div class="card">
    <div class="success-icon">✓</div>
    <h1 class="success-title">预约成功！</h1>
    <p class="success-desc">欢迎您，<?= htmlspecialchars($name) ?>。<br>请在签到时间内使用签到二维码完成签到。</p>
  </div>
</div>
</body></html>
