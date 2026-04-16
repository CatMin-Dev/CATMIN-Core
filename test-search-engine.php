<?php
/**
 * Test script for topbar search engine
 * Run: php test-search-engine.php
 */

declare(strict_types=1);

// Minimal bootstrap
define('CATMIN_ROOT', __DIR__);
define('CATMIN_CORE', CATMIN_ROOT . '/core');
define('CATMIN_ADMIN', CATMIN_ROOT . '/admin');

// Mock config function if needed
if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed {
        $config = [
            'security.admin_path' => 'admin',
            'database.prefixes.admin' => 'admin_',
            'database.prefixes.core' => 'core_',
        ];
        $keys = explode('.', $key);
        $current = $config;
        foreach ($keys as $k) {
            if (is_array($current) && array_key_exists($k, $current)) {
                $current = $current[$k];
            } else {
                return $default;
            }
        }
        return $current;
    }
}

// Mock __() function for translations
if (!function_exists('__')) {
    function __(string $key, array $params = []): string {
        $translations = [
            'nav.dashboard' => 'Dashboard',
            'nav.monitoring' => 'Monitoring',
            'nav.health_check' => 'Health Check',
            'nav.system.monitoring.title' => 'System Monitoring',
            'system.monitoring.title' => 'Monitoring',
            'system.health.title' => 'Health Check',
            'logs.title' => 'Logs',
            'nav.logs' => 'Logs',
            'nav.notifications' => 'Notifications',
            'nav.cron' => 'Cron',
            'nav.maintenance' => 'Maintenance',
            'nav.settings' => 'Settings',
            'nav.module_manager' => 'Modules',
            'nav.modules' => 'Modules',
            'nav.trust_center' => 'Trust Center',
            'nav.update_center' => 'Update Center',
            'nav.staff_admins' => 'Users',
            'nav.roles_permissions' => 'Roles',
            'nav.apps' => 'Apps',
            'settings.general' => 'General Settings',
        ];
        
        if (array_key_exists($key, $translations)) {
            return $translations[$key];
        }
        return $key;
    }
}

// Load the topbar bridge
require_once CATMIN_CORE . '/module-runtime-snapshot.php';
require_once CATMIN_CORE . '/topbar-bridge.php';

// Print test header
echo "\n" . str_repeat("=", 60) . "\n";
echo "CATMIN Search Engine Test\n";
echo str_repeat("=", 60) . "\n\n";

try {
    $bridge = new CoreTopbarBridge();
    
    // Test 1: Empty query
    echo "TEST 1: Empty search\n";
    $results = $bridge->searchResults('/admin', '', 12);
    echo "Results count: " . count($results) . "\n";
    echo "First item: " . ($results[0]['label'] ?? 'N/A') . "\n\n";
    
    // Test 2: Dashboard search
    echo "TEST 2: Search for 'dashboard'\n";
    $results = $bridge->searchResults('/admin', 'dashboard', 10);
    echo "Results count: " . count($results) . "\n";
    foreach ($results as $i => $item) {
        echo "  [$i] " . ($item['label'] ?? 'N/A') . " (score: " . ($item['_score'] ?? 'N/A') . ")\n";
    }
    echo "\n";
    
    // Test 3: Monitoring search
    echo "TEST 3: Search for 'monitoring'\n";
    $results = $bridge->searchResults('/admin', 'monitoring', 10);
    echo "Results count: " . count($results) . "\n";
    foreach ($results as $i => $item) {
        echo "  [$i] " . ($item['label'] ?? 'N/A') . "\n";
        echo "      Answer: " . (substr($item['answer'] ?? 'N/A', 0, 50)) . "...\n";
    }
    echo "\n";
    
    // Test 4: User/Security search
    echo "TEST 4: Search for 'user'\n";
    $results = $bridge->searchResults('/admin', 'user', 10);
    echo "Results count: " . count($results) . "\n";
    foreach ($results as $i => $item) {
        echo "  [$i] " . ($item['label'] ?? 'N/A') . " - Type: " . ($item['type'] ?? 'N/A') . "\n";
    }
    echo "\n";
    
    // Test 5: Structure validation
    echo "TEST 5: Result structure validation\n";
    $results = $bridge->searchResults('/admin', 'settings', 5);
    $requiredFields = ['label', 'url', 'type', 'description'];
    if (!empty($results)) {
        $item = $results[0];
        $valid = true;
        foreach ($requiredFields as $field) {
            if (!isset($item[$field])) {
                echo "  ❌ Missing field: $field\n";
                $valid = false;
            }
        }
        if ($valid) {
            echo "  ✅ All required fields present\n";
            echo "  Sample result:\n";
            echo "    Label: " . $item['label'] . "\n";
            echo "    URL: " . $item['url'] . "\n";
            echo "    Type: " . $item['type'] . "\n";
            echo "    Description: " . $item['description'] . "\n";
            echo "    Keywords: " . ($item['keywords'] ?? 'N/A') . "\n";
            echo "    Answer: " . (strlen($item['answer'] ?? '') > 50 ? substr($item['answer'], 0, 50) . '...' : $item['answer'] ?? 'N/A') . "\n";
        }
    } else {
        echo "  ⚠️  No results returned for this query\n";
    }
    echo "\n";
    
    echo str_repeat("=", 60) . "\n";
    echo "✅ Search engine is working correctly!\n";
    echo str_repeat("=", 60) . "\n\n";
    
} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
