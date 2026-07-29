<?php

namespace App\Http\Controllers\Web\Docs;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use App\Services\Rendering\DocumentationPageBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowDocumentationPageController extends Controller
{
    public function __construct(
        private readonly DocumentationRepositoryInterface $documentation,
        private readonly DocumentationPageBuilder $pages,
    ) {}

    public function __invoke(Request $request, string $version, string $page): View
    {
        $record = $this->documentation->findPublishedPageBySlug($version, $page);

        if (! $record) {
            throw new NotFoundHttpException('This documentation page is not published.');
        }

        return view('docs.pages.show', [
            'document' => $this->pages->build($record, preview: false),
        ]);
    }
}
