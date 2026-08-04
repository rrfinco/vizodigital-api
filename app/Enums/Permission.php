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
    case PlansManage = 'plans.manage';
    case DepositsManage = 'deposits.manage';

    case ApiKeysManage = 'api-keys.manage';
    case KycManage = 'kyc.manage';
    case AnalyticsView = 'analytics.view';

    case WhitelabelsManage = 'whitelabels.manage';
    case WhitelabelFloatManage = 'whitelabel-float.manage';
    case WhitelabelKycManage = 'whitelabel-kyc.manage';
    case WhitelabelFloatRequest = 'whitelabel-float.request';
    case WhitelabelCommissionsManage = 'whitelabel-commissions.manage';

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
            self::PlansManage,
            self::DepositsManage,
            self::ApiKeysManage,
            self::KycManage,
            self::AnalyticsView,
            self::WhitelabelsManage,
            self::WhitelabelFloatManage,
        ];
    }

    /**
     * @return list<self>
     */
    public static function whitelabelPermissions(): array
    {
        return [
            self::WhitelabelKycManage,
            self::WhitelabelFloatRequest,
            self::WhitelabelCommissionsManage,
            self::ApiKeysManage,
        ];
    }
}
