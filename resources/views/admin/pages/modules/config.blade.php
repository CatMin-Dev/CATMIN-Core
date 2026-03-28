@extends('admin.layouts.catmin')

@section('page_title', 'Configuration module')

@section('content')
<x-admin.crud.page-header
    :title="'Configuration: ' . $module->name"
    :subtitle="'Parametres valides et stockes pour le module ' . $module->slug . '.'"
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Parametres</h2>
            <a href="{{ route('admin.modules.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour modules
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.modules.config.update', $module->slug) }}" class="row g-3">
                @csrf

                @foreach($fields as $field)
                    @php
                        $key = $field['key'];
                        $type = $field['type'] ?? 'string';
                        $value = old($key, $values[$key] ?? ($field['default'] ?? null));
                    @endphp
                    <div class="col-12 {{ $type === 'boolean' ? '' : 'col-lg-6' }}">
                        <label class="form-label fw-semibold" for="{{ $key }}">{{ $field['label'] ?? $key }}</label>

                        @if($type === 'boolean')
                            <div class="form-check mt-1">
                                <input id="{{ $key }}" name="{{ $key }}" type="hidden" value="0">
                                <input id="{{ $key }}" name="{{ $key }}" type="checkbox" class="form-check-input" value="1" @checked((bool) $value)>
                                <label class="form-check-label" for="{{ $key }}">Activer</label>
                            </div>
                        @else
                            <input
                                id="{{ $key }}"
                                name="{{ $key }}"
                                type="{{ $type === 'integer' ? 'number' : 'text' }}"
                                value="{{ $value }}"
                                class="form-control @error($key) is-invalid @enderror"
                            >
                        @endif

                        @if(!empty($field['help']))
                            <div class="form-text">{{ $field['help'] }}</div>
                        @endif

                        @error($key)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
