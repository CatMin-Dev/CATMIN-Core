@extends('admin.layouts.catmin')

@section('page_title', 'Webhooks')

@section('content')
<x-admin.crud.page-header
    title="Webhooks sortants"
    subtitle="Envoyer des notifications HTTP signées HMAC-SHA256 vers des services tiers."
    :actions="[['label' => 'Nouveau webhook', 'url' => route('admin.webhooks.create'), 'icon' => 'bi bi-plus-lg', 'style' => 'primary']]"
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <x-admin.crud.table-card title="Webhooks enregistrés ({{ $webhooks->total() }})">
        <thead class="table-light">
            <tr>
                <th>Nom</th>
                <th>URL</th>
                <th>Événements</th>
                <th>Statut</th>
                <th>Dernière exéc.</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($webhooks as $webhook)
            <tr>
                <td class="fw-semibold">{{ $webhook->name }}</td>
                <td class="small text-muted" style="max-width:220px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" title="{{ $webhook->url }}">
                    {{ $webhook->url }}
                </td>
                <td>
                    @foreach($webhook->events ?? [] as $event)
                        <span class="badge bg-light text-dark border me-1">{{ $event }}</span>
                    @endforeach
                </td>
                <td>
                    @if($webhook->status === 'active')
                        <span class="badge bg-success">Actif</span>
                    @else
                        <span class="badge bg-secondary">Inactif</span>
                    @endif
                </td>
                <td class="small text-muted text-nowrap">
                    {{ $webhook->last_triggered_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.webhooks.edit', $webhook->id) }}"
                       class="btn btn-sm btn-outline-secondary me-1" title="Modifier">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form class="d-inline" method="POST" action="{{ route('admin.webhooks.destroy', $webhook->id) }}"
                          onsubmit="return confirm('Supprimer le webhook « {{ addslashes($webhook->name) }} » ?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Supprimer">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    Aucun webhook configuré.
                    <a href="{{ route('admin.webhooks.create') }}">En créer un ?</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </x-admin.crud.table-card>

    @if($webhooks->hasPages())
    <div class="mt-3">{{ $webhooks->links() }}</div>
    @endif

    {{-- Incoming endpoint info --}}
    <div class="card mt-4">
        <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-arrow-down-circle me-1"></i>Endpoint entrant</h2></div>
        <div class="card-body">
            <p class="small text-muted mb-2">
                Pour recevoir des webhooks, envoyez un <code>POST</code> vers l'URL ci-dessous.<br>
                Définissez <code>CATMIN_WEBHOOK_INCOMING_TOKEN</code> dans votre <code>.env</code> pour sécuriser l'accès.
            </p>
            <pre class="bg-light rounded p-3 small mb-0">POST {{ url('/webhooks/incoming/{votre-token}') }}</pre>
        </div>
    </div>
</div>
@endsection
