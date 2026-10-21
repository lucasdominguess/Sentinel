<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;
use Tests\TestCase;

class MakeDtoTest extends TestCase
{
    private string $testPath = 'DTOs/TestTemp';

    protected function tearDown(): void
    {
        File::deleteDirectory(app_path($this->testPath));
        parent::tearDown();
    }

    public function test_cria_dto_padrao_com_sufixo_e_from_request_usando_array()
    {
        $this->artisan('make:dto', ['name' => 'TestTemp/Auth'])
            ->expectsOutputToContain('gerada com sucesso')
            ->assertSuccessful();

        $filePath = app_path("{$this->testPath}/AuthDTO.php");
        
        $this->assertTrue(File::exists($filePath), 'O arquivo AuthDTO.php não foi criado.');
        
        $content = File::get($filePath);
        $this->assertStringContainsString('class AuthDTO', $content);
        $this->assertStringContainsString('fromRequest(array $data)', $content);
        $this->assertStringContainsString('namespace App\DTOs\TestTemp;', $content);
    }

    public function test_cria_classe_de_resposta_usando_flag_r()
    {
        $this->artisan('make:dto', [
            'name' => 'TestTemp/User',
            '--response' => true
        ])->assertExitCode(Command::SUCCESS);

        // Verifica se gerou UserResponseDTO.php
        $filePath = app_path("{$this->testPath}/UserResponseDTO.php");
        $this->assertTrue(File::exists($filePath), 'O arquivo UserResponseDTO.php não foi criado.');

        $content = File::get($filePath);
        $this->assertStringContainsString('class UserResponseDTO', $content);
        $this->assertStringContainsString('fromModel(Model $model)', $content);
    }

    public function test_cria_dto_de_array_usando_flag_a()
    {
        $this->artisan('make:dto', [
            'name' => 'TestTemp/Product',
            '--array' => true
        ])->assertExitCode(Command::SUCCESS);

        $filePath = app_path("{$this->testPath}/ProductDTO.php");
        $this->assertTrue(File::exists($filePath));

        $content = File::get($filePath);
        $this->assertStringContainsString('fromArray(array $data)', $content);
    }

    public function test_bloqueia_uso_simultaneo_de_flags_array_e_response()
    {
        $this->artisan('make:dto', [
            'name' => 'TestTemp/Conflict',
            '--array' => true,
            '--response' => true
        ])
        ->expectsOutput('A flag -A (--array) serve apenas para dados de entrada e não pode ser usada junto com -r (--response).')
        ->assertExitCode(Command::FAILURE);

        $this->assertFalse(File::exists(app_path("{$this->testPath}/ConflictDTO.php")));
        $this->assertFalse(File::exists(app_path("{$this->testPath}/ConflictResponseDTO.php")));
    }

    public function test_ignora_sufixos_quando_usar_flag_n()
    {
        $this->artisan('make:dto', [
            'name' => 'TestTemp/RelatorioCustomizado',
            '--no-suffix' => true
        ])->assertSuccessful();

        $filePath = app_path("{$this->testPath}/RelatorioCustomizado.php");
        
        $this->assertTrue(File::exists($filePath));
        
        $content = File::get($filePath);
        $this->assertStringContainsString('class RelatorioCustomizado', $content);
        $this->assertStringNotContainsString('class RelatorioCustomizadoDTO', $content);
        $this->assertStringNotContainsString('class RelatorioCustomizadoResponseDTO', $content);
    }

   public function test_corrige_nome_se_usuario_digitar_dto_com_flag_r()
    {
        // Se o dev digitar SetupDTO com a flag -r, deve gerar SetupResponseDTO
        $this->artisan('make:dto', [
            'name' => 'TestTemp/SetupDTO',
            '--response' => true
        ])->assertSuccessful();

        $filePath = app_path("{$this->testPath}/SetupResponseDTO.php");
        $this->assertTrue(File::exists($filePath));
        
        // E se digitar um NOME DIFERENTE (OutroSetupResponse) com a flag -r, também deve gerar certinho
        $this->artisan('make:dto', [
            'name' => 'TestTemp/OutroSetupResponse',
            '--response' => true
        ])->assertSuccessful();

        $filePath2 = app_path("{$this->testPath}/OutroSetupResponseDTO.php");
        $this->assertTrue(File::exists($filePath2));
    }

    public function test_nao_sobrescreve_classe_existente()
    {
        $this->artisan('make:dto', ['name' => 'TestTemp/Duplicate'])->assertSuccessful();

        $this->artisan('make:dto', ['name' => 'TestTemp/Duplicate'])
            ->expectsOutputToContain('já existe!')
            ->assertExitCode(Command::FAILURE); 
    }
}