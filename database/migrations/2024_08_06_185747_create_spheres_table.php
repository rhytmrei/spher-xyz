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
        Schema::create('spheres', function (Blueprint $table) {
            $table->uuid('id')->unique()->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('title', 50);
            $table->text('description', 255)->nullable();
            $table->jsonb('texture_colors')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spheres');
    }
};
