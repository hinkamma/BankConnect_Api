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
        Schema::create('scheduled_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_account_id')->constrained('accounts');
            $table->foreignId('receiver_account_id')->constrained('accounts');
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->date('scheduled_date');
            $table->enum('status', ['en_attente', 'executee', 'echouee', 'annulee'])
                  ->default('en_attente');
            $table->text('failure_reason')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_transfers');
    }
};
