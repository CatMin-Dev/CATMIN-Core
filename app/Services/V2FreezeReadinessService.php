<?php

namespace App\Services;

use Carbon\Carbon;

class V2FreezeReadinessService
{
    /**
     * @return array<string, mixed>
     */
    public static function run(bool $withAutomatedTests = false): array
    {
        $validation = ValidationV2PlusService::run($withAutomatedTests, false);
        $qaGate = QaFinalGateService::run(
            withAutomatedTests: $withAutomatedTests,
            strictManual: false
        );

        $docsRequired = [
            'docs-site/RELEASE_PLAYBOOK_344.md',
            'docs-site/RELEASE_CHECKLIST_344.md',
            'docs-site/V2_STABLE_FREEZE_360.md',
            'docs-site/V2_BASELINE_REFERENCE_360.md',
            'docs-site/V3_HANDOVER_PACKAGE_360.md',
        ];

        $missingDocs = collect($docsRequired)
            ->filter(fn (string $path) => !file_exists(base_path($path)))
            ->values()
            ->all();

        $checks = [
            [
                'id' => 'validation_v2_plus',
                'label' => 'Validation V2+ complete',
                'ok' => (bool) ($validation['ok'] ?? false),
                'severity' => 'critical',
                'details' => sprintf(
                    'NOK=%d sur total=%d',
                    (int) data_get($validation, 'summary.nok', 0),
                    (int) data_get($validation, 'summary.total', 0)
                ),
            ],
            [
                'id' => 'qa_final_gate',
                'label' => 'QA Final Gate',
                'ok' => (string) ($qaGate['status'] ?? 'NOT READY') === 'READY',
                'severity' => 'critical',
                'details' => 'status=' . (string) ($qaGate['status'] ?? 'NOT READY'),
            ],
            [
                'id' => 'docs_pack_present',
                'label' => 'Documentation freeze/handover presente',
                'ok' => $missingDocs === [],
                'severity' => 'critical',
                'details' => $missingDocs === [] ? 'Pack docs complet.' : ('Manquants: ' . implode(', ', $missingDocs)),
            ],
        ];

        $blockers = collect($checks)
            ->filter(fn (array $check) => ($check['severity'] ?? 'warning') === 'critical' && !(bool) ($check['ok'] ?? false))
            ->map(fn (array $check) => (string) ($check['id'] ?? 'check'))
            ->values()
            ->all();

        $status = $blockers === [] ? 'READY_TO_FREEZE' : 'NOT_READY';

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'status' => $status,
            'blockers' => $blockers,
            'summary' => [
                'checks_total' => count($checks),
                'checks_ok' => collect($checks)->where('ok', true)->count(),
                'checks_nok' => collect($checks)->where('ok', false)->count(),
                'critical_blockers' => count($blockers),
            ],
            'scope' => self::scopePolicy(),
            'baseline' => self::baselineSnapshot(),
            'release_strategy' => self::releaseStrategy(),
            'handover' => self::handoverContract(),
            'checks' => $checks,
            'references' => [
                'release_playbook' => 'docs-site/RELEASE_PLAYBOOK_344.md',
                'release_checklist' => 'docs-site/RELEASE_CHECKLIST_344.md',
                'freeze_doc' => 'docs-site/V2_STABLE_FREEZE_360.md',
                'baseline_doc' => 'docs-site/V2_BASELINE_REFERENCE_360.md',
                'handover_doc' => 'docs-site/V3_HANDOVER_PACKAGE_360.md',
            ],
            'validation' => [
                'v2_plus' => $validation,
                'qa_final_gate' => $qaGate,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function baselineSnapshot(): array
    {
        $modules = ModuleManager::enabled()->map(fn ($m) => [
            'slug' => (string) ($m->slug ?? ''),
            'version' => (string) ($m->version ?? 'unknown'),
        ])->values()->all();

        $addons = AddonManager::enabled()->map(fn ($a) => [
            'slug' => (string) ($a->slug ?? ''),
            'version' => (string) ($a->version ?? 'unknown'),
        ])->values()->all();

        return [
            'dashboard_version' => (string) config('app.dashboard_version', 'V3-dev'),
            'development_phase' => (string) config('app.development_phase', 'v3-dev'),
            'modules_enabled' => $modules,
            'addons_enabled' => $addons,
            'support_criteria' => [
                'security_p0_closed',
                'rbac_sensitive_routes_covered',
                'release_check_no_critical',
                'qa_final_gate_ready',
                'v2_docs_up_to_date',
            ],
            'known_out_of_scope' => [
                'major_admin_ui_ux_redesign',
                'radical_visual_component_overhaul',
                'non_critical_cosmetic_optimizations',
                'gaming_fivem_integrations',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function scopePolicy(): array
    {
        return [
            'v2_stable_in_scope' => [
                'security_hardening_and_guardrails',
                'rbac_and_authorization_coverage',
                'auth_reset_2fa_operational_flows',
                'webhooks_logs_monitoring_queue_cron_reliability',
                'cms_minimum_usable_pages_articles_media',
                'api_external_stability_and_docs',
                'shop_mailer_operational_reliability',
                'install_update_rollback_packaging_stability',
            ],
            'mandatory_before_freeze' => [
                'release_check_without_critical',
                'qa_final_gate_ready',
                'v2_plus_validation_green',
                'freeze_and_handover_docs_published',
            ],
            'deferred_to_v3' => [
                'major_admin_ui_ux_refactor',
                'complete_dashboard_visual_redesign',
                'new_non_critical_feature_epics',
                'gaming_fivem_scope',
            ],
            'admission_rule' => [
                'must_close_known_gap' => 'Feature closes a P0/P1/P2 gap listed in release docs or audits.',
                'must_not_create_new_major_debt' => 'Feature does not introduce structural debt or scope expansion.',
                'must_be_testable_and_documented' => 'Feature ships with automated/manual verification and docs updates.',
                'otherwise' => 'Defer to V3 backlog with rationale and impact.',
            ],
            'defer_template' => [
                'title' => 'V3 defer: <feature>',
                'reason' => 'Why it is out of V2 scope',
                'impact' => 'Expected product/technical impact',
                'dependencies' => 'Known prerequisites',
                'target' => 'V3 phase candidate',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function releaseStrategy(): array
    {
        return [
            'tag_convention' => [
                'stable' => 'v2-stable-YYYY-MM-DD',
                'hotfix' => 'v2.0.x',
            ],
            'branching' => [
                'stable_release_branch' => 'release/v2-stable',
                'maintenance_branch' => 'maintenance/v2',
                'future_branch' => 'next/v3-ui-ux',
            ],
            'hotfix_policy' => [
                'scope' => 'critical bugs and security fixes only',
                'flow' => 'fix on maintenance/v2 -> tag v2.0.x -> cherry-pick to main when applicable',
                'no_new_features' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function handoverContract(): array
    {
        return [
            'do_not_start_v3_now' => true,
            'v3_entry_docs' => [
                'docs-site/V3_HANDOVER_PACKAGE_360.md',
                'docs-site/V2_BASELINE_REFERENCE_360.md',
                'docs-site/V2_STABLE_FREEZE_360.md',
            ],
            'do_not_break_in_v3_refactor' => [
                'permission_model_and_route_protection',
                'extension_contracts_and_manifest_rules',
                'security_guardrails_and_release_gates',
                'operational_install_update_recovery_chain',
            ],
        ];
    }
}
