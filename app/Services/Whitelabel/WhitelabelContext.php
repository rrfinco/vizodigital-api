<?php

namespace App\Services\Whitelabel;

use App\Enums\WhitelabelStatus;
use App\Models\Whitelabel;
use App\Models\WhitelabelDomain;

class WhitelabelContext
{
    private ?Whitelabel $whitelabel = null;

    private bool $resolved = false;

    public function resolveFromHost(?string $host): self
    {
        $this->resolved = true;
        $this->whitelabel = null;

        $host = strtolower(trim((string) $host));
        if ($host === '') {
            return $this;
        }

        // Strip port if present (e.g. partner.test:8080)
        if (str_contains($host, ':')) {
            $host = explode(':', $host, 2)[0];
        }

        $domain = WhitelabelDomain::query()
            ->where('host', $host)
            ->with('whitelabel')
            ->first();

        if ($domain?->whitelabel) {
            $this->whitelabel = $domain->whitelabel;
        }

        return $this;
    }

    public function set(?Whitelabel $whitelabel): self
    {
        $this->resolved = true;
        $this->whitelabel = $whitelabel;

        return $this;
    }

    public function clear(): void
    {
        $this->resolved = false;
        $this->whitelabel = null;
    }

    public function whitelabel(): ?Whitelabel
    {
        return $this->whitelabel;
    }

    public function id(): ?int
    {
        return $this->whitelabel?->id;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function isActive(): bool
    {
        return $this->whitelabel?->isActive() ?? false;
    }

    public function displayName(): ?string
    {
        if (! $this->whitelabel) {
            return null;
        }

        return $this->whitelabel->brand_name ?: $this->whitelabel->name;
    }

    public function status(): ?WhitelabelStatus
    {
        return $this->whitelabel?->status;
    }
}
