<?php
declare(strict_types=1);

// Front controller: the single public entry point.

// When using PHP's built-in server, let it serve real files (CSS, JS, images) directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\CommentController;
use App\Controllers\DiscussionController;
use App\Models\Comment;
use App\Models\Discussion;
use App\Models\User;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\View;

$configFile = __DIR__ . '/../config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing config/config.php. Copy config/config.example.php to config/config.php first.');
}
$config = require $configFile;

Auth::startSession();

$pdo = Database::connect($config['db']);
$users = new User($pdo);
$view = new View(__DIR__ . '/../app/Views');

$discussions = new DiscussionController(new Discussion($pdo), new Comment($pdo), $view, $config);
$comments = new CommentController(new Discussion($pdo), new Comment($pdo), $view, $config);
$auth = new AuthController($users, $view);

$router = new \Bramus\Router\Router();
require __DIR__ . '/../config/routes.php';
$router->run();
