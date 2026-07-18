<?php

namespace App\Core;

abstract class Controller {
    /**
     * Render a view template.
     */
    protected function view(string $view, array $data = [], string $layout = 'app'): void {
        View::render($view, $data, $layout);
    }

    /**
     * Return JSON response.
     */
    protected function json(mixed $data, int $statusCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to another URL.
     */
    protected function redirect(string $path): void {
        header("Location: " . route($path));
        exit;
    }

    /**
     * Enforce authentication. Redirects unauthorized users to the login screen.
     */
    protected function middlewareAuth(): void {
        if (!Auth::check()) {
            Session::flash('error', 'Please log in to access the dashboard.');
            $this->redirect('/login');
        }
    }
}
