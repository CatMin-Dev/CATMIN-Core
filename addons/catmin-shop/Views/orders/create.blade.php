@extends('admin.layouts.catmin')

@section('page_title', 'Nouvelle commande')

@section('content')
<x-admin.crud.page-header title="Nouvelle commande" subtitle="Creation admin avec decrement de stock sur statuts de paiement/traitement." />

<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ admin_route('shop.orders.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-lg-6"><label class="form-label">Nom client</label><input name="customer_name" class="form-control" value="{{ old('customer_name') }}" required></div>
                <div class="col-12 col-lg-6"><label class="form-label">Email client</label><input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}" required></div>
                <div class="col-12 col-lg-4"><label class="form-label">Telephone</label><input name="customer_phone" class="form-control" value="{{ old('customer_phone') }}"></div>
                <div class="col-12 col-lg-4"><label class="form-label">Statut initial</label><select name="status" class="form-select">@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status', 'pending') === $status)>{{ $status }}</option>@endforeach</select></div>
                <div class="col-12 col-lg-4"><label class="form-label">Devise</label><input name="currency" class="form-control" value="{{ old('currency', 'EUR') }}" maxlength="3" required></div>
                <div class="col-12 col-lg-4"><label class="form-label">TVA</label><input type="number" step="0.01" min="0" name="tax_total" class="form-control" value="{{ old('tax_total', 0) }}"></div>
                <div class="col-12 col-lg-4"><label class="form-label">Livraison</label><input type="number" step="0.01" min="0" name="shipping_total" class="form-control" value="{{ old('shipping_total', 0) }}"></div>
                <div class="col-12"><label class="form-label">Notes admin</label><textarea name="admin_notes" rows="3" class="form-control">{{ old('admin_notes') }}</textarea></div>
                <div class="col-12"><h2 class="h6 mb-3">Lignes de commande</h2></div>
                @for($i = 0; $i < 3; $i++)
                    <div class="col-12 col-lg-8">
                        <label class="form-label">Produit {{ $i + 1 }}</label>
                        <select name="items[{{ $i }}][product_id]" class="form-select">
                            <option value="">Choisir un produit</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected((string) old('items.' . $i . '.product_id') === (string) $product->id)>{{ $product->name }} ({{ number_format((float) $product->price, 2, '.', ' ') }} {{ 'EUR' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Quantite {{ $i + 1 }}</label>
                        <input type="number" min="1" name="items[{{ $i }}][quantity]" class="form-control" value="{{ old('items.' . $i . '.quantity', 1) }}">
                    </div>
                @endfor
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Creer la commande</button><a class="btn btn-outline-secondary" href="{{ admin_route('shop.orders.index') }}">Retour</a></div>
            </form>
        </div>
    </div>
</div>
@endsection
