<?php

namespace Addons\CatminCrmLight\Services;

use Addons\CatminCrmLight\Models\CrmContact;
use Illuminate\Support\Facades\DB;

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

        $contact->update([
            'pipeline_stage' => $toStage,
            'status' => $this->legacyStatusFromStage($toStage),
        ]);

        return $contact->fresh() ?? $contact;
    }

    /**
     * @return array<string,int>
     */
    public function metrics(): array
    {
        $rows = CrmContact::query()
            ->select('pipeline_stage', DB::raw('COUNT(*) as total'))
            ->groupBy('pipeline_stage')
            ->pluck('total', 'pipeline_stage')
            ->all();

        $result = [];
        foreach ($this->stages() as $stage) {
            $result[$stage] = (int) ($rows[$stage] ?? 0);
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
