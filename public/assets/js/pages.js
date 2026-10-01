/* Page controllers (auth pages, profile, app shell). Dispatch on <body data-page="..."> */
(function (w, $) {
  'use strict';

  const page = document.body.dataset.page || '';

  function inlineNotice($box, type, msg) {
    $box.removeClass('d-none').empty();
    const $n = $('<div class="notice" role="alert"></div>').toggleClass('is-error', type === 'error');
    $n.append($('<i class="bi"></i>').addClass(type === 'error' ? 'bi-x-octagon-fill' : 'bi-check-circle-fill'), $('<span></span>').text(msg));
    $box.append($n);
  }
  function clearNotice($box) { $box.addClass('d-none').empty(); }

  /** Shared failure handling: toast + inline notice + field errors. */
  function fail(err, $form, $box) {
    if (err.errors && Object.keys(err.errors).length) V.server($form, err.errors);
    Notify.error(err.message);
    if ($box && $box.length) inlineNotice($box, 'error', err.message);
  }

  /* ============================ LOGIN ============================ */
  function initLogin() {
    const $f = $('#loginForm'), $box = $('#formNotice');
    V.bind($f);
    $f.on('submit', async function (e) {
      e.preventDefault();
      if (Loader.active) return;
      clearNotice($box);
      if (!V.form($f)) return;
      try {
        const res = await Api.post('api/v1/auth/login', { email: $f.find('[name=email]').val().trim(), password: $f.find('[name=password]').val() },
          { lockMessage: 'Signing you in...' });
        Flash.set('success', res.message);
        location.href = res.data.redirect;
      } catch (err) {
        if (err.code === 'NOT_VERIFIED' && err.data && err.data.redirect) {
          Flash.set('info', err.message);
          location.href = err.data.redirect;
          return;
        }
        fail(err, $f, $box);
      }
    });
  }

  /* =========================== REGISTER =========================== */
  function initRegister() {
    const $f = $('#registerForm'), $box = $('#formNotice');
    V.bind($f);

    const refreshCaptcha = () => {
      $('#captchaImg').attr('src', APP.url('api/v1/auth/captcha') + '?t=' + Date.now());
      $f.find('[name=captcha]').val('');
    };
    $('#captchaRefresh').on('click', refreshCaptcha);

    const toggleManager = () => {
      const isMgr = $f.find('[name=role]:checked').val() === 'inventory_manager';
      $('#managerKeyWrap').toggleClass('d-none', !isMgr);
      const $k = $f.find('[name=manager_key]');
      $k.attr('data-rules', isMgr ? 'required' : '');
      $k.data('rules', isMgr ? 'required' : '');
      if (!isMgr) { $k.val(''); V.clearError($k); }
    };
    $f.on('change', '[name=role]', toggleManager);
    toggleManager();

    $f.on('submit', async function (e) {
      e.preventDefault();
      if (Loader.active) return;
      clearNotice($box);
      if (!V.form($f)) return;
      const data = {};
      $f.serializeArray().forEach((p) => { data[p.name] = p.value; });
      try {
        const res = await Api.post('api/v1/auth/register', data, { lockMessage: 'Checking details and sending OTP to your email...' });
        Flash.set('success', res.message);
        location.href = res.data.redirect;
      } catch (err) {
        fail(err, $f, $box);
        refreshCaptcha(); // every captcha is single-use
      }
    });
  }

  /* ============================ OTP PAGE ============================ */
  function initOtp() {
    const $boxes = $('.otp-box'), $verify = $('#verifyBtn'), $resend = $('#resendBtn'), $notice = $('#formNotice');
    const $time = $('#otpTime'), $label = $('#otpLabel'), $bar = $('#otpBar'), $timer = $('#otpTimer');
    let ttl = 120, endAt = 0, tick = null, locked = false;

    const getOtp = () => $boxes.map(function () { return this.value; }).get().join('');
    const fmt = (s) => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');

    function setExpired(msg) {
      clearInterval(tick); tick = null;
      $timer.addClass('is-expired'); $time.text('00:00'); $bar.css('width', '0%');
      $label.text(msg || 'OTP expired. Request a new one.');
      $boxes.prop('disabled', true).val('');
      $verify.prop('disabled', true);
      $resend.prop('disabled', false);
    }
    function startTimer(seconds) {
      clearInterval(tick);
      if (seconds <= 0) { setExpired('No active OTP. Request a new one.'); return; }
      $timer.removeClass('is-expired');
      $boxes.prop('disabled', false).val('');
      $resend.prop('disabled', true);
      $verify.prop('disabled', false);
      $label.text('Code expires in');
      endAt = performance.now() + seconds * 1000;
      const draw = () => {
        const left = Math.max(0, Math.ceil((endAt - performance.now()) / 1000));
        $time.text(fmt(left));
        $bar.css('width', Math.min(100, (left / ttl) * 100) + '%');
        if (left <= 0) setExpired();
      };
      draw();
      tick = setInterval(draw, 250);
      $boxes.first().trigger('focus');
    }

    // six-box input: digits only, auto-advance, backspace, paste
    $boxes.on('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(-1);
      if (this.value) $boxes.eq($boxes.index(this) + 1).trigger('focus');
    }).on('keydown', function (e) {
      const i = $boxes.index(this);
      if (e.key === 'Backspace' && !this.value && i > 0) { $boxes.eq(i - 1).val('').trigger('focus'); }
      if (e.key === 'ArrowLeft' && i > 0) $boxes.eq(i - 1).trigger('focus');
      if (e.key === 'ArrowRight' && i < 5) $boxes.eq(i + 1).trigger('focus');
    }).on('paste', function (e) {
      const t = ((e.originalEvent.clipboardData || w.clipboardData).getData('text') || '').replace(/\D/g, '').slice(0, 6);
      if (!t) return;
      e.preventDefault();
      t.split('').forEach((c, i) => $boxes.eq(i).val(c));
      $boxes.eq(Math.min(t.length, 5)).trigger('focus');
    });

    $('#otpForm').on('submit', async function (e) {
      e.preventDefault();
      if (Loader.active || $verify.prop('disabled')) return;
      clearNotice($notice);
      const otp = getOtp();
      if (!/^\d{6}$/.test(otp)) {
        const m = 'Enter the 6-digit OTP.';
        inlineNotice($notice, 'error', m); Notify.error(m); return;
      }
      try {
        const res = await Api.post('api/v1/auth/otp/verify', { otp }, { lockMessage: 'Verifying OTP...' });
        Flash.set('success', res.message);
        location.href = res.data.redirect;
      } catch (err) {
        inlineNotice($notice, 'error', err.message); Notify.error(err.message);
        if (err.code === 'OTP_EXPIRED') setExpired('OTP expired. Request a new one.');
        else if (err.code === 'NO_PENDING' && err.data && err.data.redirect) location.href = err.data.redirect;
        else { $boxes.val('').first().trigger('focus'); }
      }
    });

    $resend.on('click', async function () {
      if (Loader.active || $resend.prop('disabled')) return;
      clearNotice($notice);
      try {
        const res = await Api.post('api/v1/auth/otp/resend', {}, { lockMessage: 'Sending OTP to your email...' });
        Notify.success(res.message);
        inlineNotice($notice, 'ok', res.message);
        ttl = res.data.expires_in; startTimer(ttl);
      } catch (err) {
        inlineNotice($notice, 'error', err.message); Notify.error(err.message);
        if (err.code === 'OTP_STILL_VALID' && err.data) startTimer(err.data.expires_in);
      }
    });

    // initial state (DB-backed, so a refresh never resets the timer)
    (async function boot() {
      try {
        const res = await Api.get('api/v1/auth/otp/status', { lockMessage: 'Loading...' });
        ttl = res.data.ttl; $('#maskedEmail').text(res.data.email_masked);
        startTimer(res.data.expires_in);
        if (res.data.expires_in <= 0) { $label.text('Your last OTP expired. Request a new one.'); }
      } catch (err) {
        if (err.data && err.data.redirect) { location.href = err.data.redirect; return; }
        Notify.error(err.message); inlineNotice($notice, 'error', err.message);
      }
    })();
  }

  /* ======================== FORGOT PASSWORD ======================== */
  function initForgot() {
    const $f = $('#forgotForm'), $box = $('#formNotice');
    V.bind($f);
    $f.on('submit', async function (e) {
      e.preventDefault();
      if (Loader.active) return;
      clearNotice($box);
      if (!V.form($f)) return;
      try {
        const res = await Api.post('api/v1/auth/forgot-password', { email: $f.find('[name=email]').val().trim() },
          { lockMessage: 'Sending reset link to your email...' });
        Notify.success(res.message);
        inlineNotice($box, 'ok', res.message + ' It stays valid for ' + res.data.hours_valid + ' hours and works only once.');
        $f.find('button[type=submit]').text('Send again');
      } catch (err) { fail(err, $f, $box); }
    });
  }

  /* ========================= RESET PASSWORD ========================= */
  function initReset() {
    const $f = $('#resetForm'), $box = $('#formNotice');
    if (!$f.length) return;
    V.bind($f);
    $f.on('submit', async function (e) {
      e.preventDefault();
      if (Loader.active) return;
      clearNotice($box);
      if (!V.form($f)) return;
      try {
        const res = await Api.post('api/v1/auth/reset-password', {
          token: $f.find('[name=token]').val(), password: $f.find('[name=password]').val(),
          password_confirmation: $f.find('[name=password_confirmation]').val()
        }, { lockMessage: 'Updating your password...' });
        Flash.set('success', res.message);
        location.href = res.data.redirect;
      } catch (err) { fail(err, $f, $box); }
    });
  }

  /* ============================= PROFILE ============================= */
  function initProfile() {
    const $f = $('#profileForm'), $pw = $('#passwordForm');
    V.bind($f); V.bind($pw);
    $f.on('submit', async function (e) {
      e.preventDefault();
      if (Loader.active || !V.form($f)) return;
      try {
        const res = await Api.put('api/v1/profile', { full_name: $f.find('[name=full_name]').val().trim(), phone: $f.find('[name=phone]').val().trim() },
          { lockMessage: 'Saving your profile...' });
        Notify.success(res.message);
        $('.js-user-name').text(res.data.full_name);
        $('.js-user-initials').text(res.data.full_name.split(/\s+/).slice(0, 2).map((x) => x[0].toUpperCase()).join(''));
      } catch (err) { if (err.errors) V.server($f, err.errors); Notify.error(err.message); }
    });
    $pw.on('submit', async function (e) {
      e.preventDefault();
      if (Loader.active || !V.form($pw)) return;
      try {
        const res = await Api.post('api/v1/profile/password', {
          current_password: $pw.find('[name=current_password]').val(), password: $pw.find('[name=password]').val(),
          password_confirmation: $pw.find('[name=password_confirmation]').val()
        }, { lockMessage: 'Changing your password...' });
        Notify.success(res.message);
        $pw[0].reset();
      } catch (err) { if (err.errors) V.server($pw, err.errors); Notify.error(err.message); }
    });
  }

  /* ============================ APP SHELL ============================ */
  function initShell() {
    $(document).on('click', '.js-logout', async function (e) {
      e.preventDefault();
      if (Loader.active) return;
      try {
        const res = await Api.post('api/v1/auth/logout', {}, { lockMessage: 'Logging you out...' });
        Flash.set('info', res.message);
        location.href = res.data.redirect;
      } catch (err) { Notify.error(err.message); }
    });
  }

  $(function () {
    if ($('.app-shell').length) initShell();
    ({ login: initLogin, register: initRegister, 'verify-otp': initOtp, 'forgot-password': initForgot,
       'reset-password': initReset, profile: initProfile }[page] || function () {})();
  });
})(window, jQuery);
