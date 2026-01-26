<?php

namespace App\UseCases\Article;

use App\Domain\Enums\ArticleStatus;

class UpdateArticleInput
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $title,
        public readonly string $content,
        public readonly ?string $slug,
        public readonly ArticleStatus $status
    ) {}
}