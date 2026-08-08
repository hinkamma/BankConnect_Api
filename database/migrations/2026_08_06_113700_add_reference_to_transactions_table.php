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
        Schema::table('transactions', function (Blueprint $table) {
            // Ajoute la colonne 'reference' de type string, unique et la place après la colonne 'id'
            // ->nullable() permet d'éviter des erreurs si la table contient déjà des enregistrements
            $table->string('reference')->unique()->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Permet d'annuler la migration proprement si besoin
            $table->dropColumn('reference');
        });
    }
};
