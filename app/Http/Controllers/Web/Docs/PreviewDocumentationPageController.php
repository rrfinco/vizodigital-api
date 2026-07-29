<?php

namespace App\Http\Controllers\Web\Docs;

use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use App\Services\Rendering\DocumentationPageBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreviewDocumentationPageController extends Controller
{
    public function __construct(
        private readonly DocumentationRepositoryInterface $documentation,
        private readonly DocumentationPageBuilder $pages,
    ) {}

    public function __invoke(Request $request, DocumentationPage $page): View
    {
        abort_unless($request->user()?->can('docs.preview'), 403);

        $record = $this->documentation->findPageForPreview($page->id);

        abort_if($record === null, 404);

        return view('docs.pages.show', [
            'document' => $this->pages->build($record, preview: true),
        ]);
    }
}
