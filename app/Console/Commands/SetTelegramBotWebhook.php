<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;

class SetTelegramBotWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'piejiang:set-telegrambot-webhook {certificate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Telegram Bot Webhook';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            //certificate
            if(env('TELEGRAMBOT_CERTIFICATE') != null || $this->argument('certificate') != null){
                $certificate = env('TELEGRAMBOT_CERTIFICATE') ?? $this->argument('certificate');
            }
            if(empty($certificate)){
                $telegram = new Telegram(env('TELEGRAMBOT_TOKEN'), env('TELEGRAMBOT_USERNAME'));
            }else{
                $telegram = new Telegram(env('TELEGRAMBOT_TOKEN'), env('TELEGRAMBOT_USERNAME'),[
                    'certificate' => $certificate,
                ]);
            }
            // Create Telegram API object
            // Set webhook
            $this->info('Setting webhook...');
            $this->info(route('telegram.bot.webhook'));
            $result = $telegram->setWebhook(route('telegram.bot.webhook'));
            if ($result->isOk()) {
                $this->info($result->getDescription());
                // echo $result->getDescription();
            }
        } catch (TelegramException $e) {
            // log telegram errors
            // echo $e->getMessage();
            $this->error($e->getMessage());
        }
    }
}
