<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Interfaces\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

class DataBaseTransactionManager implements TransactionManagerInterface
{
    public function run(callable $callback): mixed
    {
        // LaravelのDBトランザクション機能に委ねる
        return DB::transaction($callback);
    }
}