<?php

namespace Addons\CatminCrmLight\Services;

use Addons\CatminCrmLight\Models\CrmCompany;
use Addons\CatminCrmLight\Models\CrmContact;

class CrmRelationService
{
    public function attachContactToCompany(CrmContact $contact, ?int $companyId): CrmContact
    {
        $company = $companyId !== null ? CrmCompany::query()->find($companyId) : null;

        $contact->update([
            'crm_company_id' => $company?->id,
        ]);

        return $contact->fresh() ?? $contact;
    }

    /**
     * @return array<string,mixed>
     */
    public function contactRelationSnapshot(CrmContact $contact): array
    {
        $contact->loadMissing('company');

        return [
            'contact_id' => (int) $contact->id,
            'company_id' => $contact->company?->id,
            'company_name' => $contact->company?->name,
            'company_contacts_count' => $contact->company
                ? $contact->company->contacts()->count()
                : 0,
        ];
    }
}
