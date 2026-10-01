<?php
$__path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$page = $page ?? basename($__path === '' ? 'home' : $__path);
$me = auth_user();
$nav = $nav ?? (require dirname(__DIR__, 2) . '/config/navigation.php');
$active = $active ?? '';
?><!doctype html>
<html lang="en">
<head><?php include __DIR__ . '/../partials/head.php'; ?></head>
<body data-page="<?= e($page) ?>">
<div class="app-shell">
  <aside class="ims-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Main navigation">
    <div class="offcanvas-header">
      <a class="brand-mark" href="<?= e(base_url('dashboard')) ?>">
        <span class="logo"><i class="bi bi-boxes"></i></span><span class="name">StockSense</span>
      </a>
      <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close menu"></button>
    </div>
    <div class="offcanvas-body">
      <nav>
        <?php foreach ($nav as $item): ?>
          <?php if (isset($item['group'])): ?>
            <div class="nav-group"><?= e($item['group']) ?></div>
            <?php continue; ?>
          <?php endif; ?>
          <?php if (empty($item['built']) || !in_array($me['role'] ?? '', $item['roles'], true)) continue; ?>
          <a class="side-link<?= $active === $item['key'] ? ' active' : '' ?>" href="<?= e(base_url($item['href'])) ?>"<?= $active === $item['key'] ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="side-bottom">
        <div class="side-user">
          <span class="avatar js-user-initials"><?= e(initials($me['full_name'] ?? '')) ?></span>
          <div class="who"><b class="js-user-name"><?= e($me['full_name'] ?? '') ?></b><span><?= e(role_label($me['role'] ?? '')) ?></span></div>
        </div>
        <a class="side-link<?= $active === 'profile' ? ' active' : '' ?>" href="<?= e(base_url('profile')) ?>"><i class="bi bi-person-circle"></i><span>My Profile</span></a>
        <a class="side-link js-logout" href="#"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
      </div>
    </div>
  </aside>

  <div class="app-main">
    <header class="topbar">
      <button class="btn btn-outline-navy d-lg-none px-2 py-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-4"></i></button>
      <h1><?= e($title ?? '') ?></h1>
      <span class="role-badge<?= ($me['role'] ?? '') === 'inventory_manager' ? ' is-manager' : '' ?> d-none d-sm-inline-block"><?= e(role_label($me['role'] ?? '')) ?></span>
    </header>
    <main class="app-content"><?= $content ?></main>
  </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
