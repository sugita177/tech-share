<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Article\CreateArticleRequest;
use App\Http\Resources\Api\ArticleResource;
use App\UseCases\Article\CreateArticleUseCase;
use App\UseCases\Article\FetchArticlesUseCase;
use App\UseCases\Article\FindArticleBySlugUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    public function index(FetchArticlesUseCase $useCase): AnonymousResourceCollection
    {
        // 1ページ10件で取得
        $articles = $useCase->execute(10);

        // JsonResource::collection にパジネーターを渡すと、
        // 自動的に meta キーや links キーがレスポンスに追加されます
        return ArticleResource::collection($articles);
    }

    public function show(string $slug, FindArticleBySlugUseCase $useCase): ArticleResource
    {
        $article = $useCase->execute($slug);

        return new ArticleResource($article);
    }
}