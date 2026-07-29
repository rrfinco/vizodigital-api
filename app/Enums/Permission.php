<?php

namespace App\Enums;

enum Permission: string
{
    case DocsViewAdmin = 'docs.view_admin';
    case DocsCreate = 'docs.create';
    case DocsUpdate = 'docs.update';
    case DocsPublish = 'docs.publish';
    case DocsDelete = 'docs.delete';
    case DocsPreview = 'docs.preview';

    case NavManage = 'nav.manage';
    case VersionsManage = 'versions.manage';
    case EnvironmentsManage = 'environments.manage';

    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case SettingsManage = 'settings.manage';

    case ApiKeysManage = 'api-keys.manage';
    case KycManage = 'kyc.manage';
    case AnalyticsView = 'analytics.view';

    public function label(): string
    {
        return str_replace(['.', '_'], [' ', ' '], $this->value);
    }

    /**
     * @return list<self>
     */
    public static function staffPermissions(): array
    {
        return [
            self::DocsViewAdmin,
            self::DocsCreate,
            self::DocsUpdate,
            self::DocsPublish,
            self::DocsDelete,
            self::DocsPreview,
            self::NavManage,
            self::VersionsManage,
            self::EnvironmentsManage,
            self::UsersManage,
            self::RolesManage,
            self::SettingsManage,
            self::ApiKeysManage,
            self::KycManage,
            self::AnalyticsView,
        ];
    }
}
