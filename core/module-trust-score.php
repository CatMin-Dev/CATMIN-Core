<?php

declare(strict_types=1);

final class CoreModuleTrustScore
{
    /** @param array<string,mixed> $row */
    public function evaluate(array $row): array
    {
        $score = 0;
        $signals = [];

        $trust = strtolower(trim((string) ($row['repo_trust_level'] ?? 'community')));
        $score += match ($trust) {
            'official' => 35,
            'trusted' => 25,
            'community' => 12,
            default => 0,
        };
        $signals[] = 'repo:' . $trust;

        $integrity = strtolower(trim((string) ($row['integrity_status'] ?? 'n/a')));
        if ($integrity === 'valid') {
            $score += 18;
            $signals[] = 'integrity:ok';
        } elseif (in_array($integrity, ['tampered', 'invalid'], true)) {
            $score -= 30;
            $signals[] = 'integrity:ko';
        } else {
            $signals[] = 'integrity:unknown';
        }

        $signature = strtolower(trim((string) ($row['signature_status'] ?? 'n/a')));
        if ($signature === 'signed_valid') {
            $score += 18;
            $signals[] = 'signature:ok';
        } elseif (in_array($signature, ['invalid', 'unknown_key'], true)) {
            $score -= 18;
            $signals[] = 'signature:ko';
        } else {
            $signals[] = 'signature:unsigned';
        }

        $channel = strtolower(trim((string) ($row['release_channel'] ?? 'stable')));
        $score += match ($channel) {
            'stable' => 14,
            'beta' => 6,
            'alpha' => 2,
            'experimental' => -8,
            default => 0,
        };
        $signals[] = 'channel:' . $channel;

        $lifecycle = strtolower(trim((string) ($row['lifecycle_status'] ?? 'active')));
        $score += match ($lifecycle) {
            'active' => 10,
            'deprecated' => -8,
            'abandoned' => -20,
            'archived' => -15,
            'replaced' => -6,
            'experimental' => -10,
            default => 0,
        };
        $signals[] = 'lifecycle:' . $lifecycle;

        if (trim((string) ($row['readme_url'] ?? '')) !== '') {
            $score += 2;
            $signals[] = 'docs:readme';
        }
        if (trim((string) ($row['changelog_url'] ?? '')) !== '') {
            $score += 1;
            $signals[] = 'docs:changelog';
        }

        $score = max(0, min(100, $score));
        $grade = $score >= 85 ? 'high' : ($score >= 60 ? 'medium' : 'low');

        $badges = [];
        $badges[] = $trust;
        if ($signature === 'signed_valid') {
            $badges[] = 'signed';
        }
        if ($integrity === 'valid') {
            $badges[] = 'integrity_ok';
        }
        $badges[] = $channel;
        $badges[] = $lifecycle;

        return [
            'score' => $score,
            'grade' => $grade,
            'signals' => $signals,
            'badges' => array_values(array_unique($badges)),
            'explain' => $this->explain($trust, $signature, $integrity, $channel, $lifecycle),
        ];
    }

    private function explain(string $trust, string $signature, string $integrity, string $channel, string $lifecycle): string
    {
        return sprintf(
            'Source %s, signature %s, integrity %s, channel %s, lifecycle %s.',
            $trust !== '' ? $trust : 'unknown',
            $signature !== '' ? $signature : 'unknown',
            $integrity !== '' ? $integrity : 'unknown',
            $channel !== '' ? $channel : 'unknown',
            $lifecycle !== '' ? $lifecycle : 'unknown'
        );
    }
}

