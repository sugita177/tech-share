<?php

namespace App\Enums;

enum PermissionType: string
{
    case EDIT_ANY_ARTICLE   = 'edit any article';
    case DELETE_ANY_ARTICLE = 'delete any article';
}
