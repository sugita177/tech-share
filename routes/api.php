<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\My\ArticleController as MyArticleController;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// 公開ルート
Route::post('/login', [AuthController::class, 'login']);

// 認証必須ルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // --- 公開タイムライン用 (Published のみ) ---
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{slug}', [ArticleController::class, 'show']);

    // --- 記事の作成・更新・削除 (操作系) ---
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);

    // --- 自分の記事管理用 (My Workspace) ---
    Route::prefix('my')->group(function () {
        Route::get('/articles', [MyArticleController::class, 'index']);
    });
});
