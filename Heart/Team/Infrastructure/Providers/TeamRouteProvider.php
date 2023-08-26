<?php

namespace Heart\Team\Infrastructure\Providers;

use Heart\Team\Presentation\Controllers\TeamController;
use Heart\Team\Presentation\Controllers\TeamInvitationController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class TeamRouteProvider extends RouteServiceProvider
{
    public function map()
    {
        Route::prefix('/teams')->group(function() {
            Route::get('/', [TeamController::class, 'getTeams'])->name('teams.index');
            Route::post('/', [TeamController::class, 'postTeam'])->name('teams.store');
            Route::get('/{team}', [TeamController::class, 'getTeam'])->name('teams.show');
//            Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
//            Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
//            Route::put('/current-team', [CurrentTeamController::class, 'update'])->name('current-team.update');
            Route::post('/{team}/members', [TeamInvitationController::class, 'postInvite'])->name('teams.invite.store');
//            Route::put('/teams/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('team-members.update');
//            Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('team-members.destroy');
//
//            Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
//                ->middleware(['signed'])
//                ->name('team-invitations.accept');
//
//            Route::delete('/team-invitations/{invitation}', [TeamInvitationController::class, 'destroy'])
//                ->name('team-invitations.destroy');
        });


    }
}
