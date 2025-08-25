<?php
require_once __DIR__ . '/Controller.php';

class WebAppController extends Controller
{
    public function index(array $params)
    {
        $this->view('webapp', [
            'title' => 'Кабинет заявок'
        ]);
    }
}
