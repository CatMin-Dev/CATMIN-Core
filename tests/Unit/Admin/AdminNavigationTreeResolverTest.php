<?php

namespace Tests\Unit\Admin;

use App\Services\AdminNavigation\AdminNavigationTreeResolver;
use Tests\TestCase;

class AdminNavigationTreeResolverTest extends TestCase
{
    public function test_it_groups_items_under_master_categories(): void
    {
        $resolver = new AdminNavigationTreeResolver();

        $tree = $resolver->resolve([
            ['label' => 'Tableau de bord', 'section' => 'Administration', 'url' => '/admin', 'icon' => 'bi bi-house'],
            ['label' => 'Pages', 'section' => 'CMS', 'url' => '/admin/content/pages', 'icon' => 'bi bi-file'],
            ['label' => 'Queue', 'section' => 'Administration', 'url' => '/admin/queue', 'icon' => 'bi bi-stack'],
            ['label' => 'CRM', 'section' => 'Integrations', 'url' => '/admin/crm', 'icon' => 'bi bi-person-lines-fill'],
        ]);

        $labels = collect($tree)->pluck('label')->all();

        $this->assertContains('Dashboard', $labels);
        $this->assertContains('Contenu', $labels);
        $this->assertContains('Exploitation', $labels);
        $this->assertContains('Business / Addons', $labels);
    }
}
