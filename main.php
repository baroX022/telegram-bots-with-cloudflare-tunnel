<?php

define('BOT_TOKEN', '');
define('TELE_API_BASE', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

class TelegramBot {
    private $quotes = [
        "The only way to do great work is to love what you do. - Steve Jobs",
        "Innovation distinguishes between a leader and a follower. - Steve Jobs",
        "Stay hungry, stay foolish. - Steve Jobs"
    ];
    
    public function handleUpdate() {
        $update = json_decode(file_get_contents('php://input'), true);
        
        if (!$update) {
            http_response_code(400);
            exit;
        }
        
        $chat_id = $update['message']['chat']['id'] ?? null;
        $text = $update['message']['text'] ?? '';
        
        if (!$chat_id || !$text) {
            exit;
        }
        
        $this->processCommand($chat_id, $text);
    }
    
    private function processCommand($chat_id, $text) {
        $command = trim($text);
        
        switch ($command) {
            case '/start':
                $this->sendWelcomeMessage($chat_id);
                break;
                
            case '/setName':
                $this->sendNamePrompt($chat_id);
                break;
                
            case '/commands':
                $this->showCommands($chat_id);
                break;
                
            case '/quotes':
                $this->sendRandomQuote($chat_id);
                break;
                
            default:
                if (str_starts_with($command, '/setName ')) {
                    $this->handleSetName($chat_id, $command);
                } else {
                    $this->sendMessage($chat_id, "❌ Unknown command. Use /commands to see available commands.");
                }
                break;
        }
    }
    
    private function sendWelcomeMessage($chat_id) {
        $message = "👋 Welcome to the Example Bot!\n\n";
        $message .= "Available commands:\n";
        $message .= "/start - Show welcome message\n";
        $message .= "/setName [name] - Set your name\n";
        $message .= "/commands - List all commands\n";
        $message .= "/quotes - Get random inspirational quote";
        
        $this->sendMessage($chat_id, $message);
    }
    
    private function sendNamePrompt($chat_id) {
        $message = "Please use the command with your name:\n";
        $message .= "/setName YourName\n\n";
        $message .= "Example: /setName John";
        
        $this->sendMessage($chat_id, $message);
    }
    
    private function handleSetName($chat_id, $command) {
        $parts = explode(' ', $command, 2);
        
        if (count($parts) < 2 || empty($parts[1])) {
            $this->sendNamePrompt($chat_id);
            return;
        }
        
        $name = trim($parts[1]);
        $message = "👋 Welcome back {$name}!\n\n";
        $message .= "You can see all available commands via /commands";
        
        $this->sendMessage($chat_id, $message);
    }
    
    private function showCommands($chat_id) {
        $message = "📋 Available Commands:\n\n";
        $message .= "/start - Show welcome message\n";
        $message .= "/setName [name] - Set your name\n";
        $message .= "/commands - List all commands\n";
        $message .= "/quotes - Get random inspirational quote\n\n";
        $message .= "Simply type the command to use it!";
        
        $this->sendMessage($chat_id, $message);
    }
    
    private function sendRandomQuote($chat_id) {
        $randomIndex = array_rand($this->quotes);
        $quote = $this->quotes[$randomIndex];
        
        $message = "💡 Inspirational Quote:\n\n";
        $message .= "\"{$quote}\"";
        
        $this->sendMessage($chat_id, $message);
    }
    
    private function sendMessage($chat_id, $text) {
        $url = TELE_API_BASE . 'sendMessage';
        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        $this->makeApiRequest($url, $data);
    }
    
    private function makeApiRequest($url, $data) {
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        file_get_contents($url, false, $context);
    }
}

$bot = new TelegramBot();
$bot->handleUpdate();

?>