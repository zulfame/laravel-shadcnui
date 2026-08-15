<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identitas pengguna: hanya nama (+peranan & kata sandi) yang wajib.
 * Nama pengguna, email, dan nomor HP menjadi opsional namun tetap unik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->string('username')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
