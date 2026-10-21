<?php

namespace Tests\Unit\Rules;

use App\Rules\PasswordValidate;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PasswordValidateTest extends TestCase
{
    #[DataProvider('validPasswordsProvider')]
    public function test_senha_valido_passa_na_regra($senhasValida)
    {
        $rule = new PasswordValidate();
        $failed = false;

        $rule->validate('password', $senhasValida, function() use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed,'A regra não deveria falhar para uma senha válida.');
    }
    #[DataProvider('invalidPasswordsProvider')]
    public function test_senhas_invalidas_falham_na_regra($senhaInvalida)
    {
        $rule = new PasswordValidate();
        $failed = false;
        
        $rule->validate('password', $senhaInvalida, function() use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, "A senha {$senhaInvalida} deveria ter falhado, mas passou.");
    }

    public static function validPasswordsProvider(): array
    {
        return [
            'exemplo1' => ['Testsenha173&@'], 
            'exemplo2' => ['478Testsenha**'],
            'exemplo3' => ['test@999Senha']
        ];
    }

    public static function invalidPasswordsProvider(): array
    {
        return [
            'Curta' => ['Senha@1'],                 // Menos de 8 caracteres
            'Sem Maiúscula' => ['senhaforte@123'],  // Sem A-Z
            'Sem Minúscula' => ['SENHAFORTE@123'],  // Sem a-z
            'Sem Número' => ['SenhaForte@!'],       // Sem 0-9
            'Sem Especial' => ['SenhaForte123'],    // Sem especial
        ];
    }


}
