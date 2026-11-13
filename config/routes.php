<?php
// Route table: URL pattern -> controller method.
// IDs are captured as "any segment" on purpose so the controller can return a
// clear 400 for malformed IDs (e.g. /discussions/abc) instead of a generic 404.

use App\Controllers\AuthController;
use App\Controllers\CommentController;
use App\Controllers\DiscussionController;

/** @var \Bramus\Router\Router $router */
/** @var DiscussionController $discussions */
/** @var CommentController $comments */
/** @var AuthController $auth */

// Public pages
$router->get('/', [$discussions, 'index']);
$router->get('/discussions/([^/]+)', [$discussions, 'show']);

// Accounts
$router->get('/register', [$auth, 'showRegister']);
$router->post('/register', [$auth, 'register']);
$router->get('/login', [$auth, 'showLogin']);
$router->post('/login', [$auth, 'login']);
$router->post('/logout', [$auth, 'logout']);

// Writing (login required, checked inside the controller on every request)
$router->post('/discussions/([^/]+)/comments', [$comments, 'storeTopLevel']);
$router->post('/discussions/([^/]+)/comments/([^/]+)/replies', [$comments, 'storeReply']);

$router->set404(function () use ($discussions) {
    $discussions->notFound();
});
