<div class="row g-4">
  <div class="col-12 col-xl-7">
    <section class="panel">
      <h2>Personal details</h2>
      <p class="text-muted">Your email and role are fixed. Name and phone can be updated.</p>
      <form id="profileForm" novalidate>
        <div class="mb-3">
          <label class="form-label" for="full_name">Full name</label>
          <input class="form-control" id="full_name" name="full_name" value="<?= e($profile['full_name']) ?>" maxlength="60"
                 data-rules="required|name|min:2|max:60" data-label="Full name" data-filter="letters">
          <div class="invalid-feedback"></div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-12 col-md-7">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" value="<?= e($profile['email']) ?>" readonly>
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label" for="phone">Phone number</label>
            <input class="form-control" id="phone" name="phone" value="<?= e($profile['phone']) ?>" inputmode="numeric" maxlength="15"
                   data-rules="required|phone" data-label="Phone number" data-filter="digits">
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3">
          <button class="btn btn-gold" type="submit">Save changes</button>
          <span class="role-badge<?= $profile['role'] === 'inventory_manager' ? ' is-manager' : '' ?>"><?= e(role_label($profile['role'])) ?></span>
          <span class="text-muted small">Member since <?= e(date('d M Y', strtotime($profile['created_at'] . ' UTC'))) ?></span>
        </div>
      </form>
    </section>
  </div>
  <div class="col-12 col-xl-5">
    <section class="panel">
      <h2>Change password</h2>
      <p class="text-muted">Use a password you do not use anywhere else.</p>
      <form id="passwordForm" novalidate autocomplete="off">
        <div class="mb-3">
          <label class="form-label" for="current_password">Current password</label>
          <div class="input-group">
            <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" data-rules="required" data-label="Current password">
            <button class="btn pw-toggle" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button>
          </div>
          <div class="invalid-feedback"></div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="new_password">New password</label>
          <div class="input-group">
            <input class="form-control" id="new_password" name="password" type="password" autocomplete="new-password" data-rules="required|password" data-label="New password">
            <button class="btn pw-toggle" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button>
          </div>
          <div class="invalid-feedback"></div>
          <div class="hint">8+ characters with upper case, lower case, a digit and a symbol.</div>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password_confirmation">Confirm new password</label>
          <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" data-rules="required|same:password" data-label="Confirm password">
          <div class="invalid-feedback"></div>
        </div>
        <button class="btn btn-navy" type="submit">Update password</button>
      </form>
    </section>
  </div>
</div>
