<?php

declare(strict_types=1);

namespace He4rt\Portal\Live;

use He4rt\Live\Actions\CheckLiveStatusAction;
use He4rt\Live\Audience\Actions\RecordViewerHeartbeat;
use He4rt\Live\Models\Live;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(name: 'portal::components.layouts.app')]
final class LivePage extends Component
{
    public int $viewers = 0;

    public function pulse(): void
    {
        rescue(function (): void {
            $live = Live::query()->current()->first();

            if ($live !== null) {
                $this->viewers = resolve(RecordViewerHeartbeat::class)->execute($live, session()->getId());
            }
        }, report: true);
    }

    public function render(CheckLiveStatusAction $status): View
    {
        $live = Live::query()->current()->first();

        return view('portal::live', [
            'live' => $live,
            'status' => $status->execute(),
        ]);
    }
}
