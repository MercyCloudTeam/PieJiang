<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Request as TelegramRequest;

class TelegramController extends Controller
{
    public function botWebhook(Request $request)
    {
        try {
            // Create Telegram API object
            $telegram = new Telegram(env('TELEGRAMBOT_TOKEN'), env('TELEGRAMBOT_USERNAME'));
            //Deepl
            $commandsPaths = [
                app_path('Http/Controllers/TelegramBot/Commands/'),
            ];

            $telegram->addCommandsPaths($commandsPaths);
            $telegram->enableLimiter([
                'enabled' => true
            ]);
            if(env('TELEGRAMBOT_ADMIN_ID') != null){
                $telegram->enableAdmins([
                    env('TELEGRAMBOT_ADMIN_ID')
                ]);
            }

            // Handle telegram webhook request
            $telegram->handle();


            // //say hi
            // $data = [
            //     'chat_id' => $request->input('message.chat.id'),
            //     'text' => 'Hi, ' . $request->input('message.from.first_name') . ' ' . $request->input('message.from.last_name'),
            // ];
            // $request = TelegramRequest::sendMessage($data);
            // if ($request->isOk()) {
            //     Log::info($request->getDescription());
            //     Log::info(app_path('Http/Controllers/TelegramBot/Command'));
            // } else {
            //     Log::error($request->getDescription());
            // }

        } catch (TelegramException $e) {
            // Silence is golden!
            // log telegram errors
            // echo $e->getMessage();
            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }



    }
}
