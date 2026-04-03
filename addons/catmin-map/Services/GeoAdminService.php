<?php

namespace Addons\CatminMap\Services;

use Addons\CatminMap\Models\GeoCategory;
use Addons\CatminMap\Models\GeoLocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GeoAdminService
{
    // ─── Categories ───────────────────────────────────────────────

    /** @return Collection<int,GeoCategory> */
    public function categories(): Collection
    {
        return GeoCategory::query()
            ->withCount('locations')
            ->orderBy('name')
            ->get();
    }

    /** @param array<string,mixed> $payload */
    public function createCategory(array $payload): GeoCategory
    {
        $slug = Str::slug((string) ($payload['slug'] ?? $payload['name']));

        return GeoCategory::query()->create([
            'name'        => (string) $payload['name'],
            'slug'        => $slug,
            'color'       => (string) ($payload['color'] ?? '#3B82F6'),
            'icon'        => $payload['icon'] ?? null,
            'description' => $payload['description'] ?? null,
            'active'      => (bool) ($payload['active'] ?? true),
        ]);
    }

    /** @param array<string,mixed> $payload */
    public function updateCategory(GeoCategory $category, array $payload): GeoCategory
    {
        $category->update([
            'name'        => (string) ($payload['name'] ?? $category->name),
            'color'       => (string) ($payload['color'] ?? $category->color),
            'icon'        => $payload['icon'] ?? $category->icon,
            'description' => $payload['description'] ?? $category->description,
            'active'      => isset($payload['active']) ? (bool) $payload['active'] : $category->active,
        ]);

        return $category->fresh();
    }

    public function deleteCategory(GeoCategory $category): void
    {
        $category->delete();
    }

    // ─── Locations ────────────────────────────────────────────────

    /** @param array<string,mixed> $filters */
    public function locations(array $filters = []): LengthAwarePaginator
    {
        $q    = trim((string) ($filters['q'] ?? ''));
        $cat  = $filters['category_id'] ?? null;
        $city = trim((string) ($filters['city'] ?? ''));
        $status = $filters['status'] ?? null;

        return GeoLocation::query()
            ->with('category:id,name,color,icon')
            ->when($q !== '', function ($builder) use ($q): void {
                $builder->where(function ($sub) use ($q): void {
                    $sub->where('name', 'like', '%' . $q . '%')
                        ->orWhere('city', 'like', '%' . $q . '%')
                        ->orWhere('address', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%');
                });
            })
            ->when($cat !== null && $cat !== '', fn ($b) => $b->where('geo_category_id', $cat))
            ->when($city !== '', fn ($b) => $b->where('city', 'like', '%' . $city . '%'))
            ->when($status !== null && $status !== '', fn ($b) => $b->where('status', $status))
            ->orderByDesc('featured')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Returns all published locations with coordinates for map rendering.
     * @return Collection<int,GeoLocation>
     */
    public function mapPoints(int $categoryId = 0): Collection
    {
        return GeoLocation::query()
            ->with('category:id,name,color,icon')
            ->where('status', 'published')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->when($categoryId > 0, fn ($b) => $b->where('geo_category_id', $categoryId))
            ->orderBy('name')
            ->get();
    }

    /** @param array<string,mixed> $payload */
    public function createLocation(array $payload): GeoLocation
    {
        $slug = $this->uniqueSlug((string) ($payload['slug'] ?? $payload['name']));
        [$lat, $lng] = $this->resolveCoordinates($payload);

        return GeoLocation::query()->create([
            'geo_category_id' => $payload['geo_category_id'] ?? null,
            'name'            => (string) $payload['name'],
            'slug'            => $slug,
            'description'     => $payload['description'] ?? null,
            'address'         => $payload['address'] ?? null,
            'city'            => $payload['city'] ?? null,
            'country'         => $payload['country'] ?? null,
            'zip'             => $payload['zip'] ?? null,
            'lat'             => $lat,
            'lng'             => $lng,
            'phone'           => $payload['phone'] ?? null,
            'email'           => $payload['email'] ?? null,
            'website'         => $payload['website'] ?? null,
            'opening_hours'   => $payload['opening_hours'] ?? null,
            'status'          => (string) ($payload['status'] ?? 'published'),
            'featured'        => (bool) ($payload['featured'] ?? false),
            'linked_event_id' => isset($payload['linked_event_id']) && $payload['linked_event_id'] !== '' ? (int) $payload['linked_event_id'] : null,
            'linked_shop_id'  => isset($payload['linked_shop_id']) && $payload['linked_shop_id'] !== '' ? (int) $payload['linked_shop_id'] : null,
            'linked_page_id'  => isset($payload['linked_page_id']) && $payload['linked_page_id'] !== '' ? (int) $payload['linked_page_id'] : null,
            'metadata'        => [],
        ]);
    }

    /** @param array<string,mixed> $payload */
    public function updateLocation(GeoLocation $location, array $payload): GeoLocation
    {
        [$lat, $lng] = $this->resolveCoordinates($payload, $location);

        $location->update([
            'geo_category_id' => array_key_exists('geo_category_id', $payload) ? ($payload['geo_category_id'] ?: null) : $location->geo_category_id,
            'name'            => (string) ($payload['name'] ?? $location->name),
            'description'     => $payload['description'] ?? $location->description,
            'address'         => $payload['address'] ?? $location->address,
            'city'            => $payload['city'] ?? $location->city,
            'country'         => $payload['country'] ?? $location->country,
            'zip'             => $payload['zip'] ?? $location->zip,
            'lat'             => $lat,
            'lng'             => $lng,
            'phone'           => $payload['phone'] ?? $location->phone,
            'email'           => $payload['email'] ?? $location->email,
            'website'         => $payload['website'] ?? $location->website,
            'opening_hours'   => $payload['opening_hours'] ?? $location->opening_hours,
            'status'          => (string) ($payload['status'] ?? $location->status),
            'featured'        => isset($payload['featured']) ? (bool) $payload['featured'] : $location->featured,
            'linked_event_id' => isset($payload['linked_event_id']) && $payload['linked_event_id'] !== '' ? (int) $payload['linked_event_id'] : $location->linked_event_id,
            'linked_shop_id'  => isset($payload['linked_shop_id']) && $payload['linked_shop_id'] !== '' ? (int) $payload['linked_shop_id'] : $location->linked_shop_id,
            'linked_page_id'  => isset($payload['linked_page_id']) && $payload['linked_page_id'] !== '' ? (int) $payload['linked_page_id'] : $location->linked_page_id,
        ]);

        return $location->fresh();
    }

    public function deleteLocation(GeoLocation $location): void
    {
        $location->delete();
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $i    = 0;

        while (GeoLocation::query()->where('slug', $i === 0 ? $slug : $slug . '-' . $i)->exists()) {
            ++$i;
        }

        return $i === 0 ? $slug : $slug . '-' . $i;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{0: float|null, 1: float|null}
     */
    private function resolveCoordinates(array $payload, ?GeoLocation $location = null): array
    {
        // Backward-compat for programmatic calls/tests that still pass coordinates.
        if (isset($payload['lat'], $payload['lng']) && $payload['lat'] !== '' && $payload['lng'] !== '') {
            return [(float) $payload['lat'], (float) $payload['lng']];
        }

        $address = trim((string) ($payload['address'] ?? $location?->address ?? ''));
        $city = trim((string) ($payload['city'] ?? $location?->city ?? ''));
        $zip = trim((string) ($payload['zip'] ?? $location?->zip ?? ''));
        $country = trim((string) ($payload['country'] ?? $location?->country ?? ''));

        $query = implode(', ', array_filter([$address, $zip, $city, $country]));

        if ($query === '') {
            return [$location?->lat, $location?->lng];
        }

        $endpoint = (string) setting('map.geocoding_url', 'https://nominatim.openstreetmap.org/search');
        $timeout = (int) setting('map.geocoding_timeout', 8);
        $userAgent = (string) setting('map.geocoding_user_agent', 'CATMIN-Map/1.0 (+https://catmin.local)');

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withHeaders(['User-Agent' => $userAgent])
                ->get($endpoint, [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ]);
        } catch (\Throwable) {
            if ($location?->lat !== null && $location?->lng !== null) {
                return [(float) $location->lat, (float) $location->lng];
            }

            throw new InvalidArgumentException('Impossible de géocoder cette adresse pour le moment. Réessayez plus tard.');
        }

        if (!$response->ok()) {
            if ($location?->lat !== null && $location?->lng !== null) {
                return [(float) $location->lat, (float) $location->lng];
            }

            throw new InvalidArgumentException('Échec du géocodage automatique. Vérifiez l\'adresse.');
        }

        $rows = $response->json();
        if (is_array($rows) && isset($rows[0]['lat'], $rows[0]['lon'])) {
            return [(float) $rows[0]['lat'], (float) $rows[0]['lon']];
        }

        if ($location?->lat !== null && $location?->lng !== null) {
            return [(float) $location->lat, (float) $location->lng];
        }

        throw new InvalidArgumentException('Aucune coordonnée trouvée pour cette adresse.');
    }
}
