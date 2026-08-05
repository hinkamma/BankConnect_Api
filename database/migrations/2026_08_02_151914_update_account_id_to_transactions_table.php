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
            $table->foreignId('sender_account_id')->nullable()->after('id')->constrained('accounts')->nullOnDelete();
            $table->foreignId('receiver_account_id')->nullable()->after('sender_account_id')->constrained('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['sender_account_id']);
            $table->dropForeign(['receiver_account_id']);
            $table->dropColumn(['sender_account_id','receiver_account_id']);
        });
    }
};
