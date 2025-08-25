<?php
// router.php

$routes = [];

function route(string $method, string $pattern, string $controllerAction)
{
    global $routes;
    // convert /user/{id} → regex
    $regex = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $pattern);
    $regex = '#^' . $regex . '$#';
    $routes[] = [$method, $regex, $controllerAction];
}

function dispatch(string $method, string $path)
{
    global $routes;

    foreach ($routes as [$m, $regex, $controllerAction]) {
        if ($m !== $method) continue;
        if (preg_match($regex, $path, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            // Split "UserController@show"
            [$class, $action] = explode('@', $controllerAction);

            $file = dirname(__DIR__) . '/controllers/' . $class . '.php';
            if (!file_exists($file)) {
                http_response_code(500);
                exit("Controller file $file not found");
            }
            require_once $file;

            if (!class_exists($class)) {
                http_response_code(500);
                exit("Controller class $class not found");
            }

            $controller = new $class();
            if (!method_exists($controller, $action)) {
                http_response_code(500);
                exit("Method $action not found in $class");
            }

            return $controller->$action($params);
        }
    }

    http_response_code(404);
    echo "404 Not Found";
}
