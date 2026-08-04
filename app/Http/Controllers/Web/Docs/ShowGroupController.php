<?php

namespace App\Http\Controllers\Web\Docs;

use App\Http\Controllers\Controller;
use App\Models\ApiGroup;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use App\Services\Docs\DocsEndpointVisibility;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowGroupController extends Controller
{
    public function __construct(
        private readonly DocumentationRepositoryInterface $documentation,
        private readonly DocsEndpointVisibility $endpointVisibility,
    ) {}

    public function __invoke(Request $request, string $version, string $group): View
    {
        $versionRecord = $this->documentation->findVersionBySlug($version);

        if (! $versionRecord || ! $versionRecord->isPubliclyVisible()) {
            throw new NotFoundHttpException('Version not found.');
        }

        $record = ApiGroup::query()
            ->published()
            ->where('slug', $group)
            ->whereHas('category', fn ($q) => $q->where('api_version_id', $versionRecord->id)->published())
            ->with(['category', 'endpoints' => fn ($q) => $q->published()])
            ->first();

        if (! $record) {
            throw new NotFoundHttpException('Group not found.');
        }

        $record->setRelation(
            'endpoints',
            $this->endpointVisibility->filterEndpoints($record->endpoints, $request->user())
        );

        return view('docs.groups.show', [
            'version' => $versionRecord,
            'group' => $record,
        ]);
    }
}
