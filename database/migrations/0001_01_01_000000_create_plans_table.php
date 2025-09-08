<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('features');
            $table->unsignedInteger('email_quota')->nullable();
            $table->unsignedInteger('event_quota')->nullable();
            $table->unsignedInteger('contact_quota')->nullable();
            $table->timestamps();
        });

        DB::table('plans')->insert([
            ['name' => 'Free', 'features' => json_encode([]), 'email_quota' => 2000, 'event_quota' => 10000, 'contact_quota' => 1000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pro', 'features' => json_encode([]), 'email_quota' => 100000, 'event_quota' => 100000, 'contact_quota' => 10000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Enterprise', 'features' => json_encode(['visual_builder' => true]), 'email_quota' => null, 'event_quota' => null, 'contact_quota' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
