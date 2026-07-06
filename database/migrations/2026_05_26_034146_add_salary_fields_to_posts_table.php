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
            $table->unsignedInteger('salary_min_amount')->nullable()->after('application_link');
            $table->unsignedInteger('salary_max_amount')->nullable()->after('salary_min_amount');
            $table->string('salary_currency', 3)->nullable()->after('salary_max_amount');
            $table->string('salary_period', 16)->nullable()->after('salary_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'salary_min_amount',
                'salary_max_amount',
                'salary_currency',
                'salary_period',
            ]);
        });
    }
};
