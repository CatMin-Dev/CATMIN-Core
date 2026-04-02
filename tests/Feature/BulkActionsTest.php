<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Articles\Models\Article;
use Modules\Media\Models\MediaAsset;
use Modules\Pages\Models\Page;
use Modules\Webhooks\Models\Webhook;
use Tests\TestCase;

class BulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    private function createAdminUser(): User
    {
        return User::factory()->create();
    }

    /** @test */
    public function bulk_publish_pages()
    {
        $pages = Page::factory(3)->create(['status' => 'draft', 'published_at' => null]);

        $response = $this->post(admin_route('pages.bulk'), [
            'bulk_select' => $pages->pluck('id')->toArray(),
            'bulk_action' => 'publish',
        ]);

        $response->assertRedirect();
        foreach ($pages as $page) {
            $page->refresh();
            $this->assertEquals('published', $page->status);
        }
    }

    /** @test */
    public function bulk_unpublish_articles()
    {
        $articles = Article::factory(3)->create(['status' => 'published', 'published_at' => now()]);

        $response = $this->post(admin_route('articles.bulk'), [
            'bulk_select' => $articles->pluck('id')->toArray(),
            'bulk_action' => 'unpublish',
        ]);

        $response->assertRedirect();
        foreach ($articles as $article) {
            $article->refresh();
            $this->assertEquals('draft', $article->status);
            $this->assertNull($article->published_at);
        }
    }

    /** @test */
    public function bulk_trash_pages()
    {
        $pages = Page::factory(3)->create();

        $response = $this->post(admin_route('pages.bulk'), [
            'bulk_select' => $pages->pluck('id')->toArray(),
            'bulk_action' => 'trash',
        ]);

        $response->assertRedirect();
        foreach ($pages as $page) {
            $this->assertTrue($page->fresh()->trashed());
        }
    }

    /** @test */
    public function bulk_trash_media()
    {
        $media = MediaAsset::factory(3)->create();

        $response = $this->post(admin_route('media.bulk'), [
            'bulk_select' => $media->pluck('id')->toArray(),
            'bulk_action' => 'trash',
        ]);

        $response->assertRedirect();
        foreach ($media as $asset) {
            $this->assertTrue($asset->fresh()->trashed());
        }
    }

    /** @test */
    public function bulk_activate_users()
    {
        $users = User::factory(3)->create(['is_active' => false]);

        $response = $this->post(admin_route('users.bulk'), [
            'bulk_select' => $users->pluck('id')->toArray(),
            'bulk_action' => 'activate',
        ]);

        $response->assertRedirect();
        foreach ($users as $user) {
            $this->assertTrue($user->fresh()->is_active);
        }
    }

    /** @test */
    public function bulk_deactivate_webhooks()
    {
        $webhooks = Webhook::factory(3)->create(['status' => 'active']);

        $response = $this->post(admin_route('webhooks.bulk'), [
            'bulk_select' => $webhooks->pluck('id')->toArray(),
            'bulk_action' => 'deactivate',
        ]);

        $response->assertRedirect();
        foreach ($webhooks as $webhook) {
            $this->assertEquals('inactive', $webhook->fresh()->status);
        }
    }

    /** @test */
    public function bulk_action_with_empty_selection_redirects()
    {
        $response = $this->post(admin_route('pages.bulk'), [
            'bulk_select' => [],
            'bulk_action' => 'publish',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function bulk_delete_webhooks()
    {
        $webhooks = Webhook::factory(3)->create();
        $ids = $webhooks->pluck('id')->toArray();

        $response = $this->post(admin_route('webhooks.bulk'), [
            'bulk_select' => $ids,
            'bulk_action' => 'delete',
        ]);

        $response->assertRedirect();
        foreach ($ids as $id) {
            $this->assertNull(Webhook::find($id));
        }
    }

    /** @test */
    public function bulk_action_ignores_invalid_ids()
    {
        $pages = Page::factory(2)->create(['status' => 'draft']);
        $invalidIds = array_merge($pages->pluck('id')->toArray(), [999, 1000]);

        $response = $this->post(admin_route('pages.bulk'), [
            'bulk_select' => $invalidIds,
            'bulk_action' => 'publish',
        ]);

        $response->assertRedirect();
        // Only valid IDs should be updated
        foreach ($pages as $page) {
            $page->refresh();
            $this->assertEquals('published', $page->status);
        }
    }
}
