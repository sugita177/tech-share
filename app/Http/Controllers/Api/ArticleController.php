<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Article\CreateArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;
use App\Http\Resources\Api\ArticleResource;
use App\UseCases\Article\CreateArticleUseCase;
use App\UseCases\Article\UpdateArticleUseCase;
use App\UseCases\Article\FetchArticlesUseCase;
use App\UseCases\Article\FindArticleBySlugUseCase;
use App\UseCases\Article\DeleteArticleUseCase;
use App\UseCases\Article\UpdateArticleInput;
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

    public function update(UpdateArticleRequest $request, int $id, UpdateArticleUseCase $useCase): ArticleResource
    {
        $input = new UpdateArticleInput(
            id: $id,
            userId: $request->user()->id,
            title: $request->input('title'),
            content: $request->input('content'),
            slug: $request->input('slug'),
            status: $request->input('status')
        );

        $article = $useCase->execute($input);

        return new ArticleResource($article);
    }

    public function destroy(Request $request,int $id, DeleteArticleUseCase $useCase): \Illuminate\Http\Response
    {
        $useCase->execute($id, $request->user()->id);

        // 成功時は 204 No Content を返すのが一般的です
        return response()->noContent();
    }
}