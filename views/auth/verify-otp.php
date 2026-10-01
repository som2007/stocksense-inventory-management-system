<h2>Verify your email</h2>
<p class="auth-sub">Enter the 6-digit code we sent to <b id="maskedEmail">your email</b>.</p>
<div id="formNotice" class="mb-3 d-none"></div>
<form id="otpForm" novalidate autocomplete="off">
  <div class="otp-boxes" role="group" aria-label="6 digit OTP">
    <?php for ($i = 1; $i <= 6; $i++): ?>
      <input class="form-control otp-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="Digit <?= $i ?>" autocomplete="<?= $i === 1 ? 'one-time-code' : 'off' ?>" disabled>
    <?php endfor; ?>
  </div>
  <div class="otp-timer" id="otpTimer" aria-live="polite">
    <div class="label" id="otpLabel">Loading...</div>
    <div class="time" id="otpTime">--:--</div>
    <div class="otp-bar"><span id="otpBar"></span></div>
  </div>
  <button class="btn btn-gold w-100 mb-2" type="submit" id="verifyBtn" disabled>Verify and continue</button>
  <button class="btn btn-outline-navy w-100" type="button" id="resendBtn" disabled>Resend OTP</button>
  <div class="hint text-center mt-2">Resend unlocks when the timer reaches 00:00.</div>
</form>
<p class="auth-foot"><a href="<?= e(base_url('login')) ?>">Back to login</a></p>
