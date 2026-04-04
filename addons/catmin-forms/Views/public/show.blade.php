@extends('frontend.layouts.base')

@section('meta_title', $form->name . ' - ' . $siteName)

@section('content')
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">{{ $form->name }}</h1>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('frontend.forms.submit', $form->slug) }}" class="row g-3" novalidate>
                        @csrf
                        <input type="text" name="website_url" value="" class="d-none" tabindex="-1" autocomplete="off">

                        @foreach($form->fields as $field)
                            <div class="col-12">
                                <label class="form-label">{{ $field->label }} @if($field->is_required)<span class="text-danger">*</span>@endif</label>

                                @if($field->type === 'textarea')
                                    <textarea class="form-control @error($field->key) is-invalid @enderror" name="{{ $field->key }}" rows="4">{{ old($field->key) }}</textarea>
                                @elseif($field->type === 'select')
                                    <select class="form-select @error($field->key) is-invalid @enderror" name="{{ $field->key }}">
                                        <option value="">Choisir...</option>
                                        @foreach(($field->options ?? []) as $option)
                                            <option value="{{ $option }}" @selected(old($field->key) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="{{ $field->type === 'phone' ? 'text' : $field->type }}" class="form-control @error($field->key) is-invalid @enderror" name="{{ $field->key }}" value="{{ old($field->key) }}">
                                @endif

                                @error($field->key)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach

                        <div class="col-12">
                            <button class="btn btn-primary">Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
