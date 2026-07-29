<?php

namespace App\Http\Controllers\Web\Docs;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowChangelogEntryController extends Controller
{
    public function __construct(
        private readonly DocumentationRepositoryInterface $documentation,
    ) {}

    public function __invoke(Request $request, string $version, string $entry): View
    {
        $versionRecord = $this->documentation->findVersionBySlug($version);

        if (! $versionRecord || ! $versionRecord->isPubliclyVisible()) {
            throw new NotFoundHttpException('Version not found.');
        }

        $record = $this->documentation->findPublishedChangelogEntry($version, $entry);

        if (! $record) {
            throw new NotFoundHttpException('Changelog entry not found.');
        }

        return view('docs.changelog.show', [
            'version' => $versionRecord,
            'entry' => $record,
        ]);
    }
}
