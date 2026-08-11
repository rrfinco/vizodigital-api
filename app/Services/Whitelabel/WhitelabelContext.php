<?php

namespace App\Services\Whitelabel;

use App\Models\Whitelabel;
use App\Models\WhitelabelDomain;

class WhitelabelContext
{
    private ?Whitelabel $whitelabel = null;

    public function resolveFromHost(?string $host): self
    {
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
            ->with(['whitelabel.domains'])
            ->first();

        if ($domain?->whitelabel) {
            $this->whitelabel = $domain->whitelabel;
        }

        return $this;
    }

    public function whitelabel(): ?Whitelabel
    {
        return $this->whitelabel;
    }

    public function id(): ?int
    {
        return $this->whitelabel?->id;
    }

    public function isActive(): bool
    {
        return $this->whitelabel?->isActive() ?? false;
    }
}
