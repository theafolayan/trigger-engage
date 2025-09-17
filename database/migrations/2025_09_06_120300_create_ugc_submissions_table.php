<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ugc_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ugc_task_id')->constrained('ugc_tasks')->cascadeOnDelete();
            $table->foreignId('ugc_application_id')->nullable()->constrained('ugc_applications')->nullOnDelete();
            $table->string('content_url');
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('submitted');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ugc_submissions');
    }
};
