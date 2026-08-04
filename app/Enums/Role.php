<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';
    case Whitelabel = 'whitelabel';
    case Developer = 'developer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Editor => 'Editor',
            self::Viewer => 'Viewer',
            self::Whitelabel => 'White-label',
            self::Developer => 'Developer',
        };
    }
}
