<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\View;
use PDOException;

final class AuthController extends Controller
{
    public function __construct(private User $users, View $view)
    {
        parent::__construct($view);
    }

    /** GET /register */
    public function showRegister(): void
    {
        $this->view->render('register', ['title' => 'Register']);
    }

    /** POST /register */
    public function register(): void
    {
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->error(403, 'Invalid or expired form token. Go back, reload the page and try again.');
            return;
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3–30 letters, numbers or underscores.';
        }
        if (strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Password must be 8–72 characters.';
        }
        if ($errors === [] && $this->users->exists($username, $email)) {
            $errors[] = 'That username or email is already registered.';
        }

        if ($errors !== []) {
            $this->view->render('register', [
                'title' => 'Register', 'errors' => $errors,
                'old_username' => $username, 'old_email' => $email,
            ], 422);
            return;
        }

        try {
            $id = $this->users->create($username, $email, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $e) {
            // Unique key race: someone registered the same name/email at the same moment.
            $this->view->render('register', [
                'title' => 'Register', 'errors' => ['That username or email is already registered.'],
                'old_username' => $username, 'old_email' => $email,
            ], 422);
            return;
        }

        Auth::login(['id' => $id, 'username' => $username]);
        View::flash("Welcome, $username! Your account was created.");
        View::redirect('/');
    }

    /** GET /login */
    public function showLogin(): void
    {
        $this->view->render('login', ['title' => 'Log in']);
    }

    /** POST /login */
    public function login(): void
    {
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->error(403, 'Invalid or expired form token. Go back, reload the page and try again.');
            return;
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = $username === '' ? null : $this->users->findByUsername($username);

        // A NULL password hash (seed users) can never authenticate.
        $ok = $user !== null
            && is_string($user['password_hash'])
            && $user['password_hash'] !== ''
            && password_verify($password, $user['password_hash']);

        if (!$ok) {
            $this->view->render('login', [
                'title' => 'Log in', 'errors' => ['Invalid username or password.'],
                'old_username' => $username,
            ], 401);
            return;
        }

        Auth::login($user); // regenerates the session ID
        View::flash('You are logged in.');
        View::redirect('/');
    }

    /** POST /logout */
    public function logout(): void
    {
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->error(403, 'Invalid or expired form token. Go back, reload the page and try again.');
            return;
        }
        Auth::logout();
        View::redirect('/');
    }
}
