<?php

declare(strict_types=1);

final class CoreModuleTrustScore
{
    /** @param array<string,mixed> $row */
    public function evaluate(array $row): array
    {
        $score = 50;
        $signals = [];
        $positive = [];
        $negative = [];
        $contributions = [];

        $trust = strtolower(trim((string) ($row['repo_trust_level'] ?? 'community')));
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            'repo.' . $trust,
            match ($trust) {
                'official' => 'Dépôt officiel CATMIN',
                'trusted' => 'Dépôt trusted validé',
                'community' => 'Dépôt community non approuvé',
                'blocked' => 'Dépôt explicitement bloqué',
                default => 'Niveau de confiance dépôt inconnu',
            },
            match ($trust) {
                'official' => 14,
                'trusted' => 8,
                'community' => -12,
                'blocked' => -35,
                default => -8,
            }
        );

        $signature = strtolower(trim((string) ($row['signature_status'] ?? 'n/a')));
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            'signature.' . $signature,
            match ($signature) {
                'signed_valid' => 'Signature valide',
                'invalid' => 'Signature invalide',
                'unknown_key' => 'Signature inconnue',
                'unsigned', 'n/a' => 'Module non signé',
                'revoked_key' => 'Signature révoquée',
                default => 'Signature non qualifiée',
            },
            match ($signature) {
                'signed_valid' => 20,
                'invalid' => -28,
                'unknown_key' => -20,
                'unsigned', 'n/a' => -18,
                'revoked_key' => -45,
                default => -10,
            }
        );

        $integrity = strtolower(trim((string) ($row['integrity_status'] ?? 'n/a')));
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            'integrity.' . $integrity,
            match ($integrity) {
                'valid' => 'Intégrité checksums validée',
                'tampered', 'invalid' => 'Intégrité KO',
                'missing_checksums' => 'Checksums absents',
                'unsupported_schema' => 'Schema checksums non supporté',
                default => 'Intégrité non connue',
            },
            match ($integrity) {
                'valid' => 20,
                'tampered', 'invalid' => -36,
                'missing_checksums' => -22,
                'unsupported_schema' => -14,
                default => -8,
            }
        );

        $checksumsUrl = trim((string) ($row['checksums_url'] ?? ''));
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            $checksumsUrl !== '' ? 'checksums.present' : 'checksums.absent',
            $checksumsUrl !== '' ? 'Fichier checksums publié' : 'Aucun fichier checksums publié',
            $checksumsUrl !== '' ? 8 : -12
        );

        $signatureUrl = trim((string) ($row['signature_url'] ?? ''));
        $manifest = is_array($row['manifest'] ?? null) ? $row['manifest'] : [];
        $repoStandard = $manifest !== [] && $checksumsUrl !== '' && $signatureUrl !== '';
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            $repoStandard ? 'repo.standard' : 'repo.non_standard',
            $repoStandard ? 'Repo standardisé (manifest + signature + checksums)' : 'Repo non standard CATMIN',
            $repoStandard ? 10 : -14
        );

        $catminMin = trim((string) ($row['catmin_min'] ?? ''));
        $catminMax = trim((string) ($row['catmin_max'] ?? ''));
        $compatDeclared = $catminMin !== '' || $catminMax !== '';
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            $compatDeclared ? 'compat.declared' : 'compat.undeclared',
            $compatDeclared ? 'Compatibilité CATMIN déclarée' : 'Compatibilité CATMIN floue',
            $compatDeclared ? 8 : -10
        );

        if (!((bool) ($row['compatible'] ?? true))) {
            $this->apply(
                $score,
                $signals,
                $positive,
                $negative,
                $contributions,
                'compat.incompatible',
                'Compatibilité runtime non valide',
                -24
            );
        }

        $readme = trim((string) ($row['readme_url'] ?? ''));
        $changelog = trim((string) ($row['changelog_url'] ?? ''));
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            $readme !== '' ? 'docs.readme' : 'docs.readme_missing',
            $readme !== '' ? 'README présent' : 'README absent',
            $readme !== '' ? 4 : -6
        );
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            $changelog !== '' ? 'docs.changelog' : 'docs.changelog_missing',
            $changelog !== '' ? 'CHANGELOG présent' : 'CHANGELOG absent',
            $changelog !== '' ? 4 : -5
        );

        $channel = strtolower(trim((string) ($row['release_channel'] ?? 'stable')));
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            'channel.' . $channel,
            match ($channel) {
                'stable' => 'Canal stable',
                'beta' => 'Canal beta',
                'alpha' => 'Canal alpha',
                'experimental' => 'Canal expérimental',
                default => 'Canal inconnu',
            },
            match ($channel) {
                'stable' => 10,
                'beta' => 3,
                'alpha' => -8,
                'experimental' => -16,
                default => -6,
            }
        );

        $lifecycle = strtolower(trim((string) ($row['lifecycle_status'] ?? 'active')));
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            'lifecycle.' . $lifecycle,
            match ($lifecycle) {
                'active' => 'Lifecycle actif',
                'deprecated' => 'Lifecycle deprecated',
                'abandoned' => 'Lifecycle abandonné',
                'archived' => 'Lifecycle archivé',
                'replaced' => 'Lifecycle remplacé',
                'experimental' => 'Lifecycle expérimental',
                default => 'Lifecycle inconnu',
            },
            match ($lifecycle) {
                'active' => 8,
                'deprecated' => -12,
                'abandoned' => -26,
                'archived' => -20,
                'replaced' => -8,
                'experimental' => -14,
                default => -6,
            }
        );

        $risk = strtolower(trim((string) ($row['capabilities_risk'] ?? 'low')));
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            'capabilities.' . $risk,
            match ($risk) {
                'low' => 'Capabilities raisonnables',
                'medium' => 'Capabilities modérées',
                'high' => 'Capabilities sensibles',
                'critical' => 'Capabilities critiques',
                default => 'Risk capabilities inconnu',
            },
            match ($risk) {
                'low' => 7,
                'medium' => 2,
                'high' => -14,
                'critical' => -24,
                default => -5,
            }
        );

        $keyScope = strtolower(trim((string) ($row['key_scope'] ?? 'unknown')));
        $this->apply(
            $score,
            $signals,
            $positive,
            $negative,
            $contributions,
            'key_scope.' . $keyScope,
            match ($keyScope) {
                'official' => 'Clé officielle',
                'trusted' => 'Clé trusted',
                'community' => 'Clé community',
                'local_only' => 'Clé locale seulement',
                'revoked' => 'Clé révoquée',
                'pending_trust' => 'Clé en attente de confiance',
                default => 'Scope clé inconnu',
            },
            match ($keyScope) {
                'official' => 6,
                'trusted' => 3,
                'community' => -3,
                'local_only' => -12,
                'revoked' => -35,
                'pending_trust' => -9,
                default => -4,
            }
        );

        $keyStatus = strtolower(trim((string) ($row['key_status'] ?? 'unknown')));
        if ($keyStatus !== 'unknown') {
            $this->apply(
                $score,
                $signals,
                $positive,
                $negative,
                $contributions,
                'key_status.' . $keyStatus,
                match ($keyStatus) {
                    'active' => 'Statut clé actif',
                    'deprecated' => 'Statut clé deprecated',
                    'revoked' => 'Statut clé révoqué',
                    default => 'Statut clé: ' . $keyStatus,
                },
                match ($keyStatus) {
                    'active' => 0,
                    'deprecated' => -10,
                    'revoked' => -40,
                    default => -3,
                }
            );
        }

        if (!((bool) ($row['install_allowed'] ?? true))) {
            $warnings = is_array($row['trust_warnings'] ?? null) ? $row['trust_warnings'] : [];
            $warningPenalty = min(10, max(3, count($warnings) * 2));
            $this->apply(
                $score,
                $signals,
                $positive,
                $negative,
                $contributions,
                'trust_policy.denied',
                'Policy trust: installation refusée',
                -12 - $warningPenalty
            );
        }

        $score = max(0, min(100, $score));
        $grade = $score >= 90 ? 'high' : ($score >= 70 ? 'medium' : ($score >= 45 ? 'low' : 'critical'));

        $badges = [$trust];
        if ($signature === 'signed_valid') {
            $badges[] = 'signed';
        }
        if ($integrity === 'valid') {
            $badges[] = 'integrity_ok';
        }
        if ($channel === 'stable') {
            $badges[] = 'stable';
        }
        if ($lifecycle === 'active') {
            $badges[] = 'active';
        }
        if ($risk === 'critical') {
            $badges[] = 'cap_critical';
        }

        return [
            'score' => $score,
            'grade' => $grade,
            'signals' => $signals,
            'positive_signals' => $positive,
            'negative_signals' => $negative,
            'contributions' => $contributions,
            'badges' => array_values(array_unique($badges)),
            'summary' => $this->summary($score, $grade),
            'explain' => $this->explain($contributions),
        ];
    }

    /**
     * @param array<int,string> $signals
     * @param array<int,string> $positive
     * @param array<int,string> $negative
     * @param array<int,array<string,mixed>> $contributions
     */
    private function apply(
        int &$score,
        array &$signals,
        array &$positive,
        array &$negative,
        array &$contributions,
        string $signal,
        string $label,
        int $impact
    ): void {
        $score += $impact;
        $signals[] = $signal;
        if ($impact >= 0) {
            $positive[] = $label;
        } else {
            $negative[] = $label;
        }
        $contributions[] = [
            'signal' => $signal,
            'label' => $label,
            'impact' => $impact,
            'kind' => $impact >= 0 ? 'positive' : 'negative',
        ];
    }

    /** @param array<int,array<string,mixed>> $contributions */
    private function explain(array $contributions): string
    {
        $bestPositive = null;
        $worstNegative = null;

        foreach ($contributions as $entry) {
            $impact = (int) ($entry['impact'] ?? 0);
            if ($impact > 0 && ($bestPositive === null || $impact > (int) $bestPositive['impact'])) {
                $bestPositive = $entry;
            }
            if ($impact < 0 && ($worstNegative === null || $impact < (int) $worstNegative['impact'])) {
                $worstNegative = $entry;
            }
        }

        if ($bestPositive === null && $worstNegative === null) {
            return 'Score basé sur les signaux procéduraux disponibles.';
        }

        if ($bestPositive !== null && $worstNegative !== null) {
            return sprintf(
                '+ %s (%+d) · - %s (%+d)',
                (string) ($bestPositive['label'] ?? ''),
                (int) ($bestPositive['impact'] ?? 0),
                (string) ($worstNegative['label'] ?? ''),
                (int) ($worstNegative['impact'] ?? 0)
            );
        }

        if ($bestPositive !== null) {
            return sprintf(
                'Signal principal: %s (%+d)',
                (string) ($bestPositive['label'] ?? ''),
                (int) ($bestPositive['impact'] ?? 0)
            );
        }

        return sprintf(
            'Risque principal: %s (%+d)',
            (string) ($worstNegative['label'] ?? ''),
            (int) ($worstNegative['impact'] ?? 0)
        );
    }

    private function summary(int $score, string $grade): string
    {
        return match ($grade) {
            'high' => 'Très bon alignement procédural CATMIN.',
            'medium' => 'Niveau correct avec points d’attention.',
            'low' => 'Confiance limitée, vérification manuelle requise.',
            default => 'Risque élevé, module non recommandé en production.',
        } . ' Score: ' . $score . '/100.';
    }
}
