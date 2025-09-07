<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('emails_sent')->default(0);
            $table->unsignedInteger('events_ingested')->default(0);
            $table->unsignedInteger('contacts_created')->default(0);
            $table->timestamps();
            $table->unique(['workspace_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
