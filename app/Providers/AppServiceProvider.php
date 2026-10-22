<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Fdr\FdrParser;
use App\Services\Fdr\NodeCliFdrParser;
use App\Services\Fdr\NodeHttpFdrParser;
use App\Support\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // FDR_PARSER_DRIVER=cli|http decides how Laravel talks to the Node.js parser.
        $this->app->bind(FdrParser::class, fn () => match (config('ids.fdr.driver')) {
            'cli' => new NodeCliFdrParser,
            'http' => new NodeHttpFdrParser,
            default => throw new InvalidArgumentException('FDR_PARSER_DRIVER must be "cli" or "http".'),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Outside production, throw on N+1 queries (lazy loading in a loop) and on
        // saving attributes missing from #[Fillable], instead of silently doing the
        // wrong thing. Makes bugs easy to spot.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        $this->registerPermissions();
    }

    /**
     * Every key in App\Support\Permission becomes a gate.
     * Super admins pass every check.
     */
    private function registerPermissions(): void
    {
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        foreach (Permission::all() as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
    }
}
