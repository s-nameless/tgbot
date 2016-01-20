<?php

namespace Tgbot;

use Exception;

class TelegramBot extends TelegramBotCore
{

    protected $chatClass;
    protected $chatOptions = [];
    protected $chatInstances = array();

    /**
     *
     * @param string $token
     * @param array $chat_params
     * @param array $options
     * @throws Exception
     */
    public function __construct($token, array $chat_params, array $options = [])
    {
        parent::__construct($token, $options);

        if (empty($chat_params[0])) {
            throw new \InvalidArgumentException("chat_params array must at least contains chat class name at index 0");
        }

        $chat_class = $chat_params[0];
        $chat_options = isset($chat_params[1]) ? (array) $chat_params[1] : [];

        $instance = new $chat_class($this, 0, $chat_options);
        if (!($instance instanceof TelegramBotChat)) {
            throw new Exception('ChatClass must be extends TelegramBotChat');
        }
        $this->chatClass = $chat_class;
        $this->chatOptions = $chat_options;
    }

    public function onUpdateReceived($update)
    {
        if ($update['message']) {
            $message = $update['message'];
            $chat_id = intval($message['chat']['id']);
            try {
                if ($chat_id) {
                    $chat = $this->getChatInstance($chat_id);
                    if (isset($message['group_chat_created'])) {
                        $chat->bot_added_to_chat($message);
                    } else if (isset($message['new_chat_participant'])) {
                        if ($message['new_chat_participant']['id'] == $this->botId) {
                            $chat->bot_added_to_chat($message);
                        }
                    } else if (isset($message['left_chat_participant'])) {
                        if ($message['left_chat_participant']['id'] == $this->botId) {
                            $chat->bot_kicked_from_chat($message);
                        }
                    } else {
                        $text = trim($message['text']);
                        $username = strtolower('@' . $this->botUsername);
                        $username_len = strlen($username);
                        if (strtolower(substr($text, 0, $username_len)) == $username) {
                            $text = trim(substr($text, $username_len));
                        }

                        $this->logger->debug('text for preg_match', ['text' => $text]);

                        if (preg_match('/^(?:\/([a-z0-9_]+)(@[a-z0-9_]+)?(?:\s+(.*))?)$/is', $text, $matches)) {
                            $command = $matches[1];
                            $command_owner = strtolower(@$matches[2]);
                            $command_params = @$matches[3];

                            $this->logger->debug('command params', [
                                'command' => $matches[1],
                                'command_owner' => $command_owner,
                                'command_params' => $command_params
                            ]);

                            if (!$command_owner || $command_owner == $username) {
                                $method = 'command_' . $command;
                                if (method_exists($chat, $method)) {
                                    $this->logger->debug("calling", ['method' => $method]);
                                    $chat->$method($command_params, $message);
                                } else {
                                    $this->logger->debug("calling", ['method' => 'some_command']);
                                    $chat->some_command($command, $command_params, $message);
                                }
                            }
                        } else {
                            $this->logger->debug("preg_match failed");

                            $chat->message($text, $message);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error("Exception", ['exception' => $e]);
            }
        }
    }

    public function getChatInstance($chat_id)
    {
        if (!isset($this->chatInstances[$chat_id])) {
            $instance = new $this->chatClass($this, $chat_id, $this->chatOptions);
            /* @var $instance TelegramBotChat */
            $instance->setLogger($this->logger);
            $this->chatInstances[$chat_id] = $instance;
            $instance->init();
        }
        return $this->chatInstances[$chat_id];
    }

}
