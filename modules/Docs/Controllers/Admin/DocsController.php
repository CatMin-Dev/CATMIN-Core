<?php

namespace Modules\Docs\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\SettingService;
use Modules\Docs\Services\DocsService;

class DocsController extends Controller
{
    public function __construct(private DocsService $docs) {}

    public function index(Request $request): View
    {
        $query = (string) $request->query('q', '');
        $filters = [
            'module' => (string) $request->query('module', ''),
            'version' => (string) $request->query('version', ''),
            'status' => (string) $request->query('status', ''),
            'category' => (string) $request->query('category', ''),
        ];

        if ($query !== '') {
            $results = $this->docs->search($query, $filters);
            $items = null;
        } else {
            $results = null;
            $items = $this->docs->filteredIndex($filters);
        }

        $options = $this->docs->filterOptions();

        return view()->file(base_path('modules/Docs/Views/index.blade.php'), [
            'currentPage' => 'docs',
            'items'       => $items,
            'results'     => $results,
            'query'       => $query,
            'filters'     => $filters,
            'modules'     => $options['modules'],
            'versions'    => $options['versions'],
            'statuses'    => $options['statuses'],
            'categories'  => $options['categories'],
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
            'discordPublishEnabled' => filter_var((string) SettingService::get('docs.discord_publish_enabled', '0'), FILTER_VALIDATE_BOOLEAN)
                && trim((string) SettingService::get('docs.discord_webhook_url', '')) !== '',
        ]);
    }

    public function publishDiscord(string $slug): RedirectResponse
    {
        $doc = $this->docs->find($slug);

        if ($doc === null) {
            return redirect()->route('admin.docs.index')->with('error', 'Document introuvable.');
        }

        $result = $this->docs->publishToDiscord($doc);

        if ($result['ok']) {
            return redirect()->route('admin.docs.show', ['slug' => $slug])
                ->with('status', 'Documentation publiee sur Discord.');
        }

        return redirect()->route('admin.docs.show', ['slug' => $slug])
            ->with('error', 'Publication Discord echouee: ' . ($result['error'] ?? 'erreur inconnue'));
    }
}
