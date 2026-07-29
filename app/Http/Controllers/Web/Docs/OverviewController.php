<?php

namespace App\Http\Controllers\Web\Docs;

use App\Enums\DocPageType;
use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use App\Services\Portal\PortalContext;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __construct(
        private readonly PortalContext $portal,
    ) {}

    public function __invoke(): View
    {
        $version = $this->portal->version();

        $authPage = $version
            ? DocumentationPage::query()
                ->where('api_version_id', $version->id)
                ->where('type', DocPageType::Authentication)
                ->published()
                ->first()
            : null;

        return view('docs.overview', [
            'version' => $version,
            'environment' => $this->portal->environment(),
            'authPageUrl' => $authPage
                ? route('docs.pages.show', ['version' => $version->slug, 'page' => $authPage->slug])
                : null,
        ]);
    }
}
