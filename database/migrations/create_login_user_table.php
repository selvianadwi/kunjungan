<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_user', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
        });

        // Insert user default
        DB::table('login_user')->insert([
            'username' => 'admin',
            'password' => Hash::make('admin111'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('login_user');
    }
};
