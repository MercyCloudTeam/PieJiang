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
        Schema::table('servers', function (Blueprint $table) {
            $table->ipAddress('listen')->nullable();
        });

        Schema::table('proxies', function (Blueprint $table) {

        });

        Schema::table('proxy_group', function (Blueprint $table) {
            $table->foreignId('default_proxy_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
