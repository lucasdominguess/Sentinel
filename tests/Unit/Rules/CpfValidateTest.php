<?php

namespace Tests\Unit\Rules;

use App\Rules\CpfValidate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CpfValidateTest extends TestCase
{
    #[DataProvider('validCpfProvider')]
    public function test_cpf_valido($cpfValido)
    {
        $rule = new CpfValidate();
        $failed = false;

        $rule->validate('cpf', $cpfValido, function() use (&$failed){
            $failed = true;
        });

        $this->assertFalse($failed,'A regra não deveria falhar para um cpf valido.');
    }
    #[DataProvider('invalidCpfProvider')]
    public function test_cpf_invalido($cpfInvalido)
    {
        $rule = new CpfValidate();
        $failed = false;

        $rule->validate('cpf', $cpfInvalido, function() use (&$failed){
            $failed = true;
        });
        
        $this->assertTrue($failed, "o cpf {$cpfInvalido} deveria ter falhado, mas passou");
    }

    public static function validCpfProvider(): array
    {
        return [
            'com caracteres' => ['711.527.770-25'],
            'sem caracteres' => ['42001530099'],
        ];
    }
    public static function invalidCpfProvider(): array
    {
        return [
            'curto' => ['557598'], 
            'longo' => ['557598233414313531'], 
            'com letras' => ['cafe457812'],
        ];
    }
}
