<?php

declare(strict_types=1);

return [
    'label' => 'Evento',
    'plural' => 'Eventos',

    'columns' => [
        'title' => 'Título',
        'slug' => 'Slug',
        'type' => 'Tipo',
        'location' => 'Local',
        'description' => 'Descrição',
        'cover' => 'Capa',
        'starts_at' => 'Início',
        'ends_at' => 'Término',
        'status' => 'Status',
        'created_at' => 'Criado em',
        'date' => 'Data',
        'code' => 'Código',
        'event_date' => 'Data do evento',
        'valid_from' => 'Válido de',
        'expires_at' => 'Expira em',
        'uses' => 'Usos',
        'revoked_at' => 'Revogado em',
    ],

    'sections' => [
        'enrollment_policy' => 'Política de inscrição',
    ],

    'form' => [
        'enrollment_method' => 'Método de inscrição',
        'check_in_method' => 'Método de check-in',
        'capacity' => 'Capacidade',
        'waitlist_enabled' => 'Lista de espera habilitada',
        'attendance_requirement' => 'Requisito de presença',
        'minimum_days' => 'Dias mínimos',
        'cancellation_deadline_hours' => 'Prazo de cancelamento (horas antes do evento)',
        'xp_on_confirmed' => 'XP ao confirmar',
        'xp_on_checked_in' => 'XP no check-in',
        'xp_on_attended' => 'XP ao comparecer',
        'application_form_schema' => 'Schema do formulário de inscrição',
        'application_schema_key' => 'Nome do campo',
        'application_schema_value' => 'Tipo / rótulo do campo',
        'helpers' => [
            'minimum_days' => 'Obrigatório quando o requisito de presença é "Dias mínimos". Padrão 1, máximo = dias do evento.',
        ],
    ],

    'relations' => [
        'enrollments' => 'Inscrições',
        'check_in_codes' => 'Códigos de check-in',
    ],

    'enrollments' => [
        'columns' => [
            'participant' => 'Participante',
            'waitlist' => 'Lista de espera',
            'enrolled_at' => 'Inscrito em',
            'confirmed_at' => 'Confirmado em',
            'check_in_history' => 'Histórico de check-in',
            'cancelled_at' => 'Cancelado em',
        ],
        'actions' => [
            'check_in' => 'Fazer check-in',
            'check_in_selected' => 'Check-in selecionados',
            'override_status' => 'Alterar status',
            'new_status' => 'Novo status',
            'reason' => 'Motivo',
        ],
        'notifications' => [
            'participant_checked_in' => 'Participante com check-in realizado.',
            'selected_participants_checked_in' => 'Participantes selecionados com check-in realizado.',
            'status_overridden' => 'Status da inscrição alterado.',
        ],
    ],

    'check_in_codes' => [
        'actions' => [
            'generate_code' => 'Gerar código',
            'revoke' => 'Revogar',
        ],
        'fields' => [
            'code_length' => 'Tamanho do código',
            'generated_code' => 'Código gerado',
            'max_uses' => 'Máximo de usos (opcional)',
        ],
        'digits' => [
            'four' => '4 dígitos',
            'six' => '6 dígitos',
        ],
        'unlimited' => 'Ilimitado',
        'notifications' => [
            'code_revoked' => 'Código revogado.',
        ],
    ],

    'edit' => [
        'scan_qr' => 'Escanear QR',
        'qr_token' => 'Token QR',
        'qr_token_placeholder' => 'Escaneie ou cole o token do participante',
        'check_in_submit' => 'Fazer check-in',
        'participant_fallback' => 'Participante',
        'notifications' => [
            'check_in_success_title' => 'Check-in realizado',
            'check_in_success_body' => ':name fez check-in com sucesso.',
            'check_in_failed_title' => 'Falha no check-in',
            'check_in_unexpected_error' => 'Ocorreu um erro inesperado. Tente novamente.',
        ],
    ],
];
