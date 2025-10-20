<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('twitter_accounts', function (Blueprint $table): void {
            $table->timestamp('last_polled_at')->nullable()->after('disconnected_at');
        });
    }

    public function down(): void
    {
        Schema::table('twitter_accounts', function (Blueprint $table): void {
            $table->dropColumn('last_polled_at');
        });
    }
};
