<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'piejiang:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Piejiang Create User';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Piejiang Create User');
        //Get the name of the user
        $name = $this->ask('What is your name?');
        //Get the email of the user
        $email = $this->ask('What is your email?');
        //Get the password of the user
        $password = $this->ask('What is your password?');
        //Create the user

        $token = Str::random(32);
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'token' => $token,
        ]);
        $this->info('User created successfully');
        $this->info('User ID: '.$user->id);
        $this->info('User Name: '.$user->name);
        $this->info('User Email: '.$user->email);
        $this->info('User Token: '.$user->token);
    }
}
