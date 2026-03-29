@php($runtime = $adminRuntimeInfo ?? app(\App\Services\AdminRuntimeInfoService::class)->get())

<footer class="catmin-footer border-top bg-white">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 px-3 px-lg-4 py-2 small text-muted">
        <span>CATMIN administration</span>
        <span>
            Dashboard {{ $runtime['dashboard_version'] ?? 'n/a' }}
            · rev {{ $runtime['revision'] ?? 'n/a' }}
            · {{ !empty($runtime['dashboard_is_up_to_date']) ? 'a jour' : 'a verifier' }}
            · {{ date('Y') }}
        </span>
    </div>
</footer>
