<?php

namespace Tests\Feature\UseCases\Auth;

use App\Models\User;
use App\UseCases\Auth\LoginUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('execute: 正しいメールアドレスとパスワードでトークンが返されること', function () {
    // 1. 準備
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $useCase = new LoginUseCase();

    // 2. 実行
    $token = $useCase->execute('test@example.com', 'password123');

    // 3. 検証
    expect($token)->toBeString();
    expect($token)->not->toBeEmpty();
});

test('execute: パスワードが間違っている場合、ValidationExceptionを投げること', function () {
    // 1. 準備
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $useCase = new LoginUseCase();

    // 2. 実行 & 3. 検証
    expect(fn() => $useCase->execute('test@example.com', 'wrong-password'))
        ->toThrow(ValidationException::class);
});

test('execute: 存在しないメールアドレスの場合、ValidationExceptionを投げること', function () {
    $useCase = new LoginUseCase();

    expect(fn() => $useCase->execute('nonexistent@example.com', 'password123'))
        ->toThrow(ValidationException::class);
});