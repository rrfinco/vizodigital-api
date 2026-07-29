<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __construct(
        private readonly DocumentationRepositoryInterface $documentation,
    ) {}

    public function __invoke(): View
    {
        $version = null;

        try {
            $version = $this->documentation->defaultVersion();
            if ($version && ! $version->isPubliclyVisible()) {
                $version = $this->documentation->publishedVersions()->first();
            }
        } catch (\Throwable) {
            $version = null;
        }

        return view('landing.index', [
            'defaultVersion' => $version,
        ]);
    }
}
