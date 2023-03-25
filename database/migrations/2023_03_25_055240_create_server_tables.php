<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->ipAddress('ipv4')->nullable();
            $table->string('port')->nullable();
            $table->ipAddress('ipv6')->nullable();
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->json('config')->nullable();
            //SSH
            $table->string('ssh_host')->nullable();
            $table->string('ssh_port')->nullable();
            $table->string('ssh_user')->nullable();
            $table->string('ssh_password')->nullable();
            $table->string('ssh_key')->nullable();

            $table->enum('status',['UP','DOWN'])->default('DOWN');
            $table->string('token')->nullable();

            $table->boolean('agent')->default(false);
            $table->timestamps();
        });


        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->json('config')->nullable();
            $table->integer('port')->nullable();
            $table->foreignId('server_id')->constrained('servers');
            $table->ipAddress('in')->nullable();
            $table->timestamps();
        });

        Schema::create('proxy_group',function (Blueprint $table){
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('rules',function (Blueprint $table){
            $table->id();
            $table->string('content')->nullable();
            $table->string('type');
            $table->string('proxy_group')->default('Proxy');
            $table->boolean('resolve')->default(true);
//            $table->boolean('generate')->default(true);
            $table->timestamps();
        });
//        - 'GEOIP,CN,DIRECT'
//        - 'MATCH,NoMatch'


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
        Schema::dropIfExists('proxies');
        Schema::dropIfExists('proxy_group');
        Schema::dropIfExists('rules');
    }
};
