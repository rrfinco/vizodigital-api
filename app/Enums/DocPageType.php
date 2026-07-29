<?php

namespace App\Enums;

enum DocPageType: string
{
    case Overview = 'overview';
    case GettingStarted = 'getting_started';
    case Authentication = 'authentication';
    case Guide = 'guide';
    case Faq = 'faq';
    case Errors = 'errors';
    case RateLimits = 'rate_limits';
    case Webhooks = 'webhooks';
    case Sdk = 'sdk';
    case Support = 'support';
    case GoLive = 'go_live';
    case Changelog = 'changelog';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::GettingStarted => 'Getting Started',
            self::RateLimits => 'Rate Limits',
            self::GoLive => 'Go Live',
            default => str_replace('_', ' ', ucfirst($this->value)),
        };
    }
}
