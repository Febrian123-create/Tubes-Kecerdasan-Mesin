<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();

            $table->text('summary');
            $table->text('approval_reasons')->nullable();
            $table->json('payment_plan')->nullable();   // monthly installments array
            $table->text('risk_notes')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('model_used', 80)->nullable();
            $table->unsignedInteger('tokens_used')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_insights');
    }
};
