<?php

namespace Tests\Unit\Rules;

use App\Rules\CnpjValidate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CnpjValidateTest extends TestCase
{
    #[DataProvider('validCnpjProvider')]
    public function test_cnpj_valido($cnpjValido)
    {
        $rule = new CnpjValidate();
        $failed = false;

        $rule->validate('cnpj', $cnpjValido, function() use (&$failed){
            $failed = true;
        });

        $this->assertFalse($failed,'A regra não deveria falhar para um cnpj valido.');
    }
    #[DataProvider('invalidCnpjProvider')]
    public function test_cnpj_invalido($cnpjInvalido)
    {
        $rule = new CnpjValidate();
        $failed = false;

        $rule->validate('cnpj', $cnpjInvalido, function() use (&$failed){
            $failed = true;
        });
        
        $this->assertTrue($failed, "o cnpj {$cnpjInvalido} deveria ter falhado, mas passou");
    }
    public static function validCnpjProvider(): array
    {
        return[
            'com caracteres' => ['61.293.730/0001-12'],
            'sem caracteres' => ['47432166000140']
        ];
    }
    public static function invalidCnpjProvider(): array
    {
        return[
            'curto' => ['25458888'],
            'longo' => ['8487487848711578878748788718784'],
            'sem os caracteres corretos' => ['15$155#1548/0001-12'],
            'sem numeros' => ['camceninacanoancnanca22']
        ];
    }
    
}
