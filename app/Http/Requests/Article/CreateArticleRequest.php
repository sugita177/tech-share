<?php

namespace App\Http\Requests\Article;

use App\UseCases\Article\CreateArticleInput;
use Illuminate\Foundation\Http\FormRequest;

class CreateArticleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'slug'    => 'nullable|alpha_num|max:20|unique:articles,slug',
            'status'  => 'in:draft,published',
        ];
    }

    public function toInput(): CreateArticleInput
    {
        return new CreateArticleInput(
            userId: $this->user()?->id ?? 1, // 認証未実装なら一旦1
            title: $this->validated('title'),
            content: $this->validated('content'),
            slug: $this->validated('slug'), // nullもあり得る
            status: $this->validated('status', 'draft')
        );
    }
}