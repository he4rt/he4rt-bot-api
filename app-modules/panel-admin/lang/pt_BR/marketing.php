<?php

declare(strict_types=1);

return [
    'navigation' => [
        'cluster' => 'Marketing',
        'cluster_breadcrumb' => 'Marketing',
        'back_to_admin' => 'Voltar pro Admin',
        'meeting_showcase' => 'Meeting Showcase',
        'discord_dashboard' => 'Discord Dashboard',
        'short_links' => 'Links curtos',
    ],

    'short_links' => [
        'label' => 'Link curto',
        'plural' => 'Links curtos',

        'sections' => [
            'link' => 'Link',
            'lifecycle' => 'Ciclo de vida',
            'utm' => 'Parâmetros UTM',
            'history' => 'Histórico de destinos',
            'about' => 'Sobre o link',
            'numbers' => 'Números',
            'filter' => 'Filtro',
            'destination_history' => 'Histórico de destino',
            'destination_history_hint' => 'O slug nunca muda — o destino sim.',
        ],

        'fields' => [
            'nickname' => 'Apelido',
            'slug' => 'Slug',
            'short_url' => 'URL curta',
            'destination_url' => 'Destino',
            'tags' => 'Tags',
            'active' => 'Ativo',
            'expires_at' => 'Expira em',
            'status' => 'Status',
            'clicks' => 'Cliques',
            'total_clicks' => 'Cliques totais',
            'created_at' => 'Criado em',
            'created_by' => 'Criado por',
            'valid_from' => 'Vigente desde',
            'valid_until' => 'Vigente até',
            'valid_since' => 'Vigente desde :date',
            'changed_by' => 'Alterado por',
            'utm_source' => 'utm_source',
            'utm_medium' => 'utm_medium',
            'utm_campaign' => 'utm_campaign',
            'utm_term' => 'utm_term',
            'utm_content' => 'utm_content',
        ],

        'helpers' => [
            'nickname' => 'A parte legível da URL. Um sufixo aleatório de 5 caracteres é anexado automaticamente — "discord" vira "discord-a3f9k".',
            'slug' => 'O slug é imutável e nunca é reusado: a URL que já foi impressa ou colada em algum lugar nunca muda de lugar.',
            'destination_url' => 'Para onde o link aponta agora. Trocar o destino mantém a URL curta e registra a mudança no histórico.',
            'tags' => 'Rótulos livres para agrupar e filtrar links (ex.: comunidade, hacktoberfest).',
            'expires_at' => 'Depois desta data o link para de redirecionar. Deixe em branco para nunca expirar.',
            'utm' => 'Anexados ao destino no momento do redirect, para que o analytics do site de destino também enxergue de onde veio o acesso.',
        ],

        'placeholders' => [
            'none' => '—',
            'current' => 'Vigente',
            'no_referer' => 'Acesso direto',
            'unknown' => 'Desconhecido',
        ],

        'filters' => [
            'status' => 'Status',
            'tag' => 'Tag',
        ],

        'actions' => [
            'edit_destination' => 'Editar destino',
            'disable' => [
                'label' => 'Desativar',
                'heading' => 'Desativar este link?',
                'body' => 'A URL curta para de redirecionar imediatamente. Nada é apagado — o histórico e os cliques continuam onde estão.',
            ],
            'enable' => [
                'label' => 'Ativar',
                'heading' => 'Reativar este link?',
                'body' => 'A URL curta volta a redirecionar para o destino vigente.',
            ],
            'copy_url' => [
                'label' => 'Copiar URL curta',
                'copied' => 'URL curta copiada!',
            ],
        ],

        'notifications' => [
            'disabled' => [
                'title' => 'Link desativado',
            ],
            'enabled' => [
                'title' => 'Link reativado',
            ],
            'created' => [
                'title' => 'Link curto criado',
                'body' => 'A URL curta é :url',
            ],
            'invalid_destination' => [
                'title' => 'Destino recusado',
            ],
        ],

        'stats' => [
            'clicks' => 'Cliques',
            'peak' => 'Pico em um dia',
            'top_source' => 'Maior origem',
            'humans_only' => 'só humanos',
            'including_bots' => 'humanos + bots',
            'never_expires' => 'Não expira',
            'no_clicks_yet' => 'Nenhum clique ainda',
            'share' => ':clicks cliques · :share',
        ],

        'table' => [
            'clicks_description' => ':total no total · :bots de bots',
        ],

        'widgets' => [
            'include_bots' => [
                'label' => 'Incluir bots',
                'helper' => 'Bots de preview inflam a contagem sem ninguém ter clicado.',
            ],

            'clicks_over_time' => [
                'heading' => 'Cliques por dia',
                'dataset' => 'Cliques',
                'empty' => 'Nenhum clique registrado neste período.',
                'ranges' => [
                    '7' => 'Últimos 7 dias',
                    '30' => 'Últimos 30 dias',
                    '90' => 'Últimos 90 dias',
                ],
            ],

            'top_referers' => [
                'heading' => 'De onde vieram',
                'origin' => 'Origem',
                'clicks' => 'Cliques',
                'share' => 'Participação',
                'dimension' => 'Dimensão',
                'empty_heading' => 'Nenhum clique ainda',
                'empty_description' => 'Assim que alguém abrir a URL curta, a origem do acesso aparece aqui.',
                'dimensions' => [
                    'referer' => 'Referer',
                    'utm_source' => 'UTM source',
                    'country_code' => 'País',
                ],
            ],

            'recent_clicks' => [
                'heading' => 'Cliques recentes',
                'human' => 'humano',
                'bot' => 'bot',
                'empty_heading' => 'Nenhum clique ainda',
                'empty_description' => 'Cada acesso à URL curta vira uma linha aqui.',
                'columns' => [
                    'clicked_at' => 'Quando',
                    'device' => 'Dispositivo / navegador / SO',
                    'origin' => 'Origem',
                ],
            ],
            'device_breakdown' => [
                'heading' => 'Dispositivos',
                'empty' => 'Nenhum clique registrado ainda.',
                'dimensions' => [
                    'device_type' => 'Dispositivo',
                    'browser' => 'Navegador',
                    'os' => 'Sistema',
                ],
            ],
        ],
    ],
];
