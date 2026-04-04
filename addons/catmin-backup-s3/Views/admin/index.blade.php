@extends('admin.layouts.catmin')

@section('page_title', 'Backup Distant')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Backup distant</h1>
        <p class="text-muted mb-0">Providers supportés: S3, Google Cloud Storage, SFTP, FTP.</p>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(!empty($connectionError))
        <div class="alert alert-warning">Erreur connexion remote: {{ $connectionError }}</div>
    @endif

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header bg-white"><strong>Configuration provider</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.backup.remote.settings.update') }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-md-4">
                            <label class="form-label">Activé</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1" @checked(!empty($settings['enabled']))>
                                <label class="form-check-label" for="enabled">Backup distant actif</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Provider</label>
                            <select name="provider" id="provider" class="form-select" required>
                                <option value="s3" @selected(($settings['provider'] ?? 's3') === 's3')>S3 / compatible</option>
                                <option value="google" @selected(($settings['provider'] ?? '') === 'google')>Google Cloud Storage</option>
                                <option value="sftp" @selected(($settings['provider'] ?? '') === 'sftp')>SFTP</option>
                                <option value="ftp" @selected(($settings['provider'] ?? '') === 'ftp')>FTP</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Préfixe remote</label>
                            <input type="text" name="prefix" class="form-control" value="{{ $settings['prefix'] ?? 'catmin/backups' }}" placeholder="catmin/backups">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rétention max</label>
                            <input type="number" min="1" max="3650" name="retention_max" class="form-control" value="{{ $settings['retention_max'] ?? 15 }}">
                        </div>

                        <div id="provider-s3" class="provider-panel row g-3">
                            <div class="col-12"><hr><h6 class="mb-0">S3 / compatible</h6></div>
                            <div class="col-md-6">
                                <label class="form-label">Endpoint</label>
                                <input type="text" name="endpoint" class="form-control" value="{{ $settings['endpoint'] ?? '' }}" placeholder="https://s3.eu-west-1.amazonaws.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Region</label>
                                <input type="text" name="region" class="form-control" value="{{ $settings['region'] ?? '' }}" placeholder="eu-west-1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bucket</label>
                                <input type="text" name="bucket" class="form-control" value="{{ $settings['bucket'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Access key</label>
                                <input type="text" name="access_key" class="form-control" value="{{ $settings['access_key'] ?? '' }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Secret key</label>
                                <input type="password" name="secret_key" class="form-control" placeholder="{{ $settings['secret_key_masked'] ?? 'laisser vide pour conserver la valeur existante' }}">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="use_path_style_endpoint" id="path_style" value="1" @checked(!empty($settings['use_path_style_endpoint']))>
                                    <label class="form-check-label" for="path_style">Use path-style endpoint</label>
                                </div>
                            </div>
                        </div>

                        <div id="provider-google" class="provider-panel row g-3 d-none">
                            <div class="col-12"><hr><h6 class="mb-0">Google Cloud Storage</h6></div>
                            <div class="col-md-6">
                                <label class="form-label">Project ID</label>
                                <input type="text" name="google_project_id" class="form-control" value="{{ $settings['google_project_id'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bucket</label>
                                <input type="text" name="google_bucket" class="form-control" value="{{ $settings['google_bucket'] ?? '' }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Service account JSON</label>
                                <textarea name="google_service_account_json" class="form-control" rows="5" placeholder="{{ $settings['google_service_account_json_masked'] ?? 'Coller le JSON service account (laisse vide pour conserver)' }}"></textarea>
                            </div>
                        </div>

                        <div id="provider-sftp" class="provider-panel row g-3 d-none">
                            <div class="col-12"><hr><h6 class="mb-0">SFTP</h6></div>
                            <div class="col-md-4">
                                <label class="form-label">Host</label>
                                <input type="text" name="host" class="form-control" value="{{ $settings['host'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Port</label>
                                <input type="number" name="port" class="form-control" value="{{ $settings['port'] ?? 22 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="{{ $settings['username'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="{{ $settings['password_masked'] ?? 'vide=conserver' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Root path</label>
                                <input type="text" name="root" class="form-control" value="{{ $settings['root'] ?? '/backups/catmin' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Timeout (s)</label>
                                <input type="number" name="timeout" class="form-control" value="{{ $settings['timeout'] ?? 30 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Private key (optional)</label>
                                <textarea name="private_key" class="form-control" rows="2" placeholder="{{ $settings['private_key_masked'] ?? '-----BEGIN OPENSSH PRIVATE KEY-----' }}"></textarea>
                            </div>
                        </div>

                        <div id="provider-ftp" class="provider-panel row g-3 d-none">
                            <div class="col-12"><hr><h6 class="mb-0">FTP</h6></div>
                            <div class="col-md-4">
                                <label class="form-label">Host</label>
                                <input type="text" name="host" class="form-control" value="{{ $settings['host'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Port</label>
                                <input type="number" name="port" class="form-control" value="{{ $settings['port'] ?? 21 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="{{ $settings['username'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="{{ $settings['password_masked'] ?? 'vide=conserver' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Root path</label>
                                <input type="text" name="root" class="form-control" value="{{ $settings['root'] ?? '/backups/catmin' }}">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="passive" id="passive" value="1" @checked(!empty($settings['passive']))>
                                    <label class="form-check-label" for="passive">Passive mode</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="ssl" id="ssl" value="1" @checked(!empty($settings['ssl']))>
                                    <label class="form-check-label" for="ssl">SSL/TLS</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Enregistrer configuration</button>
                        </div>
                    </form>
                    <div class="mt-3">
                        <form method="POST" action="{{ route('admin.backup.remote.test') }}" class="d-inline">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm" type="submit">Tester connexion</button>
                        </form>
                        <form method="POST" action="{{ route('admin.backup.remote.retention') }}" class="d-inline">
                            @csrf
                            <button class="btn btn-outline-warning btn-sm" type="submit">Executer retention</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header bg-white"><strong>Backups locaux</strong></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Taille</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($localBackups as $backup)
                                <tr>
                                    <td><code>{{ $backup['name'] }}</code></td>
                                    <td>{{ number_format(((int) $backup['size']) / 1024 / 1024, 2) }} MB</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($backup['created_at'])->format('d/m/Y H:i') }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.backup.remote.upload') }}">
                                            @csrf
                                            <input type="hidden" name="backup_name" value="{{ $backup['name'] }}">
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Upload remote</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Aucun backup local.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between">
                    <strong>Backups distants</strong>
                    <span class="badge text-bg-light">{{ count($remoteBackups ?? []) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Path</th>
                                <th>Taille</th>
                                <th>Date</th>
                                <th>Source</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($remoteBackups ?? []) as $item)
                                <tr>
                                    <td><code class="small">{{ $item['path'] }}</code></td>
                                    <td>{{ number_format(((int) ($item['size'] ?? 0)) / 1024 / 1024, 2) }} MB</td>
                                    <td>
                                        @if(!empty($item['last_modified']))
                                            {{ \Illuminate\Support\Carbon::createFromTimestamp((int) $item['last_modified'])->format('d/m/Y H:i') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><span class="badge text-bg-secondary">{{ strtoupper((string) ($item['source'] ?? '?')) }}</span></td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.backup.remote.download') }}">
                                            @csrf
                                            <input type="hidden" name="remote_path" value="{{ $item['path'] }}">
                                            <button class="btn btn-sm btn-outline-success" type="submit">Download</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Aucun backup distant ou provider non connecté.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const provider = document.getElementById('provider');
    const panels = document.querySelectorAll('.provider-panel');

    function togglePanels(value) {
        panels.forEach((panel) => panel.classList.add('d-none'));
        const active = document.getElementById('provider-' + value);
        if (active) active.classList.remove('d-none');
    }

    provider.addEventListener('change', function () {
        togglePanels(provider.value);
    });

    togglePanels(provider.value);
})();
</script>
@endpush
@endsection
