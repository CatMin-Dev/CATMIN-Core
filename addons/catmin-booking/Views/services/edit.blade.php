@extends('admin.layouts.catmin')

@section('page_title', 'Booking · Modifier service')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Modifier service</h1>
        <p class="text-muted mb-0">{{ $serviceItem->name }}</p>
    </div>
    <a href="{{ route('admin.booking.services.index') }}" class="btn btn-outline-secondary">Retour</a>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.booking.services.update', $serviceItem->id) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $serviceItem->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $serviceItem->slug) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Durée (min)</label>
                    <input type="number" min="5" max="480" step="5" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $serviceItem->duration_minutes) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prix (€)</label>
                    <input type="number" min="0" step="0.01" name="price" class="form-control" value="{{ old('price', number_format($serviceItem->price_cents / 100, 2, '.', '')) }}">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="is_active" id="service_active" @checked(old('is_active', $serviceItem->is_active))>
                        <label class="form-check-label" for="service_active">Actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4" class="form-control">{{ old('description', $serviceItem->description) }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
