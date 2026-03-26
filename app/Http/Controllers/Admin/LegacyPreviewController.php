<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class LegacyPreviewController extends Controller
{
    private const ALLOWED_PAGES = [
        'dashboard',
        'calendar',
        'chartjs',
        'contacts',
        'e_commerce',
        'echarts',
        'fixed_footer',
        'fixed_sidebar',
        'form',
        'form_advanced',
        'form_buttons',
        'form_upload',
        'form_validation',
        'form_wizards',
        'general_elements',
        'icons',
        'inbox',
        'invoice',
        'level2',
        'map',
        'media_gallery',
        'other_charts',
        'plain_page',
        'pricing_tables',
        'profile',
        'project_detail',
        'projects',
        'tables',
        'tables_dynamic',
        'typography',
        'widgets',
    ];

    public function __invoke(Request $request, ?string $page = null)
    {
        $currentPage = strtolower((string)($page ?? 'dashboard'));

        if (!preg_match('/^[a-z0-9_\-]+$/', $currentPage) || !in_array($currentPage, self::ALLOWED_PAGES, true)) {
            $currentPage = 'dashboard';
        }

        $contentFile = base_path('dashboard/content/' . $currentPage . '.html');

        if (!is_file($contentFile) || !is_readable($contentFile)) {
            abort(404, 'Legacy content page not found.');
        }

        $legacyContent = file_get_contents($contentFile);
        if ($legacyContent === false) {
            abort(500, 'Unable to read legacy content.');
        }

        return view('admin.pages.legacy-preview', [
            'currentPage' => $currentPage,
            'legacyContent' => $legacyContent,
        ]);
    }
}
