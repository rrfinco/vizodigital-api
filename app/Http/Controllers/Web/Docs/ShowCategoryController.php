<?php

namespace App\Http\Controllers\Web\Docs;

use App\Http\Controllers\Controller;
use App\Models\ApiCategory;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowCategoryController extends Controller
{
    public function __construct(
        private readonly DocumentationRepositoryInterface $documentation,
    ) {}

    public function __invoke(Request $request, string $version, string $category): View
    {
        $versionRecord = $this->documentation->findVersionBySlug($version);

        if (! $versionRecord || ! $versionRecord->isPubliclyVisible()) {
            throw new NotFoundHttpException('Version not found.');
        }

        $record = ApiCategory::query()
            ->published()
            ->where('slug', $category)
            ->where('api_version_id', $versionRecord->id)
            ->with(['groups' => fn ($q) => $q->published()->with(['endpoints' => fn ($eq) => $eq->published()])])
            ->first();

        if (! $record) {
            throw new NotFoundHttpException('Category not found.');
        }

        return view('docs.categories.show', [
            'version' => $versionRecord,
            'category' => $record,
        ]);
    }
}
