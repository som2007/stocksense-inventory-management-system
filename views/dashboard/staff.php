<section class="panel">
  <h2>Welcome, <?= e(explode(' ', $user['full_name'])[0]) ?></h2>
  <p class="text-muted mb-3">You are signed in as Warehouse Staff. You handle receiving, picking, packing, transfers and counts. Changes that move stock need the Inventory Manager's approval.</p>
  <ul class="stat-list">
    <li><small>Email</small><b><?= e($user['email']) ?></b></li>
    <li><small>Phone</small><b><?= e($user['phone']) ?></b></li>
    <li><small>Last login</small><b><?= $user['last_login'] ? e(date('d M Y, H:i', strtotime($user['last_login'] . ' UTC'))) : 'First login' ?></b></li>
  </ul>
</section>
<section class="panel">
  <div class="empty-state">
    <i class="bi bi-inbox"></i>
    <h2 class="mt-2">No tasks yet</h2>
    <p class="mb-0">Receipts, deliveries and transfers assigned to you will show up here once those modules are live.</p>
  </div>
</section>
