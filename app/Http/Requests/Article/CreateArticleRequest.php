<?php

namespace App\Http\Requests\Article;

use App\UseCases\Article\CreateArticleInput;
use App\Domain\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateArticleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'slug'    => 'nullable|alpha_num|max:20|unique:articles,slug',
            'status'  => [new Enum(ArticleStatus::class)],
        ];
    }

    public function toInput(): CreateArticleInput
    {
        // 文字列から Enum への変換。指定がなければ Published（または Draft）をデフォルトにする
        $status = ArticleStatus::tryFrom($this->validated('status')) ?? ArticleStatus::Published;

        return new CreateArticleInput(
            userId: $this->user()?->id ?? 1, // 認証未実装なら一旦1
            title: $this->validated('title'),
            content: $this->validated('content'),
            slug: $this->validated('slug'), // nullもあり得る
            status: $status
        );
    }
}