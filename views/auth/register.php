<h2>Create your account</h2>
<p class="auth-sub">We will email a 6-digit code to confirm your address.</p>
<div id="formNotice" class="mb-3 d-none"></div>
<form id="registerForm" novalidate autocomplete="off">
  <div class="mb-3">
    <label class="form-label" for="full_name">Full name</label>
    <input class="form-control" id="full_name" name="full_name" type="text" maxlength="60" autocomplete="name"
           data-rules="required|name|min:2|max:60" data-label="Full name" data-filter="letters">
    <div class="invalid-feedback"></div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-7">
      <label class="form-label" for="email">Email</label>
      <input class="form-control" id="email" name="email" type="email" maxlength="120" autocomplete="email"
             data-rules="required|email" data-label="Email">
      <div class="invalid-feedback"></div>
    </div>
    <div class="col-12 col-md-5">
      <label class="form-label" for="phone">Phone number</label>
      <input class="form-control" id="phone" name="phone" type="tel" inputmode="numeric" maxlength="15" autocomplete="tel"
             data-rules="required|phone" data-label="Phone number" data-filter="digits">
      <div class="invalid-feedback"></div>
    </div>
  </div>
  <div class="mb-3">
    <span class="form-label d-block">Role</span>
    <div class="row g-2">
      <div class="col-12 col-sm-6">
        <input class="btn-check" type="radio" name="role" id="roleStaff" value="warehouse_staff" checked>
        <label class="btn btn-outline-navy w-100 text-start" for="roleStaff"><i class="bi bi-person-workspace me-2"></i>Warehouse Staff</label>
      </div>
      <div class="col-12 col-sm-6">
        <input class="btn-check" type="radio" name="role" id="roleManager" value="inventory_manager">
        <label class="btn btn-outline-navy w-100 text-start" for="roleManager"><i class="bi bi-shield-check me-2"></i>Inventory Manager</label>
      </div>
    </div>
  </div>
  <div class="mb-3 d-none" id="managerKeyWrap">
    <label class="form-label" for="manager_key">Manager key</label>
    <input class="form-control" id="manager_key" name="manager_key" type="password" autocomplete="off" data-rules="" data-label="Manager key">
    <div class="invalid-feedback"></div>
    <div class="hint">Given to you by the business owner. Needed only for the Manager role.</div>
  </div>
  <div class="mb-3">
    <label class="form-label" for="password">Password</label>
    <div class="input-group">
      <input class="form-control" id="password" name="password" type="password" autocomplete="new-password"
             data-rules="required|password" data-label="Password">
      <button class="btn pw-toggle" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button>
    </div>
    <div class="invalid-feedback"></div>
    <div class="hint">8+ characters with upper case, lower case, a digit and a symbol.</div>
  </div>
  <div class="mb-3">
    <label class="form-label" for="password_confirmation">Confirm password</label>
    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
           data-rules="required|same:password" data-label="Confirm password">
    <div class="invalid-feedback"></div>
  </div>
  <div class="mb-4">
    <label class="form-label" for="captcha">Type the characters you see</label>
    <?php if ($captchaOk): ?>
      <div class="captcha-row mb-2">
        <img id="captchaImg" class="captcha-img" src="<?= e(base_url('api/v1/auth/captcha')) ?>?t=<?= time() ?>" alt="Captcha image" width="168" height="54">
        <button class="btn btn-outline-navy captcha-refresh" type="button" id="captchaRefresh" aria-label="Get a new captcha"><i class="bi bi-arrow-repeat"></i></button>
      </div>
      <input class="form-control" id="captcha" name="captcha" type="text" maxlength="5" autocomplete="off" autocapitalize="characters"
             data-rules="required|min:5" data-label="Captcha">
      <div class="invalid-feedback"></div>
    <?php else: ?>
      <div class="notice is-error"><i class="bi bi-exclamation-octagon-fill"></i><span>Captcha needs the PHP GD extension. Enable <code>extension=gd</code> in php.ini and restart Apache.</span></div>
    <?php endif; ?>
  </div>
  <button class="btn btn-gold w-100" type="submit"<?= $captchaOk ? '' : ' disabled' ?>>Send OTP to my email</button>
</form>
<p class="auth-foot">Already registered? <a href="<?= e(base_url('login')) ?>">Login</a></p>
