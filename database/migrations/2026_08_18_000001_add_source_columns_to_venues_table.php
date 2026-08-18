<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 外部データ（OpenStreetMap）から取り込んだ施設と、利用者が投稿した施設を
// 区別できるようにする。再取り込みしたときに重複させないための鍵も兼ねる。
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->after('likes_count');
            $table->string('source_ref')->nullable()->after('source');
            $table->string('website')->nullable()->after('phone');
            $table->string('opening_hours')->nullable()->after('website');

            $table->unique(['source', 'source_ref']);
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropUnique(['source', 'source_ref']);
            $table->dropColumn(['source', 'source_ref', 'website', 'opening_hours']);
        });
    }
};
