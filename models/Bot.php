<?php
class Bot
{
    private string $token;
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function sendMessage(int|string $chatId, string $text): array
    {
        return $this->call('sendMessage', ['chat_id' => $chatId, 'text' => $text]);
    }

    public function sendDocument(int|string $chatId, string $filePath, string $caption = ''): array
    {
        return $this->callFile('sendDocument', ['chat_id' => $chatId, 'caption' => $caption], 'document', $filePath);
    }

    /** Копируем сообщение без «Переслано от …» */
    public function copyMessage(int|string $toChatId, int|string $fromChatId, int $messageId, array $opts = []): array
    {
        $params = array_merge([
            'chat_id'      => $toChatId,
            'from_chat_id' => $fromChatId,
            'message_id'   => $messageId,
        ], $opts);
        return $this->call('copyMessage', $params);
    }

    private function call(string $method, array $params): array
    {
        $ch = curl_init("https://api.telegram.org/bot{$this->token}/{$method}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true) ?: ['ok' => false, 'error' => $result];
    }

    private function callFile(string $method, array $params, string $fileField, string $filePath): array
    {
        $ch = curl_init("https://api.telegram.org/bot{$this->token}/{$method}");
        $params[$fileField] = new \CURLFile($filePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true) ?: ['ok' => false, 'error' => $result];
    }
}
