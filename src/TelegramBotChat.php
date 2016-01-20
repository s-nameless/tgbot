<?php

namespace Tgbot;

use Exception;

abstract class TelegramBotChat
{

    protected $core;
    protected $chatId;
    protected $isGroup;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct($core, $chat_id, array $options = [])
    {
        if (!($core instanceof TelegramBot)) {
            throw new Exception('$core must be TelegramBot instance');
        }
        $this->core = $core;
        $this->chatId = $chat_id;
        $this->isGroup = $chat_id < 0;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function init()
    {
        
    }

    public function bot_added_to_chat($message)
    {

    }

    public function bot_kicked_from_chat($message)
    {
        
    }

//public function command_commandname($params, $message) {}
    public function some_command($command, $params, $message)
    {

    }

    public function message($text, $message)
    {
        
    }

    protected function apiForwardMesage($message, $params = [])
    {
        $params += array(
            'chat_id' => $this->chatId,
            'from_chat_id' => $message['chat']['id'],
            'message_id' => $message['message_id']
        );
        return $this->core->request('forwardMessage', $params);
    }

    protected function apiSendMessage($text, $params = array())
    {
        $params += array(
            'chat_id' => $this->chatId,
            'text' => $text,
        );
        return $this->core->request('sendMessage', $params);
    }

}
