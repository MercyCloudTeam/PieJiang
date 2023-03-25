<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Psy\Util\Str;

class GenerateUserToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'piejiang:generate-user-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate User Token';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //get user
        if (!empty($this->argument('id'))) {
            $user = User::find($this->argument('id'));
        } else {
            $id = $this->ask('What is your id?');
            $user = User::find($id);
            if (empty($user)) {
                $this->error('User not found');
                return;
            }
        }

        //generate token
        $token = Str::random(32);
        $user->token = $token;
        $user->save();
        $this->info('User token generated successfully');
        $this->info('User ID: '.$user->id);
        $this->info('User Name: '.$user->name);
        $this->info('User Email: '.$user->email);
        $this->info('User Token: '.$user->token);
    }
}
