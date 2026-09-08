<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Interactions\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\PanelAdmin\Filament\Resources\Interactions\InteractionResource;
use Illuminate\Database\Eloquent\Builder;

class ListInteractions extends ListRecords
{
    protected static string $resource = InteractionResource::class;

    /** @var array<string, int>|null */
    private ?array $typeCounts = null;

    /**
     * O contador é o total do tipo no banco, não o que os outros filtros deixam
     * passar: filtrar por Origem estreita a tabela sem mexer no badge. Contribuição
     * oculta conta junto — some do perfil, não do inventário.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $counts = $this->typeCounts();

        $tabs = ['all' => Tab::make('Todas')->badge(array_sum($counts))];

        foreach (ActivityType::cases() as $type) {
            // A chave vira o activeTab da URL, então usa o valor do enum.
            $tabs[$type->value] = Tab::make($type->getLabel())
                ->badge($counts[$type->value] ?? 0)
                ->badgeColor($type->getColor())
                ->modifyQueryUsing(static fn (Builder $query): Builder => $query->where('type', $type));
        }

        return $tabs;
    }

    /**
     * Uma query para as nove tabs. O Filament chama getTabs() mais de uma vez por
     * render, então o resultado fica preso ao request.
     *
     * @return array<string, int>
     */
    private function typeCounts(): array
    {
        if ($this->typeCounts !== null) {
            return $this->typeCounts;
        }

        $rows = Interaction::query()
            ->toBase()
            ->selectRaw('type, COUNT(*) AS total')
            ->groupBy('type')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $columns = (array) $row;
            $type = $columns['type'] ?? null;
            $total = $columns['total'] ?? null;

            if (is_string($type)) {
                $counts[$type] = is_numeric($total) ? (int) $total : 0;
            }
        }

        return $this->typeCounts = $counts;
    }
}
