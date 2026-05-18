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
        Schema::create('monitor_check_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained('monitors')->cascadeOnDelete();
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->boolean('is_up');
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['monitor_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_check_histories');
    }
};
