<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            // CB fields moved to admin enrich step — make nullable
            $table->enum('cb_person_default_on_file', ['Y', 'N'])->nullable()->change();
            $table->unsignedSmallInteger('cb_person_cred_hist_length')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->enum('cb_person_default_on_file', ['Y', 'N'])->nullable(false)->change();
            $table->unsignedSmallInteger('cb_person_cred_hist_length')->nullable(false)->change();
        });
    }
};
