<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();

            $table->enum('decision', ['APPROVED', 'DECLINED', 'REVIEW']);
            $table->unsignedBigInteger('approved_amount')->nullable();
            $table->string('conditions')->nullable();
            $table->string('decline_reason')->nullable();
            $table->json('rules_triggered')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_decisions');
    }
};
