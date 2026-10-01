<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Controllers;

use Somen\InventoryManagementSystem\Core\Controller;
use Somen\InventoryManagementSystem\Core\Request;
use Somen\InventoryManagementSystem\Core\Response;
use Somen\InventoryManagementSystem\Core\Session;
use Somen\InventoryManagementSystem\Core\Validator;
use Somen\InventoryManagementSystem\Models\User;

/** /api/v1/profile* - the logged-in user's own account. */
final class ProfileController extends Controller
{
    private const LABELS = [
        'full_name' => 'Full name', 'phone' => 'Phone number', 'current_password' => 'Current password',
        'password' => 'New password', 'password_confirmation' => 'Confirm password',
    ];

    public function show(Request $r): Response
    {
        $u = User::findById((int)auth_user()['id']);
        return Response::success('OK', User::publicData($u));
    }

    public function update(Request $r): Response
    {
        $me = auth_user();
        $in = ['full_name' => $r->string('full_name'), 'phone' => $r->string('phone')];
        $v = Validator::make($in, [
            'full_name' => ['required', 'name', 'min:2', 'max:60'],
            'phone' => ['required', 'phone'],
        ], self::LABELS);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }
        if (User::phoneTaken($in['phone'], (int)$me['id'])) {
            return Response::error('This phone number is already registered.', 409, ['phone' => 'This phone number is already registered.'], 'DUPLICATE');
        }
        User::purgeUnverifiedConflicts($me['email'], $in['phone'], (int)$me['id']);
        User::updateProfile((int)$me['id'], $in['full_name'], $in['phone']);
        Session::set('auth_user', User::publicData(User::findById((int)$me['id'])));
        return Response::success('Profile updated successfully.', User::publicData(User::findById((int)$me['id'])));
    }

    public function changePassword(Request $r): Response
    {
        $me = auth_user();
        $in = [
            'current_password' => (string)$r->input('current_password', ''),
            'password' => (string)$r->input('password', ''),
            'password_confirmation' => (string)$r->input('password_confirmation', ''),
        ];
        $v = Validator::make($in, [
            'current_password' => ['required'],
            'password' => ['required', 'password'],
            'password_confirmation' => ['required', 'same:password'],
        ], self::LABELS);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }
        $u = User::findById((int)$me['id']);
        if (!password_verify($in['current_password'], $u['password_hash'])) {
            return $this->validationError(['current_password' => 'Current password is incorrect.']);
        }
        if (hash_equals($in['current_password'], $in['password'])) {
            return $this->validationError(['password' => 'New password must be different from the current one.']);
        }
        User::updatePassword((int)$me['id'], password_hash($in['password'], PASSWORD_DEFAULT));
        return Response::success('Password changed successfully.');
    }
}
