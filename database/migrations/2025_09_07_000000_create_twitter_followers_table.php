<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twitter_followers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('twitter_account_id')->constrained()->cascadeOnDelete();
            $table->string('follower_id');
            $table->string('username')->nullable();
            $table->string('name')->nullable();
            $table->timestamp('seen_at')->useCurrent();
            $table->timestamps();

            $table->unique(['twitter_account_id', 'follower_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twitter_followers');
    }
};
