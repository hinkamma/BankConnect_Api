<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->after('id');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->after('first_name');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 10)->after('email');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'client'])->default('client')->after('phone');
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['homme', 'femme', 'autre'])->nullable();
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->string('date_of_birth')->nullable();
            }
            if (!Schema::hasColumn('users', 'place_of_birth')) {
                $table->string('place_of_birth')->nullable();
            }
            if (!Schema::hasColumn('users', 'nationality')) {
                $table->string('nationality')->nullable();
            }
            if (!Schema::hasColumn('users', 'adress')) {
                $table->string('adress')->nullable();
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable();
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('users', 'profession')) {
                $table->string('profession')->nullable();
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Annulation si nécessaire
    }
};
