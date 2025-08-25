<?php
require_once __DIR__ . '/Controller.php';
require_once dirname(__DIR__) . '/src/config.php';
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/Bot.php';

class OrderController extends Controller
{
    private Database $db;
    private Bot $bot;

    public function __construct()
    {
        $this->db = Database::get();
        $this->bot = new Bot(BOT_TOKEN);
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
    }

    public function create(array $params)
    {
        $isJson = (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
        $data = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
        if (!is_array($data)) $data = [];

        $type = (string)($data['type'] ?? '');
        $managerId = (int)($data['manager_id'] ?? 0);

        // Проверка доступа менеджера
        if (!$managerId || !is_manager_allowed($managerId)) {
            http_response_code(403);
            return $this->json(['ok' => false, 'error' => 'Доступ запрещён']);
        }

        if (!in_array($type, ['new_order', 'old_order', 'request_document', 'request_pdf'], true)) {
            http_response_code(400);
            return $this->json(['ok' => false, 'error' => 'Неверный тип запроса']);
        }

        // Валидация полей и формирование payload
        $payload = [];
        switch ($type) {
            case 'new_order':
            case 'old_order':
                $payload = [
                    'franchise'    => trim((string)($data['franchise'] ?? '')),
                    'organization' => trim((string)($data['organization'] ?? '')),
                    'cost'         => (float)($data['cost'] ?? 0),
                    'leads'        => (int)  ($data['leads'] ?? 0),
                    'comment'      => trim((string)($data['comment'] ?? '')),
                ];
                if (!$payload['franchise'] || !$payload['organization'] || $payload['cost'] <= 0 || $payload['leads'] <= 0) {
                    http_response_code(400);
                    return $this->json(['ok' => false, 'error' => 'Проверьте поля заказа']);
                }
                break;

            case 'request_document':
            case 'request_pdf':
                $payload = [
                    'organization' => trim((string)($data['organization'] ?? '')),
                    'order_number' => trim((string)($data['order_number'] ?? '')),
                    'order_date'   => trim((string)($data['order_date'] ?? '')),
                    'total_cost'   => (float)($data['total_cost'] ?? 0),
                    'comment'      => trim((string)($data['comment'] ?? '')),
                ];
                if (!$payload['organization'] || !$payload['order_number'] || !$payload['order_date'] || $payload['total_cost'] < 0) {
                    http_response_code(400);
                    return $this->json(['ok' => false, 'error' => 'Проверьте поля запроса']);
                }
                break;
        }

        // Файл: обязателен только для new_order
        $savedPath = null;
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $safeName = preg_replace('~[^a-zA-Z0-9._-]+~', '_', $_FILES['document']['name']);
            $filename = time() . '_' . $safeName;
            $savedPath = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($_FILES['document']['tmp_name'], $savedPath)) {
                http_response_code(500);
                return $this->json(['ok' => false, 'error' => 'Не удалось сохранить файл']);
            }
        } elseif ($type === 'new_order') {
            http_response_code(400);
            return $this->json(['ok' => false, 'error' => 'Для нового клиента нужен документ']);
        }

        // Сохраняем в БД
        $orderId = $this->db->insertOrder($managerId, $type, $payload, $savedPath);

        // Текст уведомления
        $title = match ($type) {
            'new_order'        => '🆕 Новый клиент — Новый заказ',
            'old_order'        => '🔁 Действующий клиент — Заказ',
            'request_document' => '📄 Запрос документа',
            'request_pdf'      => '🖨️ Запрос PDF',
        };
        $lines = ["{$title} — №{$orderId}"];
        if ($type === 'new_order' || $type === 'old_order') {
            $lines[] = "Франшиза: " . $payload['franchise'];
            $lines[] = "Организация: " . $payload['organization'];
            $lines[] = "Стоимость лида: " . $payload['cost'];
            $lines[] = "Кол-во лидов: " . $payload['leads'];
            if ($payload['comment']) $lines[] = "Комментарий: " . $payload['comment'];
        } else {
            $lines[] = "Организация: " . $payload['organization'];
            $lines[] = "Номер заказа: " . $payload['order_number'];
            $lines[] = "Дата заказа: " . $payload['order_date'];
            $lines[] = "Итоговая сумма: " . $payload['total_cost'];
            if ($payload['comment']) $lines[] = "Комментарий: " . $payload['comment'];
        }
        $lines[] = "";
        // $lines[] = "ID менеджера: {$managerId}";
        $lines[] = "↩️ Ответьте на это сообщение файлом — он будет автоматически отправлен менеджеру.";

        $text = implode("\n", $lines);

        // Кому отправляем информацию о создании: бухгалтер + директор + РОП
        $recipients = array_values(array_filter([ACCOUNTANT_ID, DIRECTOR_ID, ROP_ID]));
        foreach ($recipients as $rid) {
            $this->bot->sendMessage($rid, $text);
            if ($savedPath) {
                $this->bot->sendDocument($rid, $savedPath, "Вложение к заявке №{$orderId}");
            }
        }

        return $this->json(['ok' => true, 'order_id' => $orderId]);
    }
}
