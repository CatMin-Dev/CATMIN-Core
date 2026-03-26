<?php
$allowedPages = [
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

$currentPage = $_GET['page'] ?? 'dashboard';
$currentPage = strtolower($currentPage);

if (!preg_match('/^[a-z0-9_\-]+$/', $currentPage) || !in_array($currentPage, $allowedPages, true)) {
    $currentPage = 'dashboard';
}

include_once 'components/header.php';
include 'components/aside.php';
include 'components/topnav.php';
?>

<main class="right_col" role="main" aria-label="Main content">
<?php 

// here the page system, include with link from content .html files 

$contentFile = __DIR__ . '/content/' . $currentPage . '.html';

if (is_file($contentFile) && is_readable($contentFile)) {
    include $contentFile;
} else {
    http_response_code(404);
    echo '<div class="alert alert-warning m-3" role="alert">';
    echo 'Requested page content not found.';
    echo '</div>';
}

?>
</main>

<?php
    include 'components/footer.php';
?>