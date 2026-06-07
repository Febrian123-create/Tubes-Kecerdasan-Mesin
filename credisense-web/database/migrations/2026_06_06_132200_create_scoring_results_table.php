<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();

            $table->decimal('risk_score', 6, 4);
            $table->string('risk_category', 20);  // LOW / MEDIUM / HIGH / VERY_HIGH
            $table->string('model_used', 50);
            $table->json('feature_importances')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_results');
    }
};
