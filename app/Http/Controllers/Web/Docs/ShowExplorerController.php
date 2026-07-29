<?php

namespace App\Http\Controllers\Web\Docs;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use App\Services\Portal\PortalContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowExplorerController extends Controller
{
    public function __construct(
        private readonly DocumentationRepositoryInterface $documentation,
        private readonly PortalContext $portal,
    ) {}

    public function __invoke(Request $request, string $version): View
    {
        $record = $this->documentation->findVersionBySlug($version);

        if (! $record || ! $record->isPubliclyVisible()) {
            throw new NotFoundHttpException('Version not found.');
        }

        return view('docs.explorer', [
            'version' => $record,
            'categories' => $this->documentation->publishedCategoryTree($version),
            'environment' => $this->portal->environment(),
        ]);
    }
}
