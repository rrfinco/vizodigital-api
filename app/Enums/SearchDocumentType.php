<?php

namespace App\Enums;

enum SearchDocumentType: string
{
    case Endpoint = 'endpoint';
    case Page = 'page';
    case Category = 'category';
    case Group = 'group';
    case Faq = 'faq';
    case Changelog = 'changelog';

    public function label(): string
    {
        return match ($this) {
            self::Endpoint => 'Endpoint',
            self::Page => 'Page',
            self::Category => 'Category',
            self::Group => 'Group',
            self::Faq => 'FAQ',
            self::Changelog => 'Changelog',
        };
    }
}
