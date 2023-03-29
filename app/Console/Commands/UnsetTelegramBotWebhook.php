<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;

class UnsetTelegramBotWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'piejiang:unset-telegrambot-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unset Telegram Bot Webhook';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            // Create Telegram API object
            $telegram = new Telegram(env('TELEGRAMBOT_TOKEN'), env('TELEGRAMBOT_USERNAME'));

            // Unset / delete the webhook
            $result = $telegram->deleteWebhook();
            $this->info($result->getDescription());
            // echo $result->getDescription();
        } catch (TelegramException $e) {
            // echo $e->getMessage();
            $this->error($e->getMessage());
        }
    }
}
