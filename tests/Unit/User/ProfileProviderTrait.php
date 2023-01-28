<?php

namespace Tests\Unit\User;

use Heart\User\Domain\Entities\ProfileEntity;

trait ProfileProviderTrait
{
    public function validProfilePayload(array $fields = []): array
    {
        return [
            "id" => "6ed043a2-c567-333d-abe8-ccf4f86eb4a6",
            "username" => "bailey.hal",
            "is_donator" => 0,
            "created_at" => "2023-01-28T22:11:45.000000Z",
            "updated_at" => "2023-01-28T22:11:45.000000Z",
            "character" => [
                "id" => "c58fde47-ec6f-31c2-82e1-0c929a9e181d",
                "user_id" => "6ed043a2-c567-333d-abe8-ccf4f86eb4a6",
                "experience" => 614,
                "reputation" => 10,
                "daily_bonus_claimed_at" => "2023-01-28 19:11:45",
                "created_at" => "2023-01-28T22:11:45.000000Z",
                "updated_at" => "2023-01-28T22:11:45.000000Z",
                "ranking" => 1,
                "badges" => [
                    [
                        "id" => 1,
                        "provider" => "72275e81-e1a1-30fe-8142-6995443c2959",
                        "name" => "Mr. Gussie Willms",
                        "description" => "Veniam soluta minus esse quo eos qui.",
                        "image_url" => "https://via.placeholder.com/640x480.png/0022ff?text=id",
                        "redeem_code" => "ut-tempora",
                        "active" => true,
                        "created_at" => "2023-01-28T22:11:45.000000Z",
                        "updated_at" => "2023-01-28T22:11:45.000000Z",
                        "pivot" => [
                            "character_id" => "c58fde47-ec6f-31c2-82e1-0c929a9e181d",
                            "badge_id" => 1,
                            "claimed_at" => "2023-01-28 19:11:45"
                        ]
                    ]
                ],
                "past_seasons" => [
                    [
                        "id" => 1,
                        "season_id" => "2",
                        "character_id" => "c58fde47-ec6f-31c2-82e1-0c929a9e181d",
                        "ranking_position" => 220,
                        "experience" => 566,
                        "messages_count" => 695,
                        "badges_count" => 547,
                        "meetings_count" => 144,
                        "created_at" => "2023-01-28T22:11:45.000000Z",
                        "updated_at" => "2023-01-28T22:11:45.000000Z"
                    ]
                ]
            ],
            "providers" => [
                [
                    "id" => "d8991eec-7d81-3875-977a-d41f2963545c",
                    "user_id" => "6ed043a2-c567-333d-abe8-ccf4f86eb4a6",
                    "provider" => "twitch",
                    "provider_id" => "561189",
                    "email" => "jleannon@yahoo.com",
                    "created_at" => "2023-01-28T22:11:45.000000Z",
                    "updated_at" => "2023-01-28T22:11:45.000000Z",
                    "messages_count" => 2
                ]
            ],
            "information" => [
                "id" => "aa31925b-b366-3cd4-9609-dad2c435eda3",
                "user_id" => "6ed043a2-c567-333d-abe8-ccf4f86eb4a6",
                "name" => "Oleta Quitzon",
                "nickname" => "zharris",
                "linkedin_url" => "http://keeling.com/doloribus-porro-qui-enim",
                "github_url" => "http://www.kunde.info/quia-atque-velit-qui-quia-voluptatem-consequatur-tempore.html",
                "birthdate" => "2009-01-13",
                "about" => "Et vitae qui consequatur veniam. Facere unde fugiat ex sint commodi consectetur eum. Vel et ut in reiciendis perferendis ad.",
                "created_at" => "2023-01-28T22:11:45.000000Z",
                "updated_at" => "2023-01-28T22:11:45.000000Z"
            ],
            "address" => [
                "id" => "f7591730-0550-3249-9185-2a40bc7ce3e7",
                "user_id" => "6ed043a2-c567-333d-abe8-ccf4f86eb4a6",
                "country" => "IT",
                "state" => "RJ",
                "city" => "Stehrland",
                "zip_code" => "70219530",
                "created_at" => "2023-01-28T22:11:45.000000Z",
                "updated_at" => "2023-01-28T22:11:45.000000Z"
            ]
        ];
    }

    public function validProfileEntity(): ProfileEntity
    {
        return ProfileEntity::make($this->validProfilePayload());
    }
}
