<?php
$__path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$page = $page ?? basename($__path === '' ? 'home' : $__path);
?><!doctype html>
<html lang="en">
<head><?php include __DIR__ . '/../partials/head.php'; ?></head>
<body data-page="<?= e($page) ?>">
<main class="auth-shell">
  <aside class="auth-brand">
    <a class="brand-mark" href="<?= e(base_url()) ?>" aria-label="StockSense home">
      <span class="logo"><i class="bi bi-boxes"></i></span><span class="name">StockSense</span>
    </a>
    <div>
      <h1>Every unit, accounted for.</h1>
      <p class="lead-copy">One place for receipts, deliveries, transfers and stock counts, so your team stops chasing registers and spreadsheets.</p>
    </div>
    <ul class="points">
      <li><i class="bi bi-check2-circle"></i>Live stock across every warehouse</li>
      <li><i class="bi bi-check2-circle"></i>Manager approvals on sensitive changes</li>
      <li><i class="bi bi-check2-circle"></i>Every movement written to the ledger</li>
    </ul>
  </aside>
  <section class="auth-main">
    <div class="auth-card<?= !empty($wide) ? ' wide' : '' ?>"><?= $content ?></div>
  </section>
</main>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
