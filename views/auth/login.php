<h2>Welcome back</h2>
<p class="auth-sub">Login to your inventory workspace.</p>
<?php if (!empty($notice)): ?>
  <div class="notice mb-3" role="status"><i class="bi bi-check-circle-fill"></i><span><?= e($notice) ?></span></div>
<?php endif; ?>
<div id="formNotice" class="mb-3 d-none"></div>
<form id="loginForm" novalidate autocomplete="on">
  <div class="mb-3">
    <label class="form-label" for="email">Email</label>
    <input class="form-control" id="email" name="email" type="email" autocomplete="email" placeholder="you@company.com"
           data-rules="required|email" data-label="Email">
    <div class="invalid-feedback"></div>
  </div>
  <div class="mb-2">
    <label class="form-label" for="password">Password</label>
    <div class="input-group">
      <input class="form-control" id="password" name="password" type="password" autocomplete="current-password"
             data-rules="required" data-label="Password">
      <button class="btn pw-toggle" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button>
    </div>
    <div class="invalid-feedback"></div>
  </div>
  <div class="text-end mb-3"><a href="<?= e(base_url('forgot-password')) ?>" class="small fw-semibold">Forgot password?</a></div>
  <button class="btn btn-gold w-100" type="submit">Login</button>
</form>
<p class="auth-foot">New to StockSense? <a href="<?= e(base_url('register')) ?>">Create an account</a></p>
