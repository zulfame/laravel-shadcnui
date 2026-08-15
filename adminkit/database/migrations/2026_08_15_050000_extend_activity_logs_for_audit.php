<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Audit trail: perubahan data (diff), konteks permintaan, dan detail kesalahan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->json('changes')->nullable()->after('subject_id');
            $table->json('context')->nullable()->after('changes');
            $table->string('method', 10)->nullable()->after('ip');
            $table->string('url', 500)->nullable()->after('method');
            $table->unsignedSmallInteger('status_code')->nullable()->after('url');
            $table->string('user_agent', 500)->nullable()->after('status_code');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['changes', 'context', 'method', 'url', 'status_code', 'user_agent']);
        });
    }
};
