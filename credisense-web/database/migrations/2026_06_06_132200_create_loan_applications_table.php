<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Borrower-supplied fields
            $table->unsignedSmallInteger('person_age');
            $table->unsignedBigInteger('person_income');
            $table->enum('person_home_ownership', ['RENT', 'OWN', 'MORTGAGE', 'OTHER']);
            $table->decimal('person_emp_length', 4, 1)->nullable();
            $table->enum('loan_intent', ['PERSONAL', 'EDUCATION', 'MEDICAL', 'VENTURE', 'HOMEIMPROVEMENT', 'DEBTCONSOLIDATION']);
            $table->unsignedBigInteger('loan_amnt');
            $table->decimal('loan_int_rate', 5, 2);
            $table->enum('cb_person_default_on_file', ['Y', 'N']);
            $table->unsignedSmallInteger('cb_person_cred_hist_length');

            // Admin-enriched fields (OJK / credit bureau)
            $table->string('loan_grade', 1)->nullable();  // A-G from OJK
            $table->decimal('loan_percent_income', 5, 4)->nullable();

            $table->enum('status', ['pending', 'enriched', 'scored', 'approved', 'rejected', 'review'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
