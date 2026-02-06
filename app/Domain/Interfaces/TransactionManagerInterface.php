<?php

namespace App\Domain\Interfaces;

/**
 * トランザクションという「場の状態」を管理するインターフェース
 */
interface TransactionManagerInterface
{
    /**
     * 一連の処理を原子的な操作として実行する
     * * @template T
     * @param callable(): T $callback
     * @return T
     * @throws \Throwable
     */
    public function run(callable $callback): mixed;
}