<?php
namespace App\Core;

class Router {
    protected $routes = [];
    protected $currentGroupOptions = [];
    protected $middlewareMap = [
        'auth' => \App\Middleware\AuthMiddleware::class,
        'rate_limit' => \App\Middleware\RateLimitMiddleware::class,
    ];

    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }

    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }

    protected function addRoute($method, $path, $callback) {
        $middleware = $this->currentGroupOptions['middleware'] ?? [];
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }

        // Normalize path: trim all slashes and ensure exactly one leading slash
        $path = '/' . trim($path, '/');

        $this->routes[$method][$path] = [
            'callback' => $callback,
            'middleware' => $middleware,
            'prefix' => $this->currentGroupOptions['prefix'] ?? ''
        ];
    }

    public function group($options, $callback) {
        $previousOptions = $this->currentGroupOptions;
        
        // Merge middleware
        if (isset($options['middleware'])) {
            $currentMiddleware = $this->currentGroupOptions['middleware'] ?? [];
            if (!is_array($currentMiddleware)) $currentMiddleware = [$currentMiddleware];
            
            $newMiddleware = $options['middleware'];
            if (!is_array($newMiddleware)) $newMiddleware = [$newMiddleware];
            
            $options['middleware'] = array_merge($currentMiddleware, $newMiddleware);
        }

        // Merge prefix
        if (isset($options['prefix'])) {
            $currentPrefix = $this->currentGroupOptions['prefix'] ?? '';
            $options['prefix'] = $currentPrefix . $options['prefix'];
        }

        $this->currentGroupOptions = array_merge($this->currentGroupOptions, $options);
        $callback($this);
        $this->currentGroupOptions = $previousOptions;
    }

    public function resolve() {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'];
        $path = explode('?', $path)[0];

        $baseUrl = App::getBaseUrl();
        $scriptPath = parse_url($baseUrl, PHP_URL_PATH);
        
        // Match base URL prefix case-insensitively and strip it
        if (!empty($scriptPath) && stripos($path, $scriptPath) === 0) {
           $path = substr($path, strlen($scriptPath));
        }
        
        // Normalize path for matching: ensure exactly one leading slash, no trailing
        $pathMatch = '/' . trim($path, '/');

        $route = $this->routes[$method][$pathMatch] ?? false;

        if ($route === false) {
            http_response_code(404);
            $viewPath = __DIR__ . '/../../resources/views/errors/404.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                echo "<h1>404 Not Found</h1>";
                echo "<p>Đường dẫn: <strong>" . htmlspecialchars($pathMatch) . "</strong> (" . $method . ") không tồn tại.</p>";
                echo "<p><small>Debug Info: ScriptPath: [" . htmlspecialchars($scriptPath) . "] | RequestURI: [" . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') . "]</small></p>";
            }
            return;
        }

        $callback = $route['callback'];
        $middlewares = $route['middleware'];

        // Execute Middlewares
        foreach ($middlewares as $middlewareDefinition) {
            // Tách middleware name và params (ví dụ: 'rate_limit:5,1')
            $parts = explode(':', $middlewareDefinition);
            $mwName = $parts[0];
            $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

            if (isset($this->middlewareMap[$mwName])) {
                $mwClass = $this->middlewareMap[$mwName];
                $middleware = new $mwClass();
                if (method_exists($middleware, 'handle')) {
                    call_user_func_array([$middleware, 'handle'], $params);
                }
            }
        }

        if (is_string($callback)) {
            $parts = explode('@', $callback);
            $controllerName = "App\\Controllers\\" . $parts[0];
            $action = $parts[1];
            
            if (!class_exists($controllerName)) {
                die("Controller $controllerName not found");
            }

            $controller = new $controllerName();

            // Auto CSRF for all POST requests
            if ($method === 'POST') {
                if (method_exists($controller, 'validateCsrf')) {
                    $controller->validateCsrf();
                }
            }

            if (!method_exists($controller, $action)) {
                die("Action $action not found in $controllerName");
            }

            call_user_func([$controller, $action]);
        } else {
            call_user_func($callback);
        }
    }
}
