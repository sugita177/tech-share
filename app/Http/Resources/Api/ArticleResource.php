<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this はコントローラーから渡された $article (Entity) を指します
        return [
            'id'     => $this->id,
            'title'  => $this->title,
            'slug'   => $this->slug,
            'status' => $this->status,
            // 以前のエラーで追加した view_count などもここでフォーマット可能
            'views'  => $this->viewCount ?? 0, 
        ];
    }
}
