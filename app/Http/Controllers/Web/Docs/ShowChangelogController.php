<?php

namespace App\Http\Controllers\Web\Docs;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowChangelogController extends Controller
{
    public function __construct(
        private readonly DocumentationRepositoryInterface $documentation,
    ) {}

    public function __invoke(Request $request, string $version): View
    {
        $versionRecord = $this->documentation->findVersionBySlug($version);

        if (! $versionRecord || ! $versionRecord->isPubliclyVisible()) {
            throw new NotFoundHttpException('Version not found.');
        }

        return view('docs.changelog.index', [
            'version' => $versionRecord,
            'entries' => $this->documentation->publishedChangelog($version),
        ]);
    }
}
