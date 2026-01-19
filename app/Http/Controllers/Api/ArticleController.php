<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Article\CreateArticleRequest;
use App\Http\Resources\Api\ArticleResource;
use App\UseCases\Article\CreateArticleUseCase;
use App\UseCases\Article\FetchArticlesUseCase;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function store(
        CreateArticleRequest $request, 
        CreateArticleUseCase $useCase
    ): JsonResponse {
        // 1. バリデーション済みの入力を取得
        $input = $request->toInput();

        // 2. UseCase を実行（Entity が返ってくる）
        $article = $useCase->execute($input);

        // 3. Resource を使ってレスポンスを生成
        // additional() を使うと message などの付加情報を追加できます
        return (new ArticleResource($article))
            ->additional(['message' => '記事を作成しました'])
            ->response()
            ->setStatusCode(201);
    }

    public function index(FetchArticlesUseCase $useCase): JsonResponse
    {
        $articles = $useCase->execute();

        // Resource::collection() で配列をラップして返却
        return ArticleResource::collection($articles)
            ->response()
            ->setStatusCode(200);
    }
}