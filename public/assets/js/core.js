/* StockSense core: APP config, screen-lock Loader, Notify (toasts), Flash, Api (fetch wrapper) */
(function (w, $) {
  'use strict';

  const meta = (n) => (document.querySelector('meta[name="' + n + '"]') || {}).content || '';
  const APP = { base: meta('app-base').replace(/\/$/, ''), csrf: meta('csrf-token') };
  APP.url = (p) => APP.base + '/' + String(p || '').replace(/^\//, '');
  w.APP = APP;

  const MSG_OFFLINE = "You haven't internet connection.";
  const MSG_UNREACHABLE = "Can't reach the server. Please check that it is running and try again.";

  /* ---------------- Loader: locks the whole screen until released ---------------- */
  const Loader = {
    count: 0,
    el: null,
    get active() { return this.count > 0; },
    _ensure() {
      if (this.el) return;
      this.el = document.createElement('div');
      this.el.className = 'ims-lock';
      this.el.setAttribute('role', 'alertdialog');
      this.el.setAttribute('aria-live', 'assertive');
      this.el.setAttribute('aria-label', 'Please wait');
      this.el.tabIndex = -1;
      this.el.innerHTML = '<div class="ims-lock__box"><div class="ims-spinner" aria-hidden="true"></div><p class="ims-lock__msg"></p></div>';
      document.body.appendChild(this.el);
      // keep keyboard inside the lock while it is on
      document.addEventListener('keydown', (e) => {
        if (Loader.active && (e.key === 'Tab' || e.key === 'Enter')) { e.preventDefault(); Loader.el.focus(); }
      }, true);
    },
    show(message) {
      this._ensure();
      this.count++;
      this.el.querySelector('.ims-lock__msg').textContent = message || 'Please wait...';
      this.el.classList.add('is-on');
      document.body.setAttribute('aria-busy', 'true');
      document.body.style.overflow = 'hidden';
      this.el.focus();
    },
    hide() {
      if (this.count > 0) this.count--;
      if (this.count === 0 && this.el) {
        this.el.classList.remove('is-on');
        document.body.removeAttribute('aria-busy');
        document.body.style.overflow = '';
      }
    }
  };
  w.Loader = Loader;

  /* ---------------- Notify: toasts ---------------- */
  const ICONS = { success: 'bi-check-circle-fill', error: 'bi-x-octagon-fill', info: 'bi-info-circle-fill' };
  const Notify = {
    _host() {
      let h = document.querySelector('.ims-toasts');
      if (!h) { h = document.createElement('div'); h.className = 'ims-toasts'; h.setAttribute('aria-live', 'polite'); document.body.appendChild(h); }
      return h;
    },
    show(type, message, ms) {
      const t = document.createElement('div');
      t.className = 'ims-toast is-' + type;
      t.setAttribute('role', type === 'error' ? 'alert' : 'status');
      const i = document.createElement('i'); i.className = 'bi ' + (ICONS[type] || ICONS.info);
      const s = document.createElement('span'); s.textContent = message;
      t.append(i, s);
      this._host().appendChild(t);
      const life = ms || (type === 'error' ? 7000 : 4500);
      const kill = () => t.remove();
      t.addEventListener('click', kill);
      setTimeout(kill, life);
    },
    success(m, ms) { this.show('success', m, ms); },
    error(m, ms) { this.show('error', m, ms); },
    info(m, ms) { this.show('info', m, ms); }
  };
  w.Notify = Notify;

  /* ---------------- Flash: show a toast on the NEXT page ---------------- */
  const Flash = {
    set(type, message) { try { sessionStorage.setItem('ims_flash', JSON.stringify({ type, message })); } catch (e) { /* ignore */ } },
    flush() {
      try {
        const raw = sessionStorage.getItem('ims_flash');
        if (!raw) return;
        sessionStorage.removeItem('ims_flash');
        const f = JSON.parse(raw);
        if (f && f.message) Notify.show(f.type || 'info', f.message);
      } catch (e) { /* ignore */ }
    }
  };
  w.Flash = Flash;

  /* ---------------- Api: every call can lock the screen ---------------- */
  class ApiError extends Error {
    constructor(message, status, body, network) {
      super(message);
      this.status = status || 0;
      this.body = body || {};
      this.code = (body && body.code) || null;
      this.errors = (body && body.errors) || {};
      this.data = (body && body.data) || null;
      this.network = !!network;
    }
  }
  w.ApiError = ApiError;

  const Api = {
    /**
     * @param {string} method
     * @param {string} path  relative to app base, e.g. "api/v1/auth/login"
     * @param {object} [opts] { data, lock = true, lockMessage }
     */
    async request(method, path, opts) {
      opts = opts || {};
      const lock = opts.lock !== false;
      if (lock) Loader.show(opts.lockMessage || 'Please wait...');
      try {
        if (navigator.onLine === false) throw new ApiError(MSG_OFFLINE, 0, { code: 'NO_INTERNET' }, true);
        let res;
        try {
          res = await fetch(APP.url(path), {
            method,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': APP.csrf },
            body: opts.data !== undefined && method !== 'GET' ? JSON.stringify(opts.data) : undefined
          });
        } catch (netErr) {
          throw new ApiError(navigator.onLine === false ? MSG_OFFLINE : MSG_UNREACHABLE, 0,
            { code: navigator.onLine === false ? 'NO_INTERNET' : 'NETWORK' }, true);
        }
        let body;
        try { body = await res.json(); } catch (e) { body = { success: false, message: 'Unexpected response from the server.' }; }
        if (res.status === 419 && body && body.code === 'CSRF_MISMATCH') {
          throw new ApiError('Your page was open for too long. Please refresh and try again.', 419, body);
        }
        if (!res.ok || body.success === false) throw new ApiError(body.message || 'Request failed.', res.status, body);
        return body;
      } finally {
        if (lock) Loader.hide();
      }
    },
    get(path, opts) { return this.request('GET', path, opts); },
    post(path, data, opts) { return this.request('POST', path, Object.assign({}, opts, { data: data || {} })); },
    put(path, data, opts) { return this.request('PUT', path, Object.assign({}, opts, { data: data || {} })); },
    del(path, opts) { return this.request('DELETE', path, opts); }
  };
  w.Api = Api;

  /* Connectivity hints */
  w.addEventListener('offline', () => Notify.error(MSG_OFFLINE));
  w.addEventListener('online', () => Notify.info('Back online.'));

  $(function () { Flash.flush(); });
})(window, jQuery);
