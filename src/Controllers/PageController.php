<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Controllers;

use Somen\InventoryManagementSystem\Core\Controller;
use Somen\InventoryManagementSystem\Core\Request;
use Somen\InventoryManagementSystem\Core\Response;
use Somen\InventoryManagementSystem\Core\Session;
use Somen\InventoryManagementSystem\Models\User;
use Somen\InventoryManagementSystem\Services\CaptchaService;
use Somen\InventoryManagementSystem\Services\PasswordResetService;

/** HTML pages. All data operations happen through the REST API via AJAX. */
final class PageController extends Controller
{
    public function home(Request $r): Response
    {
        return Response::redirect(base_url(auth_user() ? 'dashboard' : 'login'));
    }

    public function login(Request $r): Response
    {
        $notice = null;
        if ($r->string('verified') === '1') {
            $notice = 'Email verified successfully. Please login to continue.';
        } elseif ($r->string('reset') === '1') {
            $notice = 'Password updated successfully. Please login with your new password.';
        }
        return $this->view('auth/login', ['title' => 'Login', 'notice' => $notice], 'layouts/auth');
    }

    public function register(Request $r): Response
    {
        return $this->view('auth/register', ['title' => 'Create account', 'captchaOk' => CaptchaService::available()], 'layouts/auth');
    }

    public function verifyOtp(Request $r): Response
    {
        $id = (int)Session::get('pending_verify_user_id', 0);
        $u = $id > 0 ? User::findById($id) : null;
        if ($u === null || (int)$u['is_verified'] === 1) {
            return Response::redirect(base_url('login'));
        }
        return $this->view('auth/verify-otp', ['title' => 'Verify email'], 'layouts/auth');
    }

    public function forgotPassword(Request $r): Response
    {
        return $this->view('auth/forgot-password', ['title' => 'Forgot password'], 'layouts/auth');
    }

    public function resetPassword(Request $r): Response
    {
        $token = $r->string('token');
        $valid = $token !== '' && PasswordResetService::find($token) !== null;
        return $this->view('auth/reset-password', ['title' => 'Reset password', 'token' => $token, 'valid' => $valid], 'layouts/auth');
    }

    public function dashboard(Request $r): Response
    {
        $user = auth_user();
        $nav = require dirname(__DIR__, 2) . '/config/navigation.php';
        $view = $user['role'] === 'inventory_manager' ? 'dashboard/manager' : 'dashboard/staff';
        return $this->view($view, ['title' => 'Dashboard', 'active' => 'dashboard', 'user' => $user, 'nav' => $nav]);
    }

    public function profile(Request $r): Response
    {
        $u = User::findById((int)auth_user()['id']);
        return $this->view('profile/index', ['title' => 'My Profile', 'active' => 'profile', 'profile' => User::publicData($u)]);
    }
}
