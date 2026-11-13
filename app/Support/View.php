<?php
declare(strict_types=1);

namespace App\Support;

/** Thin wrapper around Mustache. {{name}} is HTML-escaped; we never use {{{ }}} for user text. */
final class View
{
    private \Mustache_Engine $engine;

    public function __construct(string $viewsDir)
    {
        $this->engine = new \Mustache_Engine([
            'loader'          => new \Mustache_Loader_FilesystemLoader($viewsDir),
            'partials_loader' => new \Mustache_Loader_FilesystemLoader($viewsDir . '/partials'),
            'charset'         => 'UTF-8',
            'entity_flags'    => ENT_QUOTES | ENT_SUBSTITUTE, // also escapes single quotes
            'strict_callables' => true,
        ]);
    }

    /** Render a template with shared layout data (current user, CSRF token, flash message). */
    public function render(string $template, array $data = [], int $status = 200): void
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $shared = [
            'logged_in' => Auth::check(),
            'username'  => Auth::username(),
            'csrf'      => Csrf::token(),
            'flash'     => $flash,
            'title'     => 'Nested Forum',
        ];
        $data['has_errors'] = !empty($data['errors']);

        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $this->engine->render($template, array_merge($shared, $data));
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url, true, 303);
    }

    public static function flash(string $message): void
    {
        $_SESSION['flash'] = $message;
    }
}
