<?php

namespace App\Http\Requests\Article;

use App\UseCases\Article\CreateArticleInput;
use App\Domain\Enums\ArticleStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Auth;

class CreateArticleRequest extends FormRequest
{
    /**
     * ログインしている場合のみ許可する
     */
    public function authorize(): bool
    {
        // auth:sanctum ミドルウェアで認証を保証しているため、ここでは認可のみを扱う
        return true;
    }

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
        /** @var User $user */
        $user = $this->user();

        // 文字列から Enum への変換。指定がなければ Publishedをデフォルトにする
        $status = ArticleStatus::tryFrom($this->validated('status')) ?? ArticleStatus::Published;

        return new CreateArticleInput(
            userId: $this->user()->id,
            title: $this->validated('title'),
            content: $this->validated('content'),
            status: $status,
            slug: $this->validated('slug') // nullもあり得る
        );
    }
}