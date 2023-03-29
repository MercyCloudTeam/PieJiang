<?php


namespace App\Http\Controllers\TelegramBot\Commands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class HiCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'hi';

    /**
     * @var string
     */
    protected $description = 'hi command';

    /**
     * @var string
     */
    protected $usage = '/hi';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        // If you use deep-linking, get the parameter like this:
        // $deep_linking_parameter = $this->getMessage()->getText(true);

        return $this->replyToChat(
            'Hi there!'  . PHP_EOL
        );
    }
}
