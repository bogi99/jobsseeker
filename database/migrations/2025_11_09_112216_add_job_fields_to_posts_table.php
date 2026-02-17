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
        Schema::table('posts', function (Blueprint $table) {
            // `full_content` is added by a later migration — position after `content`
            // so this migration can run regardless of `full_content` ordering.
            $table->string('company_name')->default('Unknown Company')->after('content');
            $table->string('company_logo')->nullable()->after('company_name');
            $table->string('application_link')->nullable()->after('company_logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_logo',
                'application_link',
            ]);
        });
    }
};
