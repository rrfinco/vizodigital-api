<?php

namespace App\Enums;

enum NavigationTargetType: string
{
    case Page = 'page';
    case Category = 'category';
    case Group = 'group';
    case Endpoint = 'endpoint';
    case Url = 'url';
    case Explorer = 'explorer';
}
