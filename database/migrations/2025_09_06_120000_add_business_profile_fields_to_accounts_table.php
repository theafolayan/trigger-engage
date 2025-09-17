<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->string('slug')->after('name');
            $table->string('website_url')->nullable()->after('slug');
            $table->string('support_email')->nullable()->after('website_url');
            $table->string('support_phone')->nullable()->after('support_email');
            $table->text('description')->nullable()->after('support_phone');
            $table->string('logo_url')->nullable()->after('description');

            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug',
                'website_url',
                'support_email',
                'support_phone',
                'description',
                'logo_url',
            ]);
        });
    }
};
