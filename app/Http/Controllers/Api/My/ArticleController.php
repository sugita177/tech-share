<?php

namespace App\Http\Controllers\Api\My;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ArticleResource;
use App\UseCases\Article\FetchMyArticlesUseCase;
use App\Domain\Enums\ArticleStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    public function index(Request $request, FetchMyArticlesUseCase $useCase): AnonymousResourceCollection
    {
        // クエリパラメータから status を取得（?status=draft など）
        $status = $request->query('status') 
            ? ArticleStatus::tryFrom($request->query('status')) 
            : null;

        // ログインユーザーのIDを渡して実行
        $articles = $useCase->execute(
            userId: $request->user()->id, 
            perPage: 10,
            status: $status
        );

        return ArticleResource::collection($articles);
    }
}