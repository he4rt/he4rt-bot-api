<?php


namespace App\Console\Commands;


use App\Models\Gamification\Season;
use App\Models\User\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate users from legacy database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::connection('legacy')
            ->table('users')
            ->orderBy('id')
            ->chunk(500, function ($users) {
                foreach($users as $user) {
                    User::create([
                        'discord_id' => $user->discord_id,
                        'name' =>  $user->name,
                        'nickname' => $user->nickname,
                        'git' => $user->git,
                        'about' => $user->about,
                        'level' => $user->level,
                        'current_exp' => $user->current_exp,
                        'money' => $user->money,
                        'daily' => $user->daily,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at
                    ]);
                }
            });

        return true;
    }
}
