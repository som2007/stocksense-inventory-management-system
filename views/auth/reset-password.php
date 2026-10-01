<?php if (!$valid): ?>
  <h2>Link not valid</h2>
  <p class="auth-sub">This reset link is invalid, already used or has expired (links last 8 hours and work once).</p>
  <a class="btn btn-gold w-100" href="<?= e(base_url('forgot-password')) ?>">Request a new link</a>
<?php else: ?>
  <h2>Choose a new password</h2>
  <p class="auth-sub">Pick something you have not used here before.</p>
  <div id="formNotice" class="mb-3 d-none"></div>
  <form id="resetForm" novalidate autocomplete="off">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="mb-3">
      <label class="form-label" for="password">New password</label>
      <div class="input-group">
        <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" data-rules="required|password" data-label="Password">
        <button class="btn pw-toggle" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button>
      </div>
      <div class="invalid-feedback"></div>
      <div class="hint">8+ characters with upper case, lower case, a digit and a symbol.</div>
    </div>
    <div class="mb-4">
      <label class="form-label" for="password_confirmation">Confirm password</label>
      <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" data-rules="required|same:password" data-label="Confirm password">
      <div class="invalid-feedback"></div>
    </div>
    <button class="btn btn-gold w-100" type="submit">Update password</button>
  </form>
<?php endif; ?>
<p class="auth-foot"><a href="<?= e(base_url('login')) ?>">Back to login</a></p>
