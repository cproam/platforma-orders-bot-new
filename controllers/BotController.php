<?php
require_once __DIR__ . '/Controller.php';
require_once dirname(__DIR__) . '/src/config.php';
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/Bot.php';

class BotController extends Controller
{
    private Database $db;
    private Bot $bot;

    public function __construct()
    {
        $this->db  = Database::get();
        $this->bot = new Bot(BOT_TOKEN);
    }

    public function webhook(array $params)
    {
        $update = json_decode(file_get_contents("php://input"), true);
        if (!$update) {
            http_response_code(400);
            return $this->json(['ok' => false, 'error' => 'Некорректный JSON']);
        }

        // --- Команда /myid ---
        if (isset($update['message']['text'])) {
            $text = trim((string)$update['message']['text']);
            if (preg_match('~^/myid(?:@\w+)?(?:\s|$)~ui', $text)) {
                $chatId = (int)($update['message']['chat']['id'] ?? 0);
                $userId = (int)($update['message']['from']['id'] ?? $chatId);

                $msg = "Ваш ID: {$userId}\nВот ваш id передайте его администратору.";
                $this->bot->sendMessage($chatId, $msg);

                return $this->json(['ok' => true]);
            }
        }

        // Нас интересуют ответы бухгалтера на наш исходный месседж
        if (isset($update['message']) && isset($update['message']['reply_to_message'])) {
            $fromId = (int)($update['message']['from']['id'] ?? 0);

            // Только бухгалтер имеет право "закрыть" заявку
            if ($fromId !== ACCOUNTANT_ID) {
                return $this->json(['ok' => true]);
            }

            // Пытаемся вытащить номер заявки из исходного сообщения/подписи
            $source = ($update['message']['reply_to_message']['text'] ?? '')
                . ' ' .
                ($update['message']['reply_to_message']['caption'] ?? '');
            if (preg_match('/№\s*(\d+)/u', $source, $m)) {
                $orderId = (int)$m[1];
                $managerId = $this->db->getManagerId($orderId);

                if ($managerId) {
                    // Получатели: менеджер + директор + РОП
                    $targets = array_values(array_filter([$managerId, DIRECTOR_ID, ROP_ID]));

                    foreach ($targets as $to) {
                        // Небольшая подпись
                        $this->bot->sendMessage($to, "📎 Обновление по заявке №{$orderId}:");
                        // Копируем сообщение (а не «пересылаем»), чтобы скрыть имя отправителя
                        $this->bot->copyMessage(
                            $to,
                            $fromId,
                            (int)$update['message']['message_id']
                        );
                    }

                    // Обновим статус
                    $this->db->setStatus($orderId, 'completed');
                }
            }
        }

        return $this->json(['ok' => true]);
    }

    // (по желанию) /send можно оставить как есть
    public function send(array $params)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['recipient']) || empty($data['message'])) {
            http_response_code(400);
            return $this->json(['ok' => false, 'error' => 'Нет получателя или текста']);
        }
        return $this->json($this->bot->sendMessage($data['recipient'], $data['message']));
    }
}
