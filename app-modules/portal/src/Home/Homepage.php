<?php

declare(strict_types=1);

namespace He4rt\Portal\Home;

use He4rt\Portal\Articles\ArticleFeed;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(name: 'portal::components.layouts.app')]
final class Homepage extends Component
{
    private const int LATEST_ARTICLES = 3;

    public function render(ArticleFeed $feed): View
    {
        return view('portal::homepage', [
            'latestArticles' => $feed->latest(self::LATEST_ARTICLES),
        ]);
    }
}
