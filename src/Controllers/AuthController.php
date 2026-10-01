<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Controllers;

use Somen\InventoryManagementSystem\Core\Controller;
use Somen\InventoryManagementSystem\Core\Env;
use Somen\InventoryManagementSystem\Core\Request;
use Somen\InventoryManagementSystem\Core\RateLimiter;
use Somen\InventoryManagementSystem\Core\Response;
use Somen\InventoryManagementSystem\Core\Session;
use Somen\InventoryManagementSystem\Core\Validator;
use Somen\InventoryManagementSystem\Models\User;
use Somen\InventoryManagementSystem\Services\CaptchaService;
use Somen\InventoryManagementSystem\Services\MailService;
use Somen\InventoryManagementSystem\Services\OtpService;
use Somen\InventoryManagementSystem\Services\PasswordResetService;

/**
 * REST endpoints under /api/v1/auth/*
 */
final class AuthController extends Controller
{
    private const LABELS = [
        'full_name' => 'Full name', 'email' => 'Email', 'phone' => 'Phone number',
        'password' => 'Password', 'password_confirmation' => 'Confirm password',
        'role' => 'Role', 'captcha' => 'Captcha', 'otp' => 'OTP', 'manager_key' => 'Manager key',
    ];

    // ------------------------------------------------------------ captcha
    public function captcha(Request $r): Response
    {
        if (!CaptchaService::available()) {
            return Response::error('Captcha needs the PHP GD extension. Enable extension=gd in php.ini.', 503, [], 'CAPTCHA_UNAVAILABLE');
        }
        return Response::raw(CaptchaService::image(), 'image/png', 200, ['Cache-Control' => 'no-store, no-cache, must-revalidate']);
    }

