<?php

declare(strict_types=1);

return [
    'label' => 'Álbum',
    'plural' => 'Álbuns de fotos',

    'columns' => [
        'title' => 'Título',
        'slug' => 'Slug',
        'event' => 'Evento',
        'location' => 'Local',
        'happened_at' => 'Aconteceu em',
        'description' => 'Descrição',
        'published_at' => 'Publicado em',
        'photos' => 'Fotos',
        'photos_count' => 'Fotos',
        'created_at' => 'Criado em',
    ],

    'form' => [
        'helpers' => [
            'event' => 'Opcional. Escolher um evento preenche local e data, se estiverem vazios.',
            'published_at' => 'Vazio mantém o álbum como rascunho, fora do portal.',
            'photos' => 'Até :max_files fotos por vez, :max_mb MB cada. JPG, PNG ou WEBP.',
        ],
    ],

    'filters' => [
        'published' => 'Publicados',
        'drafts' => 'Rascunhos',
    ],

    'photos' => [
        'title' => 'Fotos',
        'empty' => 'Envie fotos pelo campo "Fotos" do formulário acima.',
        'columns' => [
            'preview' => 'Prévia',
            'highlight' => 'Destaque',
            'caption' => 'Legenda',
            'size' => 'Tamanho',
            'uploaded_at' => 'Enviada em',
        ],
    ],
];
