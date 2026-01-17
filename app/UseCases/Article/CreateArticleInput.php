<?php

namespace App\UseCases\Article;

readonly class CreateArticleInput
{
    public function __construct(
        public int $userId,
        public string $title,
        public string $content,
        public ?string $slug = null,
        public string $status = 'draft',
    ) {}

    /**
     * ControllerからのRequestをDTOに変換する静的メソッド
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            title: $data['title'],
            content: $data['content'],
            slug: $data['slug'] ?? null,
            status: $data['status'] ?? 'draft',
        );
    }
}