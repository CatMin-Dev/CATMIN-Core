<?php

namespace Tests\Unit\Media;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaAsset;
use Modules\Media\Services\UploadSecurityService;
use Tests\TestCase;

class UploadSecurityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');

        $this->createTables();

        // Ensure system_logs table exists for log tests
        if (!Schema::hasTable('system_logs')) {
            Schema::create('system_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('channel', 64)->nullable();
                $table->string('level', 32)->nullable();
                $table->string('event', 128)->nullable();
                $table->text('message')->nullable();
                $table->text('context')->nullable();
                $table->string('method', 16)->nullable();
                $table->text('url')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->timestamps();
            });
        }

        Config::set('catmin.uploads.quarantine_enabled', false);
        Config::set('catmin.uploads.allowed_extensions', [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'txt', 'csv', 'json', 'mp4', 'webm', 'mp3', 'zip',
        ]);
    }

    // ─── Test 1: fake jpg with PHP MIME is rejected ───────────────────────────

    public function test_fake_jpg_with_php_mime_is_rejected(): void
    {
        $service = app(UploadSecurityService::class);

        // Create a temp file with PHP content but jpg extension
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_upload_') . '.jpg';
        file_put_contents($tmpPath, '<?php echo "evil"; ?>');

        $file = new UploadedFile($tmpPath, 'evil.jpg', 'image/jpeg', null, true);

        $result = $service->inspect($file, 'jpg');

        // finfo should detect text/x-php or similar forbidden type
        $this->assertFalse((bool) $result['valid'], 'PHP-content file should be rejected even with jpg extension');
        $this->assertNotNull($result['error']);

        @unlink($tmpPath);
    }

    // ─── Test 2: valid image is accepted ─────────────────────────────────────

    public function test_valid_image_is_accepted(): void
    {
        $service = app(UploadSecurityService::class);

        // Use Laravel's fake image — real JPEG content
        Storage::fake('public');
        $fake = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $result = $service->inspect($fake, 'jpg');

        $this->assertTrue((bool) $result['valid'], 'Valid JPEG image should be accepted');
        $this->assertFalse((bool) $result['quarantine']);
        $this->assertNull($result['error']);
        $this->assertNotEmpty($result['real_mime']);
    }

    // ─── Test 3: rejected upload logs to system_logs ─────────────────────────

    public function test_rejected_upload_logs_to_system_logs(): void
    {
        $service = app(UploadSecurityService::class);

        $tmpPath = tempnam(sys_get_temp_dir(), 'test_upload_log_') . '.jpg';
        file_put_contents($tmpPath, '<?php system($_GET["cmd"]); ?>');

        $file = new UploadedFile($tmpPath, 'shell.jpg', 'image/jpeg', null, true);

        // Inspect — will be rejected
        $service->inspect($file, 'jpg');

        // Log should have been written
        $logEntry = DB::table('system_logs')
            ->where('channel', 'uploads')
            ->where('level', 'warning')
            ->where('event', 'upload.rejected')
            ->first();

        $this->assertNotNull($logEntry, 'A warning log entry should be written for rejected upload');

        @unlink($tmpPath);
    }

    // ─── Test 4: quarantine mode stores suspicious file but flags it ──────────

    public function test_quarantine_stores_file_and_flags_it(): void
    {
        Config::set('catmin.uploads.quarantine_enabled', true);

        $service = app(UploadSecurityService::class);

        Storage::fake('public');

        // A file whose declared extension (.jpg) doesn't match its actual content (text/plain)
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_quarantine_') . '.txt';
        file_put_contents($tmpPath, 'This is plain text but declared as a jpg');

        $file = new UploadedFile($tmpPath, 'mismatch.jpg', 'text/plain', null, true);

        $result = $service->inspect($file, 'jpg');

        // Should be valid but flagged for quarantine (text/plain mime on jpg extension = mismatch)
        $this->assertTrue((bool) $result['valid'], 'Quarantine mode: mismatched file should be stored not rejected');
        $this->assertTrue((bool) $result['quarantine'], 'Quarantine flag should be set for mismatch');
        $this->assertNotNull($result['error'], 'Error description should be present for quarantined file');

        @unlink($tmpPath);
    }

    // ─── DB setup helpers ────────────────────────────────────────────────────

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type', 128)->nullable();
            $table->string('real_mime', 128)->nullable();
            $table->string('extension', 32)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('uploaded_by_id')->nullable();
            $table->timestamp('quarantine_at')->nullable();
            $table->text('quarantine_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
