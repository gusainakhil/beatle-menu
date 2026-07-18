<?php

namespace App\Core;

class Router {
    private array $routes = [];

    public function get(string $path, string $handler): void {
        $this->routes['GET'][$this->normalizePath($path)] = $handler;
    }

    public function post(string $path, string $handler): void {
        $this->routes['POST'][$this->normalizePath($path)] = $handler;
    }

    public function options(string $path, string $handler): void {
        $this->routes['OPTIONS'][$this->normalizePath($path)] = $handler;
    }

    private function normalizePath(string $path): string {
        $path = trim($path, '/');
        return $path === '' ? '/' : '/' . $path;
    }

    public function resolve(Request $request): void {
        http_response_code(200);
        $path = $this->normalizePath($request->getPath());
        $method = $request->getMethod();
        
        $handler = $this->routes[$method][$path] ?? null;
        
        if ($handler === null) {
            $this->renderError(404, "Page Not Found");
            return;
        }

        if ($method === 'POST' && !str_starts_with($path, '/api/')) {
            $token = $request->csrfToken();
            if (!Csrf::validate($token)) {
                $this->renderError(419, "Page Expired (CSRF Verification Failed)");
                return;
            }
        }

        list($controllerClass, $methodName) = explode('@', $handler);
        $fullControllerClass = "App\\Controllers\\" . $controllerClass;

        if (!class_exists($fullControllerClass)) {
            $this->renderError(500, "Controller [{$controllerClass}] not found.");
            return;
        }

        $controller = new $fullControllerClass();
        if (!method_exists($controller, $methodName)) {
            $this->renderError(500, "Action [{$methodName}] not found in controller [{$controllerClass}].");
            return;
        }

        try {
            Session::start();
            $controller->$methodName($request);
        } catch (\Exception $e) {
            $this->logError($e);
            
            $config = require __DIR__ . '/../../config/config.php';
            $debug = $config['app']['debug'] ?? false;
            $msg = $debug ? $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() : "An internal server error occurred.";
            
            $this->renderError(500, $msg);
        }
    }

    private function renderError(int $code, string $message): void {
        http_response_code($code);
        
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        error_log("[" . date('Y-m-d H:i:s') . "] Error {$code}: {$message}\n", 3, $logDir . '/app.log');

        $request = new Request();
        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $message,
                'code' => $code
            ]);
            return;
        }

        try {
            Session::start();
            View::render('errors/error', [
                'code' => $code,
                'message' => $message
            ], 'auth');
        } catch (\Exception $e) {
            echo "<h1>Error {$code}</h1><p>{$message}</p>";
        }
    }

    private function logError(\Exception $e): void {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logMessage = sprintf(
            "[%s] Exception: %s in %s on line %d\nStack Trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        error_log($logMessage, 3, $logDir . '/app.log');
    }
}
