<?php
declare(strict_types=1);

use Somen\InventoryManagementSystem\Controllers\AuthController;
use Somen\InventoryManagementSystem\Controllers\PageController;
use Somen\InventoryManagementSystem\Controllers\ProfileController;

/** @var \Somen\InventoryManagementSystem\Core\Router $router */

// ------------------------------------------------------------------ pages
$router->get('/', [PageController::class, 'home']);
$router->get('/login', [PageController::class, 'login'], ['guest']);
$router->get('/register', [PageController::class, 'register'], ['guest']);
$router->get('/verify-otp', [PageController::class, 'verifyOtp'], ['guest']);
$router->get('/forgot-password', [PageController::class, 'forgotPassword'], ['guest']);
$router->get('/reset-password', [PageController::class, 'resetPassword']);
$router->get('/dashboard', [PageController::class, 'dashboard'], ['auth']);
$router->get('/profile', [PageController::class, 'profile'], ['auth']);

// ---------------------------------------------------------- REST: auth v1
$router->get('/api/v1/auth/captcha', [AuthController::class, 'captcha']);
$router->get('/api/v1/auth/check-availability', [AuthController::class, 'availability']);
$router->post('/api/v1/auth/register', [AuthController::class, 'register'], ['csrf']);
$router->get('/api/v1/auth/otp/status', [AuthController::class, 'otpStatus']);
$router->post('/api/v1/auth/otp/verify', [AuthController::class, 'otpVerify'], ['csrf']);
$router->post('/api/v1/auth/otp/resend', [AuthController::class, 'otpResend'], ['csrf']);
$router->post('/api/v1/auth/login', [AuthController::class, 'login'], ['csrf']);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout'], ['csrf']);
$router->post('/api/v1/auth/forgot-password', [AuthController::class, 'forgotPassword'], ['csrf']);
$router->post('/api/v1/auth/reset-password', [AuthController::class, 'resetPassword'], ['csrf']);

// -------------------------------------------------------- REST: profile v1
$router->get('/api/v1/profile', [ProfileController::class, 'show'], ['auth']);
$router->put('/api/v1/profile', [ProfileController::class, 'update'], ['auth', 'csrf']);
$router->post('/api/v1/profile/password', [ProfileController::class, 'changePassword'], ['auth', 'csrf']);
