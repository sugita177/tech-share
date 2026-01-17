<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\EloquentArticleRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            ArticleRepositoryInterface::class,
            EloquentArticleRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
