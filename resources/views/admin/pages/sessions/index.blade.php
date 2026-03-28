@extends('admin.layouts.catmin')

@section('page_title', 'Sessions actives')

@section('content')
<x-admin.crud.page-header
    title="Sessions actives"
    subtitle="Visualiser et revoquer les sessions admin actives pour ce compte."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="mb-3">
        <form method="POST" action="{{ route('admin.sessions.revoke-others') }}" onsubmit="return confirm('Revoquer toutes les autres sessions ?');">
            @csrf
            <button class="btn btn-outline-danger btn-sm" type="submit">
                <i class="bi bi-x-octagon me-1"></i>Revoquer toutes les autres sessions
            </button>
        </form>
    </div>

    <x-admin.crud.table-card
        title="Sessions"
        :count="count($sessions)"
        :empty-colspan="6"
        empty-message="Aucune session active enregistree."
    >
        <x-slot:head>
            <tr>
                <th>Session</th>
                <th>IP</th>
                <th>User-Agent</th>
                <th>Creee le</th>
                <th>Derniere activite</th>
                <th class="text-end">Action</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($sessions as $row)
                @php($isCurrent = $row['session_id'] === $currentSessionId)
                <tr>
                    <td>
                        <code>{{ substr($row['session_id'], 0, 20) }}...</code>
                        @if($isCurrent)
                            <span class="badge text-bg-primary ms-1">Courante</span>
                        @endif
                    </td>
                    <td>{{ $row['ip_address'] ?: 'n/a' }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($row['user_agent'] ?: 'n/a', 80) }}</td>
                    <td>{{ $row['created_at'] ? \Carbon\Carbon::parse($row['created_at'])->format('d/m/Y H:i') : 'n/a' }}</td>
                    <td>{{ $row['last_activity_at'] ? \Carbon\Carbon::parse($row['last_activity_at'])->diffForHumans() : 'n/a' }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('admin.sessions.revoke') }}" onsubmit="return confirm('Revoquer cette session ?');" class="d-inline">
                            @csrf
                            <input type="hidden" name="session_id" value="{{ $row['session_id'] }}">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Revoquer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
