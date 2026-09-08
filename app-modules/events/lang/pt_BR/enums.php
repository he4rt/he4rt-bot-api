<?php

declare(strict_types=1);

return [
    'event_status' => [
        'draft' => 'Rascunho',
        'published' => 'Publicado',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
    ],

    'event_type' => [
        'meetup' => 'Meetup',
        'workshop' => 'Workshop',
        'conference' => 'Conferência',
    ],

    'enrollment_method' => [
        'rsvp' => 'RSVP',
        'rsvp_checkin' => 'RSVP + Check-in',
        'application' => 'Inscrição',
    ],

    'attendance_requirement' => [
        'all_days' => 'Todos os dias',
        'any_day' => 'Qualquer dia',
        'minimum_days' => 'Dias mínimos',
    ],

    'enrollment_status' => [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmado',
        'waitlisted' => 'Lista de espera',
        'checked_in' => 'Check-in realizado',
        'attended' => 'Presente',
        'cancelled' => 'Cancelado',
        'rejected' => 'Rejeitado',
        'no_show' => 'Não compareceu',
    ],

    'check_in_method' => [
        'manual' => 'Manual',
        'numeric_code' => 'Código numérico',
        'qr_code' => 'QR Code',
    ],

    'triggered_by' => [
        'user' => 'Usuário',
        'admin' => 'Administrador',
        'system' => 'Sistema',
    ],

    'photo_conversion' => [
        'thumb' => 'Miniatura',
        'large' => 'Ampliada',
        'thumb_description' => 'Recorte 640x480 para cards e grades.',
        'large_description' => 'Até 1920px no lado maior, para o lightbox.',
    ],
];
