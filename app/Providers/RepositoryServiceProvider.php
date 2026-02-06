<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Domain\Interfaces\TransactionManagerInterface;
use App\Infrastructure\Persistence\DataBaseTransactionManager;
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

        // singleton にすることで、リクエスト中一つのインスタンスを使い回す（効率的）
        $this->app->singleton(
            TransactionManagerInterface::class,
            DataBaseTransactionManager::class
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
