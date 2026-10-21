<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CnpjValidate implements ValidationRule
{
       /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = preg_replace('/[^0-9]/is', '', (string) $value);

        if (strlen($cnpj) !== 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            $fail("O campo {$attribute} não é um CNPJ válido.");
            return;
        }

        for ($t = 12; $t < 14; $t++) {
            for ($d = 0, $m = ($t - 7), $i = 0; $i < $t; $i++) {
                $d += $cnpj[$i] * $m;
                $m = ($m == 2 ? 9 : --$m);
            }
            
            $d = ((10 * $d) % 11) % 10;
            
            if ($cnpj[$i] != $d) {
                $fail("O campo {$attribute} não é um CNPJ válido.");
                return;
            }
        }
    }
}
