<?php

namespace App\Enums;

enum RoleType: string
{
    case ADMIN = 'admin';
    case USER  = 'user';
    case EDITOR = 'editor';
}