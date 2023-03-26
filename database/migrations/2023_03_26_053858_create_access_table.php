<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('proxy_id')->constrained('proxies');
            $table->foreignId('server_id')->constrained('servers');
            $table->integer('port')->nullable();
            $table->string('type')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('domain', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->nullable();
            $table->string('plugin')->nullable();
            $table->json('config')->nullable();
            //Proxy Domain Random?
            $table->boolean('proxy')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access');
        Schema::dropIfExists('domain');
    }
};
