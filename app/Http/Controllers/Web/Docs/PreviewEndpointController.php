<?php

namespace App\Http\Controllers\Web\Docs;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use App\Repositories\Contracts\ApiEndpointRepositoryInterface;
use App\Services\Portal\PortalContext;
use App\Services\Rendering\EndpointDocumentBuilder;
use App\Services\Rendering\SectionRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreviewEndpointController extends Controller
{
    public function __construct(
        private readonly ApiEndpointRepositoryInterface $endpoints,
        private readonly EndpointDocumentBuilder $documents,
        private readonly SectionRenderer $renderer,
        private readonly PortalContext $portal,
    ) {}

    public function __invoke(Request $request, ApiEndpoint $endpoint): View
    {
        abort_unless($request->user()?->can('docs.preview'), 403);

        $record = $this->endpoints->findForPreview($endpoint->id);

        abort_if($record === null, 404);

        return view('docs.endpoints.show', [
            'document' => $this->documents->build(
                $record,
                preview: true,
                environment: $this->portal->environment(),
            ),
            'renderer' => $this->renderer,
        ]);
    }
}
