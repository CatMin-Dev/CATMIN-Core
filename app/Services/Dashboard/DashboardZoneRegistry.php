<?php

namespace App\Services\Dashboard;

class DashboardZoneRegistry
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function zones(): array
    {
        return [
            ['id' => 'critical', 'label' => 'Etat critique / Health', 'order' => 10],
            ['id' => 'kpis', 'label' => 'KPIs metier', 'order' => 20],
            ['id' => 'activity', 'label' => 'Activite recente', 'order' => 30],
            ['id' => 'actions', 'label' => 'Actions rapides', 'order' => 40],
            ['id' => 'secondary', 'label' => 'Widgets addons secondaires', 'order' => 50],
        ];
    }
}
