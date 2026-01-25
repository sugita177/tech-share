<?php

namespace App\Models;

use App\Domain\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'user_id',
    ];

    protected $casts = [
        // これを書くと $model->status が自動的に Enum オブジェクトになる
        'status' => ArticleStatus::class,
    ];
}
