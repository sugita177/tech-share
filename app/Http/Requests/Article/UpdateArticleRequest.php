<?php

namespace App\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateArticleRequest extends FormRequest
{
    /**
     * ログインしている場合のみ許可する
     */
    public function authorize(): bool
    {
        // auth:sanctum ミドルウェアで認証を保証しているため、ここでは認可のみを扱う
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'slug'    => ['nullable', 'string', 'max:255'],
            'status'  => ['required', 'in:draft,published'],
        ];
    }
}
