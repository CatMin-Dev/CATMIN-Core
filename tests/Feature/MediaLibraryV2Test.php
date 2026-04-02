<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Media\Models\MediaAsset;
use Modules\Media\Services\MediaAdminService;
use Tests\TestCase;

class MediaLibraryV2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('media_assets')) {
            Schema::create('media_assets', function (Blueprint $table): void {
                $table->id();
                $table->string('disk')->default('public');
                $table->string('path');
                $table->string('filename');
                $table->string('original_name');
                $table->string('mime_type', 128)->nullable();
                $table->string('extension', 32)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('alt_text')->nullable();
                $table->text('caption')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('uploaded_by_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_media_manage_supports_text_search(): void
    {
        /** @var MediaAdminService $service */
        $service = app(MediaAdminService::class);

        MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/docs/invoice-2026.pdf',
            'filename' => 'invoice-2026.pdf',
            'original_name' => 'invoice-2026.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 12345,
            'caption' => 'Invoice April',
        ]);

        MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/images/cat-photo.jpg',
            'filename' => 'cat-photo.jpg',
            'original_name' => 'cat-photo.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 54321,
        ]);

        $results = $service->listing(['q' => 'invoice'], 24, 'active');

        $this->assertSame(1, $results->total());
        $this->assertSame('invoice-2026.pdf', $results->items()[0]->original_name);
    }

    public function test_media_manage_supports_advanced_sorting(): void
    {
        /** @var MediaAdminService $service */
        $service = app(MediaAdminService::class);

        MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/docs/small.pdf',
            'filename' => 'small.pdf',
            'original_name' => 'small.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 100,
        ]);

        MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/docs/big.pdf',
            'filename' => 'big.pdf',
            'original_name' => 'big.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 100000,
        ]);

        $results = $service->listing(['sort' => 'size_desc'], 24, 'active');

        $this->assertSame(2, $results->total());
        $this->assertSame('big.pdf', $results->items()[0]->original_name);
        $this->assertSame('small.pdf', $results->items()[1]->original_name);
    }

    public function test_media_bulk_delete_sends_assets_to_trash(): void
    {
        /** @var MediaAdminService $service */
        $service = app(MediaAdminService::class);

        $first = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/docs/first.pdf',
            'filename' => 'first.pdf',
            'original_name' => 'first.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 300,
        ]);

        $second = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/docs/second.pdf',
            'filename' => 'second.pdf',
            'original_name' => 'second.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 350,
        ]);

        $count = $service->bulkTrash([$first->id, $second->id]);

        $this->assertSame(2, $count);
        $this->assertSoftDeleted('media_assets', ['id' => $first->id]);
        $this->assertSoftDeleted('media_assets', ['id' => $second->id]);
    }

    public function test_media_preview_supports_document_mode(): void
    {
        /** @var MediaAdminService $service */
        $service = app(MediaAdminService::class);

        $document = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/docs/previewable.pdf',
            'filename' => 'previewable.pdf',
            'original_name' => 'previewable.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 9012,
        ]);

        $this->assertSame('document', $service->previewMode($document));
        $this->assertNotNull($service->fileUrl($document));
        $this->assertNull($service->previewUrl($document));
    }

    public function test_media_picker_still_returns_usable_payload(): void
    {
        /** @var MediaAdminService $service */
        $service = app(MediaAdminService::class);

        $asset = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/images/hero.png',
            'filename' => 'hero.png',
            'original_name' => 'hero.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 1500,
        ]);

        $item = $service->toPickerItem($asset);

        $this->assertSame('hero.png', $item['original_name']);
        $this->assertNotEmpty($item['preview_url']);
        $this->assertNotEmpty($item['file_url']);
    }
}
