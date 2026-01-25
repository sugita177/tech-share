<?php

namespace App\Domain\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}