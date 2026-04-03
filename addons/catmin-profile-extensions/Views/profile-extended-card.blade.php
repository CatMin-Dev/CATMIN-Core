<div class="card mb-4">
    <div class="card-header bg-white">
        <h2 class="h6 mb-0">Profil étendu (contact, adresse, identité)</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.profile.extensions.update') }}" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-12 col-md-6">
                <label class="form-label" for="ext_phone">Téléphone</label>
                <input id="ext_phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $profileExtension['phone'] ?? '') }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="ext_mobile">Mobile</label>
                <input id="ext_mobile" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $profileExtension['mobile'] ?? '') }}">
                @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="ext_company_name">Société</label>
                <input id="ext_company_name" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $profileExtension['company_name'] ?? '') }}">
                @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="ext_country_code">Pays (ISO-2)</label>
                <input id="ext_country_code" name="country_code" maxlength="2" class="form-control text-uppercase @error('country_code') is-invalid @enderror" value="{{ old('country_code', $profileExtension['country_code'] ?? '') }}">
                @error('country_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label" for="ext_address_line_1">Adresse (ligne 1)</label>
                <input id="ext_address_line_1" name="address_line_1" class="form-control @error('address_line_1') is-invalid @enderror" value="{{ old('address_line_1', $profileExtension['address_line_1'] ?? '') }}">
                @error('address_line_1')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label" for="ext_address_line_2">Adresse (ligne 2)</label>
                <input id="ext_address_line_2" name="address_line_2" class="form-control @error('address_line_2') is-invalid @enderror" value="{{ old('address_line_2', $profileExtension['address_line_2'] ?? '') }}">
                @error('address_line_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="ext_postal_code">Code postal</label>
                <input id="ext_postal_code" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code', $profileExtension['postal_code'] ?? '') }}">
                @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="ext_city">Ville</label>
                <input id="ext_city" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $profileExtension['city'] ?? '') }}">
                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="ext_state">Etat/Région</label>
                <input id="ext_state" name="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $profileExtension['state'] ?? '') }}">
                @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="ext_identity_type">Type identité</label>
                <input id="ext_identity_type" name="identity_type" class="form-control @error('identity_type') is-invalid @enderror" value="{{ old('identity_type', $profileExtension['identity_type'] ?? '') }}" placeholder="passport, national_id, vat">
                @error('identity_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="ext_identity_number">Numéro identité</label>
                <input id="ext_identity_number" name="identity_number" class="form-control @error('identity_number') is-invalid @enderror" value="{{ old('identity_number', $profileExtension['identity_number'] ?? '') }}">
                @error('identity_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="ext_preferred_contact_method">Méthode de contact préférée</label>
                <select id="ext_preferred_contact_method" name="preferred_contact_method" class="form-select @error('preferred_contact_method') is-invalid @enderror">
                    @php($method = old('preferred_contact_method', $profileExtension['preferred_contact_method'] ?? ''))
                    <option value="">Aucune</option>
                    <option value="email" @selected($method === 'email')>Email</option>
                    <option value="phone" @selected($method === 'phone')>Téléphone</option>
                    <option value="mobile" @selected($method === 'mobile')>Mobile</option>
                    <option value="sms" @selected($method === 'sms')>SMS</option>
                    <option value="whatsapp" @selected($method === 'whatsapp')>WhatsApp</option>
                </select>
                @error('preferred_contact_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="ext_contact_opt_in" name="contact_opt_in" value="1" @checked(old('contact_opt_in', $profileExtension['contact_opt_in'] ?? false))>
                    <label class="form-check-label" for="ext_contact_opt_in">Accepte d'être contacté</label>
                </div>
            </div>

            <div class="col-12">
                <button class="btn btn-primary" type="submit">Enregistrer le profil étendu</button>
            </div>
        </form>
    </div>
</div>
