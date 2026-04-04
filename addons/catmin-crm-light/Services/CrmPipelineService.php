<?php

namespace Addons\CatminCrmLight\Services;

use Addons\CatminCrmLight\Models\CrmContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrmPipelineService
{
    /**
     * @return array<int,string>
     */
    public function stages(): array
    {
        return ['new', 'contacted', 'qualified', 'won', 'lost', 'archived'];
    }

    public function move(CrmContact $contact, string $toStage): CrmContact
    {
        if (!in_array($toStage, $this->stages(), true)) {
            throw new \InvalidArgumentException('Etape pipeline invalide.');
        }

        $updates = [
            'status' => $this->legacyStatusFromStage($toStage),
        ];

        if (Schema::hasTable('crm_contacts') && Schema::hasColumn('crm_contacts', 'pipeline_stage')) {
            $updates['pipeline_stage'] = $toStage;
        }

        $contact->update($updates);

        return $contact->fresh() ?? $contact;
    }

    /**
     * @return array<string,int>
     */
    public function metrics(): array
    {
        $rows = [];

        if (Schema::hasTable('crm_contacts') && Schema::hasColumn('crm_contacts', 'pipeline_stage')) {
            $rows = CrmContact::query()
                ->select('pipeline_stage', DB::raw('COUNT(*) as total'))
                ->groupBy('pipeline_stage')
                ->pluck('total', 'pipeline_stage')
                ->all();
        }

        $result = [];
        foreach ($this->stages() as $stage) {
            $result[$stage] = (int) ($rows[$stage] ?? 0);
        }

        if ($rows === [] && Schema::hasTable('crm_contacts')) {
            $leadCount = (int) CrmContact::query()->where('status', 'lead')->count();
            $activeCount = (int) CrmContact::query()->where('status', 'active')->count();
            $inactiveCount = (int) CrmContact::query()->where('status', 'inactive')->count();

            $result['new'] = $leadCount;
            $result['won'] = $activeCount;
            $result['lost'] = $inactiveCount;
        }

        return $result;
    }

    private function legacyStatusFromStage(string $stage): string
    {
        return match ($stage) {
            'won' => 'active',
            'lost', 'archived' => 'inactive',
            default => 'lead',
        };
    }
}
