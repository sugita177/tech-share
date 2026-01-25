<?php

namespace App\Domain\Entities;

use DateTime;
use App\Domain\Enums\ArticleStatus;

readonly class Article
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public string $title,
        public string $slug,
        public string $content,
        public ArticleStatus $status,
        public int $viewCount = 0,
        public ?DateTime $createdAt = null,
        public ?DateTime $updatedAt = null,
    ) {}

    // ビジネスロジックをここに書くことができる
    // 例：公開済みかどうかを判定
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}