<?php


namespace App\Repositories\Gamification;


use App\Models\User;

class RankingRepository
{
    /**
     * @var User
     */
    private $model;

    private $paginate = 10;

    public function __construct()
    {
        $this->model = new User();
    }

    /**
     * Quick description about the function
     * @param array $all
     * @return mixed
     * @author danielhe4rt - hey@danielheart.dev
     */
    public function rankingByLevel(array $all)
    {
        return $this->model
            ->orderByDesc('level')
            ->orderBy('current_exp')
            ->select(['nickname','level','current_exp'])
            ->has('messages', '>', 0)
            ->orderByDesc('messages_count')
            ->withCount('messages')
            ->paginate($this->paginate);
    }

    public function rankingByMessages(array $options)
    {
        $this->model = $this->model
            ->has('messages', '>', 0)
            ->orderByDesc('messages_count')
            ->select(['nickname','level','current_exp'])
            ->withCount('messages');

        if (array_key_exists('type', $options)) {
            if ($options['type'] == "week") {
                $this->model = $this->model
                    ->whereDate('created_at', '>' , date('Y-m-d', strtotime('-7 day')));
            }
            if ($options['type'] == "month") {
                $this->model = $this->model
                    ->whereDate('created_at', '>' , date('Y-m-d', strtotime('-30 day')));
            }
        }
        return $this->model->paginate($this->paginate);
    }
}
