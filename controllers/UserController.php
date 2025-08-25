<?php
require_once __DIR__ . '/Controller.php';

class UserController extends Controller
{
    public function show(array $params)
    {
        $id = intval($params['id']);
        $this->json(['user_id' => $id, 'name' => "User #$id"]);
    }

    public function greet(array $params)
    {
        $name = htmlspecialchars($params['name']);
        $this->view("Hello, $name!");
    }
}