    // ------------------------------------------------ live duplicate check
    public function availability(Request $r): Response
    {
        $ip = $r->ip();
        if (RateLimiter::tooMany('avail', $ip, 40, 60)) {
            return Response::error('Too many checks. Please slow down.', 429, [], 'RATE_LIMITED');
        }
        RateLimiter::hit('avail', $ip);

        $field = $r->string('field');
        $value = $r->string('value');
        if (!in_array($field, ['email', 'phone'], true)) {
            return Response::error('Unknown field.', 422, [], 'VALIDATION_FAILED');
        }
        $v = Validator::make([$field => $value], [$field => ['required', $field]], self::LABELS);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }
        $taken = $field === 'email' ? User::emailTaken($value) : User::phoneTaken($value);
        return Response::success($taken ? self::LABELS[$field] . ' is already registered.' : 'Available', ['available' => !$taken]);
    }

    // ------------------------------------------------------------ register
    public function register(Request $r): Response
    {
        $ip = $r->ip();
        if (random_int(1, 50) === 1) {
            RateLimiter::purge(); // housekeeping
        }
        if (RateLimiter::tooMany('register', $ip, 15, 3600)) {
            return Response::error('Too many sign-up attempts. Please try again after some time.', 429, [], 'RATE_LIMITED');
        }

        $input = [
            'full_name' => $r->string('full_name'), 'email' => mb_strtolower($r->string('email')),
            'phone' => $r->string('phone'), 'password' => (string)$r->input('password', ''),
            'password_confirmation' => (string)$r->input('password_confirmation', ''),
            'role' => $r->string('role'), 'captcha' => $r->string('captcha'),
            'manager_key' => $r->string('manager_key'),
        ];
        $rules = [
            'full_name' => ['required', 'name', 'min:2', 'max:60'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'phone'],
            'password' => ['required', 'password'],
            'password_confirmation' => ['required', 'same:password'],
            'role' => ['required', 'in:inventory_manager,warehouse_staff'],
            'captcha' => ['required'],
        ];
        if ($input['role'] === 'inventory_manager') {
            $rules['manager_key'] = ['required'];
        }
        $v = Validator::make($input, $rules, self::LABELS);
        if ($v->fails()) {
            RateLimiter::hit('register', $ip);
            return $this->validationError($v->errors());
        }

        // captcha (one attempt per image)
        if (!CaptchaService::check($input['captcha'])) {
            RateLimiter::hit('register', $ip);
            return $this->validationError(['captcha' => 'Captcha is incorrect or expired. Try the new image.'], 'Captcha verification failed.');
        }

        if ($input['role'] === 'inventory_manager') {
            $expected = (string)Env::get('MANAGER_REGISTRATION_KEY', '');
            if ($expected === '' || !hash_equals($expected, $input['manager_key'])) {
                RateLimiter::hit('register', $ip);
                return $this->validationError(['manager_key' => 'Manager key is incorrect.'], 'Manager key verification failed.');
            }
        }

        // duplicates BEFORE any OTP is sent
        User::purgeStaleUnverified();
        $dups = [];
        if (User::emailTaken($input['email'])) {
            $dups['email'] = 'This email is already registered. Try logging in.';
        }
        if (User::phoneTaken($input['phone'])) {
            $dups['phone'] = 'This phone number is already registered.';
        }
        if ($dups) {
            RateLimiter::hit('register', $ip);
            return Response::error('Account already exists. No OTP was sent.', 409, $dups, 'DUPLICATE');
        }

        RateLimiter::hit('register', $ip);
        if (RateLimiter::tooMany('otp_send', $input['email'], 5, 3600)) {
            return Response::error('Too many OTP requests for this email. Try again in an hour.', 429, [], 'RATE_LIMITED');
        }

        $hash = password_hash($input['password'], PASSWORD_DEFAULT);
        $payload = ['full_name' => $input['full_name'], 'phone' => $input['phone'], 'password_hash' => $hash,
                    'role' => $input['role'], 'email' => $input['email']];

        try {
            $userId = \Somen\InventoryManagementSystem\Core\Database::getInstance()->transaction(function () use ($payload): int {
                $existing = User::findByEmail($payload['email']);
                User::purgeUnverifiedConflicts($payload['email'], $payload['phone'], $existing['id'] ?? null);
                if ($existing !== null) { // unverified (verified ones were rejected above)
                    User::refreshUnverified((int)$existing['id'], $payload);
                    return (int)$existing['id'];
                }
                return User::create($payload);
            });
        } catch (\PDOException $e) {
            if (\Somen\InventoryManagementSystem\Core\Database::isDuplicateKey($e)) {
                return Response::error('Account already exists. No OTP was sent.', 409, ['email' => 'This email or phone is already registered.'], 'DUPLICATE');
            }
            throw $e;
        }

        Session::set('pending_verify_user_id', $userId);
        return $this->deliverOtp($userId);
    }

    // ----------------------------------------------------------------- OTP
    public function otpStatus(Request $r): Response
    {
        $user = $this->pendingUser();
        if ($user === null) {
            return Response::error('No verification in progress. Please register or login.', 400, [], 'NO_PENDING', ['redirect' => base_url('login')]);
        }
        $active = OtpService::active((int)$user['id']);
        return Response::success('OK', [
            'email_masked' => $this->maskEmail($user['email']),
            'expires_in'   => $active ? max(0, (int)$active['seconds_left']) : 0,
            'can_resend'   => $active === null,
            'ttl'          => OtpService::TTL,
        ]);
    }

    public function otpVerify(Request $r): Response
    {
        $user = $this->pendingUser();
        if ($user === null) {
            return Response::error('No verification in progress. Please register or login.', 400, [], 'NO_PENDING', ['redirect' => base_url('login')]);
        }
        $v = Validator::make(['otp' => $r->string('otp')], ['otp' => ['required', 'digits', 'min:6', 'max:6']], self::LABELS);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $res = OtpService::verify((int)$user['id'], $r->string('otp'));
        switch ($res['status']) {
            case 'OK':
                User::markVerified((int)$user['id']);
                Session::forget('pending_verify_user_id');
                return Response::success('Email verified successfully. You can login now.', ['redirect' => base_url('login?verified=1')]);
            case 'EXPIRED':
                return Response::error('OTP has expired. Please request a new one.', 410, ['otp' => 'OTP expired.'], 'OTP_EXPIRED');
            case 'LOCKED':
                return Response::error('Too many wrong attempts. Wait for the timer to end, then request a new OTP.', 429, ['otp' => 'Too many wrong attempts.'], 'OTP_LOCKED');
            default:
                $left = (int)($res['attempts_left'] ?? 0);
                return Response::error("Incorrect OTP. {$left} attempt(s) left.", 422, ['otp' => 'Incorrect OTP.'], 'OTP_INVALID', ['attempts_left' => $left]);
        }
    }

    public function otpResend(Request $r): Response
    {
        $user = $this->pendingUser();
        if ($user === null) {
            return Response::error('No verification in progress. Please register or login.', 400, [], 'NO_PENDING', ['redirect' => base_url('login')]);
        }
        $active = OtpService::active((int)$user['id']);
        if ($active !== null) {
            return Response::error('Your current OTP is still valid. You can resend after it expires.', 429, [], 'OTP_STILL_VALID', ['expires_in' => (int)$active['seconds_left']]);
        }
        if (RateLimiter::tooMany('otp_send', $user['email'], 5, 3600)) {
            return Response::error('Too many OTP requests for this email. Try again in an hour.', 429, [], 'RATE_LIMITED');
        }
        return $this->deliverOtp((int)$user['id']);
    }

    /** Issue + e-mail an OTP. If the mail fails the OTP is thrown away so no timer starts. */
    private function deliverOtp(int $userId): Response
    {
        $user = User::findById($userId);
        RateLimiter::hit('otp_send', $user['email']);
        $issued = OtpService::issue($userId);
        $mail = MailService::sendOtp($user['email'], $user['full_name'], $issued['otp']);
        if (!$mail['ok']) {
            OtpService::discard($issued['id']);
            $msg = $mail['code'] === 'NO_INTERNET' ? "You haven't internet connection." : 'OTP sending failed. Please try again.';
            return Response::error($msg, 502, [], $mail['code'], ['redirect' => base_url('verify-otp')]);
        }
        return Response::success('OTP sent successfully to your email.', [
            'redirect'     => base_url('verify-otp'),
            'expires_in'   => $issued['expires_in'],
            'email_masked' => $this->maskEmail($user['email']),
        ]);
    }

    // --------------------------------------------------------------- login
    public function login(Request $r): Response
    {
        $email = mb_strtolower($r->string('email'));
        $password = (string)$r->input('password', '');
        $v = Validator::make(['email' => $email, 'password' => $password], ['email' => ['required', 'email'], 'password' => ['required']], self::LABELS);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $key = $email . '|' . $r->ip();
        if (RateLimiter::tooMany('login', $key, 5, 900)) {
            return Response::error('Too many failed attempts. Please try again in 15 minutes.', 429, [], 'RATE_LIMITED');
        }

        $user = User::findByEmail($email);
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsalt.usesomesillystringfore7e0f3iS6Pk5n2';
        $ok = password_verify($password, $hash) && $user !== null;
        if (!$ok) {
            RateLimiter::hit('login', $key);
            return Response::error('Invalid email or password.', 401, [], 'INVALID_CREDENTIALS');
        }
        if ($user['status'] !== 'active') {
            return Response::error('Your account is deactivated. Please contact the Inventory Manager.', 403, [], 'ACCOUNT_INACTIVE');
        }
        if ((int)$user['is_verified'] !== 1) {
            Session::set('pending_verify_user_id', (int)$user['id']);
            return Response::error('Please verify your email with the OTP to continue.', 403, [], 'NOT_VERIFIED', ['redirect' => base_url('verify-otp')]);
        }

        RateLimiter::clear('login', $key);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int)$user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        User::touchLogin((int)$user['id']);
        Session::regenerate();
        Session::forget('pending_verify_user_id');
        Session::set('auth_user', User::publicData(User::findById((int)$user['id'])));
        return Response::success('Welcome back, ' . $user['full_name'] . '!', ['redirect' => base_url('dashboard')]);
    }

    public function logout(Request $r): Response
    {
        Session::destroy();
        return Response::success('You have been logged out.', ['redirect' => base_url('login')]);
    }

    // ----------------------------------------------------- forgot / reset
    public function forgotPassword(Request $r): Response
    {
        $email = mb_strtolower($r->string('email'));
        $v = Validator::make(['email' => $email], ['email' => ['required', 'email']], self::LABELS);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }
        $ip = $r->ip();
        if (RateLimiter::tooMany('forgot_ip', $ip, 10, 3600) || RateLimiter::tooMany('forgot', $email, 3, 3600)) {
            return Response::error('Too many requests. Please try again later.', 429, [], 'RATE_LIMITED');
        }
        RateLimiter::hit('forgot_ip', $ip);

        $user = User::findByEmail($email);
        if ($user === null) {
            return Response::error('This email is not registered. No link was sent.', 404, ['email' => 'Email is not registered.'], 'EMAIL_NOT_REGISTERED');
        }
        if ((int)$user['is_verified'] !== 1) {
            return Response::error('This email is not verified yet. Login once to complete OTP verification.', 403, [], 'NOT_VERIFIED');
        }
        if ($user['status'] !== 'active') {
            return Response::error('Your account is deactivated. Please contact the Inventory Manager.', 403, [], 'ACCOUNT_INACTIVE');
        }

        RateLimiter::hit('forgot', $email);
        $issued = PasswordResetService::issue((int)$user['id']);
        $link = app_url('reset-password?token=' . $issued['token']);
        $mail = MailService::sendPasswordReset($user['email'], $user['full_name'], $link);
        if (!$mail['ok']) {
            PasswordResetService::discard($issued['id']);
            $msg = $mail['code'] === 'NO_INTERNET' ? "You haven't internet connection." : 'Forgot password link sending failed. Please try again.';
            return Response::error($msg, 502, [], $mail['code']);
        }
        return Response::success('Forgot password link sent successfully to your email.', ['hours_valid' => PasswordResetService::TTL_HOURS]);
    }

    public function resetPassword(Request $r): Response
    {
        $token = $r->string('token');
        $input = ['password' => (string)$r->input('password', ''), 'password_confirmation' => (string)$r->input('password_confirmation', '')];
        $v = Validator::make($input, ['password' => ['required', 'password'], 'password_confirmation' => ['required', 'same:password']], self::LABELS);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }
        if (!PasswordResetService::consume($token, password_hash($input['password'], PASSWORD_DEFAULT))) {
            return Response::error('This reset link is invalid, already used or expired. Please request a new one.', 410, [], 'LINK_INVALID', ['redirect' => base_url('forgot-password')]);
        }
        return Response::success('Password updated successfully. You can login now.', ['redirect' => base_url('login?reset=1')]);
    }

    // ------------------------------------------------------------- helpers
    private function pendingUser(): ?array
    {
        $id = (int)Session::get('pending_verify_user_id', 0);
        if ($id <= 0) {
            return null;
        }
        $u = User::findById($id);
        return ($u !== null && (int)$u['is_verified'] === 0) ? $u : null;
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        return mb_substr($local, 0, 1) . str_repeat('*', max(2, mb_strlen($local) - 1)) . '@' . $domain;
    }
}
