<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\View;

abstract class Controller
{
    public function __construct(protected View $view) {}

    /** A valid ID is a positive whole number like "1" or "42". Anything else returns null. */
    protected static function parseId(string $raw): ?int
    {
        if (!preg_match('/^[1-9][0-9]{0,9}$/', $raw)) {
            return null;
        }
        $id = (int) $raw;
        return $id <= 4294967295 ? $id : null; // INT UNSIGNED max
    }

    protected function error(int $status, string $message): void
    {
        $this->view->render('error', [
            'title'   => "Error $status",
            'status'  => $status,
            'message' => $message,
        ], $status);
    }

    public function notFound(): void
    {
        $this->error(404, 'Page not found.');
    }
}
