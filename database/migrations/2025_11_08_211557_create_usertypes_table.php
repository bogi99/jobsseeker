<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usertypes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default user types
        DB::table('usertypes')->insert([
            [
                'name' => 'superadmin',
                'description' => 'Super Administrator with full system access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'description' => 'Administrator with limited system access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'customer',
                'description' => 'Customer user type',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'jobseeker',
                'description' => 'Job seeker looking for employment',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usertypes');
    }
};
