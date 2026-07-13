<?php

declare(strict_types=1);

/**
 * Authentication routes — JSON API + HTML UI (Sprint 2A/2B).
 *
 * @var \JobVisa\App\Routing\RouteRegistrar $router
 */

// —— JSON API (Sprint 1) ——
$router->group('auth_public_get', function ($router): void {
    $router->get('/auth/csrf', 'Auth\\AuthController@csrf');
}, [
    'middleware' => ['web', 'remember'],
]);

$router->group('auth_guest_post', function ($router): void {
    $router->post('/auth/register', 'Auth\\AuthController@register');
    $router->post('/auth/login', 'Auth\\AuthController@login');
}, [
    'middleware' => ['web', 'remember', 'guest', 'csrf'],
]);

$router->group('auth_token_post', function ($router): void {
    $router->post('/auth/password/forgot', 'Auth\\PasswordController@forgot');
    $router->post('/auth/password/reset', 'Auth\\PasswordController@reset');
    $router->post('/auth/email/verify', 'Auth\\PasswordController@verifyEmail');
    $router->post('/auth/email/resend', 'Auth\\PasswordController@resendVerification');
}, [
    'middleware' => ['web', 'remember', 'csrf'],
]);

$router->group('auth_session_get', function ($router): void {
    $router->get('/auth/me', 'Auth\\AuthController@me');
    $router->get('/auth/redirect', 'Auth\\AuthController@redirectTarget');
}, [
    'middleware' => ['web', 'remember', 'auth'],
]);

$router->group('auth_session_post', function ($router): void {
    $router->post('/auth/logout', 'Auth\\AuthController@logout');
}, [
    'middleware' => ['web', 'remember', 'auth', 'csrf'],
]);

// —— HTML guest: login / register ——
$router->group('auth_ui_guest', function ($router): void {
    $router->get('/register', 'Auth\\WebAuthController@showRegister');
    $router->post('/register', 'Auth\\WebAuthController@register');
    $router->get('/login', 'Auth\\WebAuthController@showLogin');
    $router->post('/login', 'Auth\\WebAuthController@login');
}, [
    'middleware' => ['web', 'remember', 'guest.web', 'csrf'],
]);

// —— Password reset (guest-accessible) ——
$router->group('auth_ui_password', function ($router): void {
    $router->get('/forgot-password', 'Auth\\WebPasswordResetController@showForgot');
    $router->post('/forgot-password', 'Auth\\WebPasswordResetController@sendResetLink');
    $router->get('/reset-password/{token}', 'Auth\\WebPasswordResetController@showReset');
    $router->post('/reset-password', 'Auth\\WebPasswordResetController@reset');
}, [
    'middleware' => ['web', 'remember', 'csrf'],
]);

// —— Email verification ——
$router->group('auth_ui_verify_get', function ($router): void {
    $router->get('/email/verify', 'Auth\\WebEmailController@notice');
    $router->get('/email/verify/{token}', 'Auth\\WebEmailController@verify');
}, [
    'middleware' => ['web', 'remember'],
]);

$router->group('auth_ui_verify_post', function ($router): void {
    $router->post('/email/verification-notification', 'Auth\\WebEmailController@resend');
}, [
    'middleware' => ['web', 'remember', 'csrf'],
]);

// —— Logout (authenticated, including unverified) ——
$router->group('auth_ui_logout', function ($router): void {
    $router->post('/logout', 'Auth\\WebAuthController@logout');
}, [
    'middleware' => ['web', 'remember', 'auth.web', 'csrf'],
]);
