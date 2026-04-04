@extends('admin.layouts.catmin')

@section('page_title', 'Addon Bundles')

@section('content')
<x-admin.crud.page-header
    title="Bundles Addons"
    subtitle="Activation de packs metier preconfigures."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Bundle</th>
                        <th>Compatibilite</th>
                        <th>Modules requis</th>
                        <th>Addons inclus</th>
                        <th>Installe</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bundles as $bundle)
                        @php
                            $compat = $bundle['compatibility'] ?? ['compatible' => false, 'missing_addons' => [], 'missing_modules' => []];
                            $state = $bundleState['bundles'][$bundle['slug']] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $bundle['name'] ?? $bundle['slug'] }}</div>
                                <div class="small text-muted">{{ $bundle['description'] ?? '' }}</div>
                                <div class="small text-muted">{{ $bundle['slug'] }}</div>
                            </td>
                            <td>
                                @if(!empty($compat['compatible']))
                                    <span class="badge text-bg-success">compatible</span>
                                @else
                                    <span class="badge text-bg-danger">incompatible</span>
                                    @if(!empty($compat['missing_addons']))
                                        <div class="small text-danger mt-1">addons: {{ implode(', ', $compat['missing_addons']) }}</div>
                                    @endif
                                    @if(!empty($compat['missing_modules']))
                                        <div class="small text-danger">modules: {{ implode(', ', $compat['missing_modules']) }}</div>
                                    @endif
                                @endif
                            </td>
                            <td>{{ empty($bundle['required_modules']) ? 'aucun' : implode(', ', $bundle['required_modules']) }}</td>
                            <td>
                                <div class="small">{{ empty($bundle['addons_included']) ? 'aucun' : implode(', ', $bundle['addons_included']) }}</div>
                                @if(!empty($bundle['optional_addons']))
                                    <div class="small text-muted">optionnels: {{ implode(', ', $bundle['optional_addons']) }}</div>
                                @endif
                            </td>
                            <td>
                                @if($state)
                                    <span class="badge text-bg-success">oui</span>
                                    <div class="small text-muted">{{ $state['installed_at'] ?? '' }}</div>
                                @else
                                    <span class="badge text-bg-light text-dark">non</span>
                                @endif
                            </td>
                            <td>
                                <form method="post" action="{{ route('admin.addons.bundles.install') }}">
                                    @csrf
                                    <input type="hidden" name="slug" value="{{ $bundle['slug'] }}">
                                    <button class="btn btn-sm btn-primary" type="submit" {{ !empty($compat['compatible']) ? '' : 'disabled' }}>
                                        Installer bundle
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Aucun bundle detecte dans bundles/*.bundle.json</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
