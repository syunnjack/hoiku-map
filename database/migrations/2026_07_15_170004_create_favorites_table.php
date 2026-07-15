<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_user_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            // タイムスタンプではなくvacancy_reports.idを検知カーソルに使う。
            // created_atは秒精度のため、同一秒内に複数件投稿されると取りこぼす恐れがあるため。
            $table->unsignedBigInteger('last_checked_report_id')->nullable();
            $table->timestamps();

            $table->unique(['line_user_id', 'venue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
