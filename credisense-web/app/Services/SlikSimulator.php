<?php

namespace App\Services;

use App\Models\LoanApplication;

/**
 * Simulates a SLIK OJK bureau check for demo purposes — in production this
 * data would come from an external credit bureau lookup, not be guessable
 * by the borrower.
 */
class SlikSimulator
{
    public function check(LoanApplication $app): array
    {
        $maxHistory = max(0, min((int) $app->person_age - 18, 25));

        return [
            'cb_person_default_on_file'  => random_int(1, 100) <= 20 ? 'Y' : 'N',
            'cb_person_cred_hist_length' => random_int(0, $maxHistory),
        ];
    }
}
