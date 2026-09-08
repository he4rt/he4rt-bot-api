<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Timeline\DTOs;

final readonly class GithubDay
{
    public function __construct(
        public int $total,
        public int $prs,
        public int $reviews,
        public int $commits,
        public int $issues,
        public int $comments,
        public int $reviewComments,
        public int $people,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'prs' => $this->prs,
            'reviews' => $this->reviews,
            'commits' => $this->commits,
            'issues' => $this->issues,
            'comments' => $this->comments,
            'reviewComments' => $this->reviewComments,
            'people' => $this->people,
        ];
    }
}
