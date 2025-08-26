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

        // Доступ менеджеру
        if (!$managerId || !is_manager_allowed($managerId)) {
            http_response_code(403);
            return $this->json(['ok' => false, 'error' => 'Доступ запрещён']);
        }

        // Разрешённые типы (PDF убран)
        if (!in_array($type, ['new_order', 'old_order', 'request_document'], true)) {
            http_response_code(400);
            return $this->json(['ok' => false, 'error' => 'Неверный тип запроса']);
        }

        // Формируем payload
        $payload = [];
        switch ($type) {
            case 'new_order':
            case 'old_order':
                $payload = [
                    'franchise'    => trim((string)($data['franchise'] ?? '')),
                    'organization' => trim((string)($data['organization'] ?? '')),
                    'cost'         => ($data['cost'] === '' ? null : (float)$data['cost']),
                    'leads'        => ($data['leads'] === '' ? null : (int)$data['leads']),
                    'comment'      => trim((string)($data['comment'] ?? '')),
                ];
                // Для заказа допустимы пустые поля, но хотя бы организация обычно нужна — оставим мягкую проверку
                if ($type === 'new_order' && empty($_FILES['document'])) {
                    http_response_code(400);
                    return $this->json(['ok' => false, 'error' => 'Для нового клиента нужен документ']);
                }
                break;

            case 'request_document':
                // Единственное обязательное поле — организация
                $organization = trim((string)($data['organization'] ?? ''));
                if ($organization === '') {
                    http_response_code(400);
                    return $this->json(['ok' => false, 'error' => 'Укажите название организации']);
                }
                $payload = [
                    'organization'   => $organization,
                    'doc_type'       => trim((string)($data['doc_type'] ?? '')),          // "Акт выполненных работ" | "Акт сверки" | ''
                    'inn'            => trim((string)($data['inn'] ?? '')),
                    'invoice_number' => trim((string)($data['invoice_number'] ?? '')),    // номер счёта
                    'period_from'    => trim((string)($data['period_from'] ?? '')),       // дата YYYY-MM-DD
                    'period_to'      => trim((string)($data['period_to'] ?? '')),         // дата YYYY-MM-DD
                    'format'         => trim((string)($data['format'] ?? '')),            // "PDF" | "ЭДО" | ''
                    'total_cost'     => ($data['total_cost'] === '' ? null : (float)$data['total_cost']),
                    'comment'        => trim((string)($data['comment'] ?? '')),
                ];
                break;
        }

        // Файл (обязателен только для new_order)
        $savedPath = null;
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0777, true);
            }
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

        // Сохраняем
        $orderId = $this->db->insertOrder($managerId, $type, $payload, $savedPath);

        // Сообщение бухгалтеру/директору/РОП
        $title = match ($type) {
            'new_order'        => '🆕 Новый клиент',
            'old_order'        => '🔁 Действующий клиент',
            'request_document' => '📄 Запрос документа',
        };

        $lines = ["{$title} — №{$orderId}"];

        if ($type === 'new_order' || $type === 'old_order') {
            if ($payload['franchise']   !== '') $lines[] = "Франшиза: " . $payload['franchise'];
            if ($payload['organization'] !== '') $lines[] = "Организация: " . $payload['organization'];
            if ($payload['cost']        !== null) $lines[] = "Стоимость лида: " . $payload['cost'];
            if ($payload['leads']       !== null) $lines[] = "Кол-во лидов: " . $payload['leads'];
            if ($payload['comment']     !== '') $lines[] = "Комментарий: " . $payload['comment'];
        } else {
            $pd = $payload; // для краткости
            $lines[] = "Организация: " . $pd['organization'];
            if ($pd['doc_type']       !== '') $lines[] = "Тип документа: " . $pd['doc_type'];
            if ($pd['inn']            !== '') $lines[] = "ИНН: " . $pd['inn'];
            if ($pd['invoice_number'] !== '') $lines[] = "Номер счёта: " . $pd['invoice_number'];
            if ($pd['period_from']    !== '' || $pd['period_to'] !== '') {
                $lines[] = "Период: " . ($pd['period_from'] ?: '—') . " → " . ($pd['period_to'] ?: '—');
            }
            if ($pd['format']         !== '') $lines[] = "Формат: " . $pd['format']; // PDF или ЭДО
            if ($pd['total_cost']     !== null) $lines[] = "Итоговая сумма: " . $pd['total_cost'];
            if ($pd['comment']        !== '') $lines[] = "Комментарий: " . $pd['comment'];
        }

        $lines[] = "";
        // $lines[] = "ID менеджера: {$managerId}";
        $lines[] = "↩️ Ответьте на это сообщение файлом — он будет автоматически отправлен менеджеру.";

        $text = implode("\n", $lines);

        // Рассылки: бухгалтер + директор + РОП
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
