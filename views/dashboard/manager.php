<?php
$modules = [
    ['M0', 'Foundation (router, validation, loader, mail, PDF engine)', true],
    ['M1', 'Authentication and role access', true],
    ['M2', 'Warehouses, locations and service-area map', false],
    ['M3', 'Products, categories and reorder rules', false],
    ['M4', 'Approvals and Receipts', false],
    ['M5', 'Delivery Orders and order tracking', false],
    ['M6', 'Internal Transfers', false],
    ['M7', 'Stock Adjustments', false],
    ['M8', 'Move History (ledger)', false],
    ['M9', 'Live dashboards and KPIs', false],
    ['M10', 'Alerts, staff management and settings', false],
    ['M11', 'Testing and hardening', false],
];
?>
<section class="panel">
  <h2>Welcome, <?= e(explode(' ', $user['full_name'])[0]) ?></h2>
  <p class="text-muted mb-3">You are signed in as Inventory Manager, with full authority over products, warehouses, approvals and staff.</p>
  <ul class="stat-list">
    <li><small>Email</small><b><?= e($user['email']) ?></b></li>
    <li><small>Phone</small><b><?= e($user['phone']) ?></b></li>
    <li><small>Last login</small><b><?= $user['last_login'] ? e(date('d M Y, H:i', strtotime($user['last_login'] . ' UTC'))) : 'First login' ?></b></li>
  </ul>
</section>
<section class="panel">
  <h2>Build progress</h2>
  <p class="text-muted">Live KPIs arrive with the Dashboard module (M9). Until then this card tracks what is ready.</p>
  <?php foreach ($modules as [$code, $label, $done]): ?>
    <div class="mod-row"><span><b><?= e($code) ?></b>&nbsp; <?= e($label) ?></span><span class="mod-state<?= $done ? ' done' : '' ?>"><?= $done ? 'Ready' : 'Pending' ?></span></div>
  <?php endforeach; ?>
</section>
