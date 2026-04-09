<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

use PageMill\Router\Router;
use Dealnews\Indexera\Api;
use Dealnews\Indexera\Repository;
use Dealnews\Indexera\SessionHandler;
use Dealnews\Indexera\Mapper\SessionMapper;
use Dealnews\Indexera\Controller\Auth\LoginController;
use Dealnews\Indexera\Controller\Auth\LoginPostController;
use Dealnews\Indexera\Controller\Auth\LogoutController;
use Dealnews\Indexera\Controller\Auth\OAuthCallbackController;
use Dealnews\Indexera\Controller\Auth\OAuthStartController;
use Dealnews\Indexera\Controller\Auth\RegisterController;
use Dealnews\Indexera\Controller\Auth\RegisterPostController;
use Dealnews\Indexera\Controller\Admin\SettingsController as AdminSettingsController;
use Dealnews\Indexera\Controller\Admin\SettingsPostController as AdminSettingsPostController;
use Dealnews\Indexera\Controller\Admin\ToggleAdminController;
use Dealnews\Indexera\Controller\Admin\UsersController as AdminUsersController;
use Dealnews\Indexera\Controller\DashboardController;
use Dealnews\Indexera\Controller\GroupController;
use Dealnews\Indexera\Controller\GroupCreateController;
use Dealnews\Indexera\Controller\GroupCreatePostController;
use Dealnews\Indexera\Controller\GroupManageController;
use Dealnews\Indexera\Controller\GroupsController;
use Dealnews\Indexera\Controller\PageEditController;
use Dealnews\Indexera\Controller\ProfileController;
use Dealnews\Indexera\Controller\ProfilePasswordController;
use Dealnews\Indexera\Controller\ProfilePasswordPostController;
use Dealnews\Indexera\Controller\HomeController;
use Dealnews\Indexera\Controller\NotFoundController;
use Dealnews\Indexera\Controller\PagesController;
use Dealnews\Indexera\Controller\PageViewController;

$session_handler = new SessionHandler(new SessionMapper());
session_set_save_handler($session_handler, true);
session_set_cookie_params([
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$repository = new Repository();
$api        = new Api();

$routes = [
    // API routes (CRUD via dealnews/data-mapper-api)
    $api->getAllRoutes('/api'),

    // Home
    [
        'type'    => 'exact',
        'pattern' => '/',
        'method'  => 'GET',
        'action'  => HomeController::class,
    ],

    // Auth
    [
        'type'    => 'exact',
        'pattern' => '/login',
        'method'  => 'GET',
        'action'  => LoginController::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/login',
        'method'  => 'POST',
        'action'  => LoginPostController::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/logout',
        'method'  => 'GET',
        'action'  => LogoutController::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/register',
        'method'  => 'GET',
        'action'  => RegisterController::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/register',
        'method'  => 'POST',
        'action'  => RegisterPostController::class,
    ],

    // OAuth — callback defined before start to avoid prefix shadowing
    [
        'type'    => 'regex',
        'pattern' => '!/auth/([a-z0-9_-]+)/callback/?$!i',
        'method'  => 'GET',
        'tokens'  => ['provider'],
        'action'  => OAuthCallbackController::class,
    ],
    [
        'type'    => 'regex',
        'pattern' => '!/auth/([a-z0-9_-]+)/?$!i',
        'method'  => 'GET',
        'tokens'  => ['provider'],
        'action'  => OAuthStartController::class,
    ],

    // Authenticated
    [
        'type'    => 'exact',
        'pattern' => '/dashboard',
        'method'  => 'GET',
        'action'  => DashboardController::class,
    ],

    [
        'type'    => 'regex',
        'pattern' => '!/pages/(\d+)/edit/?$!i',
        'method'  => 'GET',
        'tokens'  => ['page_id'],
        'action'  => PageEditController::class,
    ],

    // Profile
    [
        'type'    => 'exact',
        'pattern' => '/profile',
        'method'  => 'GET',
        'action'  => ProfileController::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/profile/password',
        'method'  => 'GET',
        'action'  => ProfilePasswordController::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/profile/password',
        'method'  => 'POST',
        'action'  => ProfilePasswordPostController::class,
    ],

    // Admin
    [
        'type'    => 'exact',
        'pattern' => '/admin/users',
        'method'  => 'GET',
        'action'  => AdminUsersController::class,
    ],
    [
        'type'    => 'regex',
        'pattern' => '!/admin/users/(\d+)/toggle-admin/?$!i',
        'method'  => 'POST',
        'tokens'  => ['user_id'],
        'action'  => ToggleAdminController::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/admin/settings',
        'method'  => 'GET',
        'action'  => AdminSettingsController::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/admin/settings',
        'method'  => 'POST',
        'action'  => AdminSettingsPostController::class,
    ],

    // Page directory
    [
        'type'    => 'exact',
        'pattern' => '/pages',
        'method'  => 'GET',
        'action'  => PagesController::class,
    ],

    // Group directory
    [
        'type'    => 'exact',
        'pattern' => '/groups',
        'method'  => 'GET',
        'action'  => GroupsController::class,
    ],

    // Create group — exact routes before the slug regex
    [
        'type'    => 'exact',
        'pattern' => '/groups/create',
        'method'  => 'GET',
        'action'  => GroupCreateController::class,
    ],
    [
        'type'    => 'exact',
        'pattern' => '/groups/create',
        'method'  => 'POST',
        'action'  => GroupCreatePostController::class,
    ],

    // Group manage — sub-route before group home
    [
        'type'    => 'regex',
        'pattern' => '!/groups/([a-z0-9_-]+)/manage/?$!i',
        'method'  => 'GET',
        'tokens'  => ['group_slug'],
        'action'  => GroupManageController::class,
    ],

    // Group home
    [
        'type'    => 'regex',
        'pattern' => '!/groups/([a-z0-9_-]+)/?$!i',
        'method'  => 'GET',
        'tokens'  => ['group_slug'],
        'action'  => GroupController::class,
    ],

    // Public page or group page view — tries group slug first, falls back to username
    [
        'type'    => 'regex',
        'pattern' => '!/([a-z0-9_-]+)/([a-z0-9_-]+)/?$!i',
        'method'  => 'GET',
        'tokens'  => ['username', 'slug'],
        'action'  => PageViewController::class,
    ],

    // 404 fallback
    [
        'type'   => 'default',
        'action' => NotFoundController::class,
    ],
];

$router = new Router($routes);
$route  = $router->match();

$action = $route['action'] ?? null;
$tokens = $route['tokens'] ?? [];

if (empty($action)) {
    http_response_code(404);
    exit;
}

$request_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_subclass_of($action, \DealNews\DataMapperAPI\Action\Base::class, true) ||
    is_a($action, \DealNews\DataMapperAPI\Action\Base::class, true))
{
    header('Content-Type: application/json');
    $base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
                '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $api->executeAction($action, $tokens, $base_url, $repository);
} else {
    $controller = new $action($request_path, $tokens);
    $controller->handleRequest();
}
