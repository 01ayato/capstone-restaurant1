<?php

namespace App\Providers;

use App\Models\Module;
use App\Models\Page;
use App\Observers\ModuleObserver;
use App\Observers\PageObserver;
use App\Services\TableSessionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo', function (array $config = []) {
            $key = $config['key'] ?? config('services.brevo.key');

            return (new BrevoTransportFactory)->create(
                new Dsn('brevo+api', 'default', $key)
            );
        });

        Module::observe(ModuleObserver::class);
        Page::observe(PageObserver::class);

        View::composer('layouts.partials.sidebar', function ($view): void {
            $modules = Module::query()
                ->where('is_active', true)
                ->with([
                    'pages' => fn ($q) => $q
                        ->where('is_active', true)
                        ->orderBy('sequence_number')
                        ->orderBy('id'),
                    'pages.roles',
                    'roles',
                ])
                ->orderBy('sequence')
                ->orderBy('id')
                ->get();

            $user = Auth::user();
            if ($user !== null && ! $user->isSuperadmin()) {
                $modules = $modules
                    ->filter(function (Module $module) use ($user) {
                        if ($module->roles->isEmpty()) {
                            return false;
                        }
                        if ($user->role_id === null) {
                            return false;
                        }

                        return $module->roles->contains(
                            fn ($role) => (int) $role->id === (int) $user->role_id
                        );
                    })
                    ->values();

                $modules = $modules
                    ->map(function (Module $module) use ($user) {
                        $filteredPages = $module->pages->filter(function (Page $page) use ($user) {
                            if ($page->roles->isEmpty()) {
                                return false;
                            }
                            if ($user->role_id === null) {
                                return false;
                            }

                            return $page->roles->contains(
                                fn ($role) => (int) $role->id === (int) $user->role_id
                            );
                        })->values();
                        $module->setRelation('pages', $filteredPages);

                        return $module;
                    })
                    ->values();
            }

            $modules = $modules
                ->filter(function (Module $module) {
                    $hasRoutablePage = $module->pages->contains(
                        fn (Page $page) => Route::has('page.'.$module->alias.'.'.$page->page_alias.'.index')
                    );

                    return $hasRoutablePage || Route::has('module.'.$module->alias.'.index');
                })
                ->values();

            $view->with('sidebarModules', $modules);
        });

        View::composer(['layouts.portal', 'portal.*', 'portal.partials.navbar'], function ($view): void {
            $tableSession = app(TableSessionService::class);

            $view->with([
                'currentTable' => $tableSession->currentTablePayload(),
                'tableLocation' => $tableSession->locationLabel(),
            ]);
        });
    }
}
