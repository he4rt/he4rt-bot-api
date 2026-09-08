<?php

declare(strict_types=1);

return [
    'short_link_status' => [
        'active' => [
            'label' => 'Ativo',
            'description' => 'Redireciona normalmente e cada acesso vira um clique registrado',
        ],
        'expired' => [
            'label' => 'Expirado',
            'description' => 'Passou de `expires_at` — devolve a página de link indisponível',
        ],
        'disabled' => [
            'label' => 'Desativado',
            'description' => 'Desligado manualmente ou removido — o slug segue reservado para sempre',
        ],
    ],
];
