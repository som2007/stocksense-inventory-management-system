<h2>Forgot your password?</h2>
<p class="auth-sub">Enter your registered email and we will send a reset link. It works once and stays valid for 8 hours.</p>
<div id="formNotice" class="mb-3 d-none"></div>
<form id="forgotForm" novalidate>
  <div class="mb-4">
    <label class="form-label" for="email">Registered email</label>
    <input class="form-control" id="email" name="email" type="email" autocomplete="email" placeholder="you@company.com"
           data-rules="required|email" data-label="Email">
    <div class="invalid-feedback"></div>
  </div>
  <button class="btn btn-gold w-100" type="submit">Send reset link</button>
</form>
<p class="auth-foot"><a href="<?= e(base_url('login')) ?>">Back to login</a></p>
