<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Article\CreateArticleRequest;
use App\UseCases\Article\CreateArticleUseCase;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function store(
        CreateArticleRequest $request, 
        CreateArticleUseCase $useCase
    ): JsonResponse {
        // FormRequest でバリデーション済みの DTO を取得
        $input = $request->toInput();

        // UseCase を実行
        $article = $useCase->execute($input);

        // クリーンアーキテクチャでは Entity をそのまま返さず、
        // 本来は等価な配列や Resource クラスに変換するのがベストです
        return response()->json([
            'message' => '記事を作成しました',
            'data' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'status' => $article->status,
            ]
        ], 201);
    }
}