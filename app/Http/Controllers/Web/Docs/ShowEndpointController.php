<?php

namespace App\Http\Controllers\Web\Docs;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ApiEndpointRepositoryInterface;
use App\Services\Portal\PortalContext;
use App\Services\Rendering\EndpointDocumentBuilder;
use App\Services\Rendering\SectionRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowEndpointController extends Controller
{
    public function __construct(
        private readonly ApiEndpointRepositoryInterface $endpoints,
        private readonly EndpointDocumentBuilder $documents,
        private readonly SectionRenderer $renderer,
        private readonly PortalContext $portal,
    ) {}

    public function __invoke(Request $request, string $version, string $endpoint): View
    {
        $record = $this->endpoints->findPublishedBySlug($version, $endpoint);

        if (! $record) {
            throw new NotFoundHttpException('This endpoint is not published.');
        }

        return view('docs.endpoints.show', [
            'document' => $this->documents->build(
                $record,
                preview: false,
                environment: $this->portal->environment(),
            ),
            'renderer' => $this->renderer,
        ]);
    }
}
