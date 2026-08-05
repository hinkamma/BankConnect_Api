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
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            
            // Lien avec l'utilisateur
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Adresse IP de la connexion (ex: 192.168.1.1)
            $table->string('ip_address')->nullable();
            
            // Signature de l'appareil / navigateur (User-Agent)
            $table->text('user_agent')->nullable();
            
            // Date et heure de connexion
            $table->timestamp('login_at');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
