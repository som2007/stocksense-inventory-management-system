/* Client-side validator. Same rule names + messages as src/Core/Validator.php.
   Usage: <input data-rules="required|name|min:2|max:60" data-label="Full name" data-filter="letters">
          <div class="invalid-feedback"></div> right after the input (or after its .input-group). */
(function (w, $) {
  'use strict';

  const NAME_RE = /^[\p{L}\p{M}]+(?: [\p{L}\p{M}]+)*$/u;
  const PHONE_RE = /^\d{10,15}$/;
  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  const rules = {
    name: (v, l) => NAME_RE.test(v) ? null : l + ' can contain letters and spaces only.',
    email: (v) => (EMAIL_RE.test(v) && v.length <= 120) ? null : 'Enter a valid email address.',
    phone: (v, l) => PHONE_RE.test(v) ? null : l + ' must contain digits only (10 to 15 digits).',
    digits: (v, l) => /^\d+$/.test(v) ? null : l + ' can contain digits only.',
    letters: (v, l) => /^[\p{L}\p{M} ]+$/u.test(v) ? null : l + ' can contain letters only.',
    password: (v) => (v.length >= 8 && /[a-z]/.test(v) && /[A-Z]/.test(v) && /\d/.test(v) && /[^A-Za-z0-9]/.test(v))
      ? null : 'Password needs 8+ characters with upper case, lower case, a digit and a symbol.',
    min: (v, l, a) => v.length >= +a ? null : l + ' must be at least ' + a + ' characters.',
    max: (v, l, a) => v.length <= +a ? null : l + ' must be at most ' + a + ' characters.',
    same: (v, l, a, $form) => {
      const other = $form.find('[name="' + a + '"]').val() || '';
      return other === v ? null : l + ' does not match.';
    },
    in: (v, l, a) => a.split(',').includes(v) ? null : l + ' is invalid.',
    alnum_code: (v, l) => /^[A-Za-z0-9_-]+$/.test(v) ? null : l + ' can contain letters, digits, - and _ only.'
  };

  const filters = {
    digits: (v) => v.replace(/\D+/g, ''),
    letters: (v) => v.replace(/[^\p{L}\p{M} ]+/gu, '').replace(/ {2,}/g, ' ')
  };

  function feedbackEl($el) {
    const $group = $el.closest('.input-group');
    const $anchor = $group.length ? $group : $el;
    let $fb = $anchor.nextAll('.invalid-feedback').first();
    if (!$fb.length) $fb = $el.siblings('.invalid-feedback').first();
    if (!$fb.length) { $fb = $('<div class="invalid-feedback"></div>').insertAfter($anchor); }
    return $fb;
  }

  const V = {
    setError($el, msg) {
      $el.addClass('is-invalid').removeClass('is-valid').attr('aria-invalid', 'true');
      feedbackEl($el).text(msg).addClass('show');
    },
    clearError($el) {
      $el.removeClass('is-invalid').removeAttr('aria-invalid');
      feedbackEl($el).text('').removeClass('show');
    },
    /** Validate one field. Returns error message or null (and paints the UI). */
    field($el) {
      const spec = ($el.data('rules') || '').toString();
      if (!spec || $el.is(':disabled') || $el.closest('.d-none').length) { V.clearError($el); return null; }
      const label = $el.data('label') || 'This field';
      const val = ($el.attr('type') === 'password' ? String($el.val() || '') : String($el.val() || '').trim());
      const list = spec.split('|');
      const $form = $el.closest('form');
      let err = null;
      if (val === '') {
        if (list.includes('required')) err = label + ' is required.';
      } else {
        for (const r of list) {
          if (r === 'required') continue;
          const [name, arg] = r.split(/:(.+)/);
          if (rules[name]) { err = rules[name](val, label, arg, $form); if (err) break; }
        }
      }
      if (err) V.setError($el, err); else V.clearError($el);
      return err;
    },
    /** Validate whole form; focuses first invalid field. */
    form($form) {
      let first = null;
      $form.find('[data-rules]').each(function () {
        const e = V.field($(this));
        if (e && !first) first = this;
      });
      if (first) first.focus();
      return !first;
    },
    /** Paint {field: message} coming from the server. */
    server($form, errors) {
      let first = null;
      Object.keys(errors || {}).forEach((k) => {
        const $el = $form.find('[name="' + k + '"]').first();
        if ($el.length) { V.setError($el, errors[k]); if (!first) first = $el[0]; }
      });
      if (first) first.focus();
    },
    /** Wire live behaviour for every [data-rules] input inside $root. */
    bind($root) {
      $root.on('input', '[data-filter]', function () {
        const f = filters[$(this).data('filter')];
        if (f) { const cur = this.value, nxt = f(cur); if (cur !== nxt) this.value = nxt; }
      });
      $root.on('blur', '[data-rules]', function () { $(this).data('touched', true); V.field($(this)); });
      $root.on('input change', '[data-rules]', function () {
        if ($(this).data('touched') || $(this).hasClass('is-invalid')) V.field($(this));
        // keep the confirm field in sync when its source changes
        const name = this.name;
        $root.find('[data-rules*="same:' + name + '"]').each(function () { if ($(this).val()) V.field($(this)); });
      });
    }
  };
  w.V = V;

  // password show/hide toggles
  $(document).on('click', '.pw-toggle', function () {
    const $in = $(this).closest('.input-group').find('input');
    const show = $in.attr('type') === 'password';
    $in.attr('type', show ? 'text' : 'password');
    $(this).find('i').attr('class', show ? 'bi bi-eye-slash' : 'bi bi-eye');
    $(this).attr('aria-label', show ? 'Hide password' : 'Show password');
  });
})(window, jQuery);
