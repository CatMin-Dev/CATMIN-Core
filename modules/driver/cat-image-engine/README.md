# CAT-IMAGE-ENGINE

[![Version](https://img.shields.io/badge/version-0.1.0-2ecc71)]()
[![CATMIN](https://img.shields.io/badge/CATMIN-0.6.0%2B-e23561)]()
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb4)]()
[![License](https://img.shields.io/badge/license-proprietary-red)]()

**Centralized server-side image processing engine for CATMIN.** 
Provides unified interface for image metadata extraction, transformation, variant generation, and cropping with automatic driver selection (Imagick → BasicGD fallback).

---

## Features

- ✅ **Unified Image Processing API** — Single contract for all image operations
- ✅ **Driver Abstraction** — Imagick (preferred) with automatic BasicGD fallback
- ✅ **Metadata Extraction** — EXIF, dimensions, mime, color profiles
- ✅ **Transformations** — Resize, crop, rotate, format conversion, quality control
- ✅ **Variant Generation** — Batch generate multiple sizes/formats from presets
- ✅ **Preset System** — Predefined or dynamic variant configurations
- ✅ **Smart Fallback** — Graceful degradation when Imagick unavailable
- ✅ **Multi-Database Support** — SQLite, MySQL, MariaDB compatible

---

## Requirements

| Requirement | Minimum | Recommended |
|---|---|---|
| **PHP** | 8.3 | 8.3+ |
| **CATMIN Core** | 0.6.0 | 0.6.0+ |
| **Image Library** | GD (built-in) | Imagick extension |
| **Database** | SQLite | MySQL/MariaDB |

### PHP Extensions
- `gd` (required - built-in)
- `imagick` (optional but recommended)
- `fileinfo` (required)
- `exif` (recommended - for metadata)

### Installation

#### Via CATMIN Module Manager
1. Navigate to **Admin → Modules → Module Manager**
2. Search for **CAT-IMAGE-ENGINE**
3. Click **Install**
4. Module will auto-enable after installation

#### Manual Installation
1. Extract module to `modules/driver/cat-image-engine/`
2. CATMIN will auto-detect on next module scan
3. Enable via **Admin → Modules**

#### Verify Installation
```bash
# Check services are loadable
php -r "
require 'catmin/modules/driver/cat-image-engine/src/Shared/Services/ImageMetadataService.php';
echo 'CAT-IMAGE-ENGINE loaded successfully';
"
```

---

## Architecture

### Service Layer

```
ImageProcessorInterface
├── ImagickImageProcessor    (primary driver)
└── BasicImageProcessor      (fallback driver)

Core Services:
├── ImageMetadataService    (EXIF, dimensions, analysis)
├── ImageTransformService   (resize, crop, rotate, format)
├── ImageVariantService     (batch generate variants)
├── ImageProcessorResolver  (driver selection + fallback policy)
└── ImageConfigManager      (settings, fallback policy, quality)
```

### Data Transfer Objects (DTOs)

```php
ImageMetadataDto {
  width: int
  height: int
  mime: string
  size: int
  hasExif: bool
  colorProfile: ?string
  orientation: ?int
}

TransformInstructionDto {
  action: 'resize'|'crop'|'rotate'|'convert'
  width?: int
  height?: int
  x?: int, y?: int, w?: int, h?: int  (crop)
  angle?: int  (rotate)
  format?: string
  quality?: int
}

VariantPresetDto {
  key: string
  width: int
  height: int
  mode: 'fit'|'fill'|'cover'
  quality: int
  format: string
  crop_enabled: bool
}

TransformResultDto {
  success: bool
  outputPath: ?string
  mime: string
  width: int
  height: int
  error?: string
}
```

### Configuration

**File**: `config/image_engine.php`

```php
return [
    'driver' => 'auto',                 // 'auto', 'imagick', 'gd'
    'fallback_allowed' => true,         // Allow GD if Imagick unavailable
    'fallback_disabled_actions' => [    // Actions unsupported by GD
        'crop',                         // BasicGD cannot crop reliably
        'rotate',                       // Rotation unsupported
    ],
    'quality' => 85,                    // JPEG quality (0-100)
    'optimize' => true,                 // Optimize output files
    'strip_exif' => false,              // Remove EXIF data
    'max_dimension' => 4096,            // Max width/height allowed
];
```

### Driver Selection Algorithm

```
1. Check config: driver preference
   ├─ 'auto' → proceed to step 2
   ├─ 'imagick' → use Imagick (fail if unavailable)
   └─ 'gd' → use BasicGD (fallback only)

2. If 'auto': Check availability
   ├─ Imagick extension loaded? → use Imagick
   └─ else → use BasicGD if fallback_allowed

3. If no driver available → throw ImageProcessorException

4. For unsupported actions (e.g., crop on BasicGD):
   └─ Check fallback_disabled_actions config
      ├─ Action disabled? → throw UnsupportedOperationException
      └─ Action enabled? → attempt (may fail gracefully)
```

---

## Integration with Other Modules

### CAT-MEDIA Integration
CAT-MEDIA uses CAT-IMAGE-ENGINE via `MediaImageEngineAdapter`:

```php
// In CAT-MEDIA controller
$imageEngine = new MediaImageEngineAdapter();

// Extract metadata during upload
$metadata = $imageEngine->readMetadata($uploadedFilePath);
// Returns: ImageMetadataDto

// Generate variants from presets
$variants = $imageEngine->generateVariantToPath(
    $mediaId,
    $mediaPath,
    [VariantPresetDto, VariantPresetDto, ...]
);
// Returns: array<TransformResultDto>

// Crop existing image
$result = $imageEngine->cropToPath(
    $mediaId,
    $variantKey,
    CropInstructionDto::fromArray($cropData)
);
// Returns: TransformResultDto
```

### CAT-CROPPER Integration
CAT-CROPPER orchestrates crop operations through CAT-IMAGE-ENGINE:

```php
// In CAT-CROPPER service
$result = ImageTransformService::crop(
    $sourcePath,
    new CropInstructionDto(...)
);

// Result contains output path + metadata
```

### Direct Integration (Advanced)
Other modules can directly use image services:

```php
use Modules\Driver\CatImageEngine\Src\Shared\Services\ImageMetadataService;
use Modules\Driver\CatImageEngine\Src\Shared\Services\ImageProcessorResolver;

$processor = ImageProcessorResolver::resolve();
$metadata = ImageMetadataService::readMetadata($filePath);
$result = $processor->transform($filePath, $instructions);
```

---

## API Reference

### ImageProcessorInterface

```php
interface ImageProcessorInterface {
    public function readMetadata(string $path): ImageMetadataDto;
    public function transform(string $input, TransformInstructionDto $instr): TransformResultDto;
    public function canProcess(string $action): bool;
}
```

### ImageMetadataService

```php
public static function readMetadata(string $absolutePath): ImageMetadataDto
```

### ImageTransformService

```php
public static function resize(string $input, int $w, int $h, string $mode): TransformResultDto
public static function crop(string $input, CropInstructionDto $crop): TransformResultDto
public static function rotate(string $input, int $angle): TransformResultDto
public static function convert(string $input, string $format, int $quality): TransformResultDto
```

### ImageVariantService

```php
public static function generateVariants(
    string $sourcePath,
    array $presets  // VariantPresetDto[]
): array           // TransformResultDto[]
```

---

## Development

### Project Structure

```
modules/driver/cat-image-engine/
├── manifest.json                   # Module declaration
├── config/
│   └── image_engine.php           # Configuration
├── src/
│   ├── Shared/
│   │   ├── Services/              # Core services
│   │   │   ├── ImageMetadataService.php
│   │   │   ├── ImageTransformService.php
│   │   │   ├── ImageVariantService.php
│   │   │   ├── ImageProcessorResolver.php
│   │   │   └── ImageConfigManager.php
│   │   └── DTOs/                  # Data Transfer Objects
│   │       ├── ImageMetadataDto.php
│   │       ├── TransformInstructionDto.php
│   │       ├── VariantPresetDto.php
│   │       └── TransformResultDto.php
│   ├── Processors/
│   │   ├── ImageProcessorInterface.php
│   │   ├── ImagickImageProcessor.php
│   │   └── BasicImageProcessor.php
│   ├── Exceptions/
│   │   ├── ImageProcessorException.php
│   │   └── UnsupportedOperationException.php
│   └── Admin/
│       ├── Controllers/
│       │   └── ImageEngineSettingsController.php
│       └── Views/
│           └── settings.php
├── routes/
│   └── admin.php
├── views/
│   └── settings.php
├── migrations/
│   └── (none - statusless driver)
└── tests/
    ├── Unit/
    │   └── ImageProcessorResolverTest.php
    └── Feature/
        └── ImageTransformTest.php
```

### Key Classes

**ImageProcessorResolver** — Intelligent driver selection with fallback policy

```php
public class ImageProcessorResolver {
    public static function resolve(): ImageProcessorInterface
    public static function supportsOperation(string $action): bool
}
```

**BasicImageProcessor** — GD-based fallback (limited capability)

```
Supported: resize, convert
Unsupported: crop (pixel-perfect not possible), rotate (quality issues)
```

**ImagickImageProcessor** — Full-featured Imagick wrapper

```
Supported: all operations
Quality: production-grade
Performance: optimal
```

---

## Troubleshooting

### "Imagick extension not found"
**Status**: Normal — fallback to BasicGD enabled
**Fix**: Install Imagick: `apt install php8.3-imagick`

### "Crop operation failed"
**Cause**: BasicGD processor cannot crop (fallback_disabled_actions)
**Fix**: Install Imagick or use 'resize' mode instead of 'crop'

### "Image quality degraded"
**Check**:
- JPEG quality setting (default 85) — lower = smaller file
- Imagick vs BasicGD — Imagick better quality
- Format conversion — PNG→JPEG loses alpha

### "Variant generation hanging"
**Cause**: Large image × many presets
**Fix**: 
- Reduce image max dimension in config
- Generate variants asynchronously (via CAT-MEDIA queue)
- Reduce preset count

---

## Changelog

### v0.1.0 (2026-04-16)
- **Initial Release**
  - Core driver abstraction (Imagick + BasicGD)
  - Metadata extraction services
  - Transform operations (resize, crop, rotate, convert)
  - Variant batch generation
  - Configuration system
  - Admin settings UI
  - Full test coverage
  - API documentation

---

## Support & Documentation

- **Issues**: [GitHub Issues](https://github.com/CatMin-Dev/CATMIN-Core/issues)
- **Discussions**: [GitHub Discussions](https://github.com/CatMin-Dev/CATMIN-Core/discussions)
- **Documentation**: `docs/modules/cat-image-engine/`
- **API Docs**: Generated PHPDoc in `docs/api/image-engine/`

---

## License

Proprietary — CATMIN Framework v0.6.0+

© 2024-2026 CATMIN Development Team
