<?php

namespace App\Providers;

use App\Services\LdapService;
use App\Services\DomPdfService;
use App\Services\XssCleanService;
use App\Interfaces\LdapInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Interfaces\SanitizerInterface;
use Illuminate\Support\ServiceProvider;
use App\Interfaces\PdfExporterInterface;


use App\Contracts\Repositories\DogRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\DogRepository;
use App\Repositories\UserRepository;

use App\Clients\SigaSoapPessoaClient;
use App\Clients\SigaSoapProfissionalClient;
use App\Contracts\Services\Siga\SigaPessoaServiceInterface;
use App\Contracts\Services\Siga\SigaProfissionalServiceInterface;
use App\Services\Siga\SigaPessoaService;
use App\Services\Siga\SigaProfissionalService;

use App\Contracts\Services\AcaoServiceInterface;
use App\Services\AcaoService;
use App\Contracts\Repositories\AcaoRepositoryInterface;
use App\Repositories\AcaoRepository;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->bind(SocialAuthInterface::class, GoogleAuthService::class);
        // $this->app->register(L5SwaggerServiceProvider::class);

        $this->app->bind(
            SanitizerInterface::class,
            XssCleanService::class
        );
        $this->app->bind(
            LdapInterface::class,
            LdapService::class
        );
        $this->app->bind(
            PdfExporterInterface::class,
            DomPdfService::class
        );
        $this->app->bind(
            DogRepositoryInterface::class,
            DogRepository::class
        );
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // WebService SIGA Saúde — busca de pessoa e profissional
        $this->app->bind(
            SigaPessoaServiceInterface::class,
            SigaPessoaService::class
        );
        $this->app->bind(
            SigaProfissionalServiceInterface::class,
            SigaProfissionalService::class
        );

        $this->app->singleton(SigaSoapPessoaClient::class, function () {
            return new SigaSoapPessoaClient(
                config('services.siga.url'),
                config('services.siga.username'),
                config('services.siga.password'),
                config('services.siga.sistema'),
            );
        });
        $this->app->singleton(SigaSoapProfissionalClient::class, function () {
            return new SigaSoapProfissionalClient(
                config('services.siga.url_profissional'),
                config('services.siga.username'),
                config('services.siga.password'),
                config('services.siga.sistema'),
            );
        });
        $this->app->bind(
            AcaoServiceInterface::class,
            AcaoService::class
        );
        $this->app->bind(
            AcaoRepositoryInterface::class,
            AcaoRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::pattern('id', '[0-9]+');
        // Gate para verificar se o usuário é um Administrador
        Gate::define('Gate-Admin', function ($user) {
            // return $user->hasRole(Roles::ADMIN->label());
        });
    }
}
