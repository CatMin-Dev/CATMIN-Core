<?php

namespace Modules\Docs\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Docs\Services\DocsService;

class DocsController extends Controller
{
    public function __construct(private DocsService $docs) {}

    public function index(Request $request): View
    {
        $query = (string) $request->query('q', '');
        $module = (string) $request->query('module', '');

        if ($query !== '') {
            $results = $this->docs->search($query);
            $items = null;
        } elseif ($module !== '') {
            $results = null;
            $items = $this->docs->forModule($module);
        } else {
            $results = null;
            $items = $this->docs->index();
        }

        // Collect distinct module slugs for filter UI
        $modules = collect($this->docs->index())
            ->pluck('module')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return view()->file(base_path('modules/Docs/Views/index.blade.php'), [
            'currentPage' => 'docs',
            'items'       => $items,
            'results'     => $results,
            'query'       => $query,
            'activeModule' => $module,
            'modules'     => $modules,
        ]);
    }

    public function show(string $slug): View|\Illuminate\Http\RedirectResponse
    {
        $doc = $this->docs->find($slug);

        if ($doc === null) {
            return redirect()->route('admin.docs.index')
                ->with('error', 'Document introuvable.');
        }

        return view()->file(base_path('modules/Docs/Views/show.blade.php'), [
            'currentPage' => 'docs',
            'doc'         => $doc,
        ]);
    }
}
