<?php

namespace App\Http\Controllers\TelegramBot\Commands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CertCommand extends UserCommand
{
    protected $name = 'cert';                      // Your command's name
    protected $description = 'Cert'; // Your command description
    protected $usage = '/cert';                    // Usage of your command
    protected $version = '1.0.0';                  // Version of your command


    public function execute(): ServerResponse
    {
        $message = $this->getMessage();            // Get Message object

        $chat_id = $message->getChat()->getId();   // Get the current Chat ID

        //have reply?
        if ($message->getReplyToMessage()) {
            $cert = $message->getReplyToMessage()->getText();
        } else {
            $text = null;
            $data = [                                  // Set up the new message data
                'chat_id' => $chat_id,                 // Set Chat ID to send the message to
                'text'    =>  "Please reply a message to translate.", // Set message to send
            ];
            return Request::sendMessage($data);        // Send message!
        }

        try {
            // Log::info($lang, [$text]);
            //decode cert
            $cert = openssl_x509_parse($cert);
            $res = implode("\n", $cert);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $res = "Error:".$e->getMessage().", Please try again later.";
        }

        $data = [                                  // Set up the new message data
            'chat_id' => $chat_id,                 // Set Chat ID to send the message to
            'text'    => $res, // Set message to send
        ];

        return Request::sendMessage($data);        // Send message!
    }

}
