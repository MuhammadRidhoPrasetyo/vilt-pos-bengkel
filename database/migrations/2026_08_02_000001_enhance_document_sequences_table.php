<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->string('prefix')->nullable()->after('type');
            $table->string('format_pattern')->default('{STORE_CODE}/{PREFIX}/{YYYY}{MM}/{SEQ:4}')->after('prefix');
            $table->string('reset_frequency')->default('monthly')->after('format_pattern');
            $table->integer('day')->nullable()->after('year');
            $table->integer('month')->nullable()->after('day');
            $table->integer('padding')->default(4)->after('month');
        });
    }

    public function down(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropColumn(['prefix', 'format_pattern', 'reset_frequency', 'day', 'month', 'padding']);
        });
    }
};
