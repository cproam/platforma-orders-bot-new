<?php
abstract class Controller
{
    protected function view(string $file, array $data = [])
    {
        $path = dirname(__DIR__) . '/templates/' . $file . '.php';
        if (!file_exists($path)) {
            http_response_code(500);
            echo "Template $file not found";
            return;
        }

        extract($data, EXTR_SKIP);
        include $path;
    }

    protected function json($data)
    {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($data);
    }
}
