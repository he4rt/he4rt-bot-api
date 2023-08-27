<?php

namespace Heart\Team\Infrastructure\Providers;

use Heart\Team\Presentation\Controllers\TeamController;
use Heart\Team\Presentation\Controllers\TeamInvitationController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class TeamRouteProvider extends RouteServiceProvider
{
    public function map(): void
    {
        Route::prefix('/teams')->name('teams.')->group(function () {
            Route::get('/', [TeamController::class, 'getTeams'])->name('index');
            Route::post('/', [TeamController::class, 'postTeam'])->name('store');
            Route::get('/{team}', [TeamController::class, 'getTeam'])->name('show');
            Route::put('/{team}', [TeamController::class, 'update'])->name('update');
            Route::delete('/{team}', [TeamController::class, 'destroy'])->name('destroy');
//            Route::put('/current-team', [CurrentTeamController::class, 'update'])->name('current-team.update');
            Route::get('/invite/{user_id}', [TeamInvitationController::class, 'listInvites'])->name('invite.list');
            Route::post('/{team}/invite', [TeamInvitationController::class, 'postInvite'])->name('invite.store');
//            Route::put('/teams/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('team-members.update');
//            Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('team-members.destroy');
//
            Route::post('/invite/{invite_id}', [TeamInvitationController::class, 'handleInvite'])
                ->name('handle.invite');
        });
    }
}
