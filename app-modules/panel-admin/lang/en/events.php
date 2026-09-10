<?php

declare(strict_types=1);

return [
    'label' => 'Event',
    'plural' => 'Events',

    'columns' => [
        'title' => 'Title',
        'slug' => 'Slug',
        'type' => 'Type',
        'location' => 'Location',
        'description' => 'Description',
        'cover' => 'Cover',
        'starts_at' => 'Starts At',
        'ends_at' => 'Ends At',
        'status' => 'Status',
        'created_at' => 'Created At',
        'date' => 'Date',
        'code' => 'Code',
        'event_date' => 'Event Date',
        'valid_from' => 'Valid From',
        'expires_at' => 'Expires At',
        'uses' => 'Uses',
        'revoked_at' => 'Revoked At',
    ],

    'sections' => [
        'enrollment_policy' => 'Enrollment Policy',
    ],

    'form' => [
        'enrollment_method' => 'Enrollment Method',
        'check_in_method' => 'Check-in Method',
        'capacity' => 'Capacity',
        'waitlist_enabled' => 'Waitlist Enabled',
        'attendance_requirement' => 'Attendance Requirement',
        'minimum_days' => 'Minimum Days',
        'cancellation_deadline_hours' => 'Cancellation Deadline (hours before event)',
        'xp_on_confirmed' => 'XP on Confirmed',
        'xp_on_checked_in' => 'XP on Checked-in',
        'xp_on_attended' => 'XP on Attended',
        'application_form_schema' => 'Application Form Schema',
        'application_schema_key' => 'Field name',
        'application_schema_value' => 'Field type / label',
        'helpers' => [
            'minimum_days' => 'Required when attendance requirement is "Minimum Days". Default 1, max = event days.',
        ],
    ],

    'relations' => [
        'enrollments' => 'Enrollments',
        'check_in_codes' => 'Check-in Codes',
    ],

    'enrollments' => [
        'columns' => [
            'participant' => 'Participant',
            'waitlist' => 'Waitlist',
            'enrolled_at' => 'Enrolled At',
            'confirmed_at' => 'Confirmed At',
            'check_in_history' => 'Check-in History',
            'cancelled_at' => 'Cancelled At',
        ],
        'actions' => [
            'check_in' => 'Check In',
            'check_in_selected' => 'Check In Selected',
            'override_status' => 'Override Status',
            'new_status' => 'New Status',
            'reason' => 'Reason',
        ],
        'notifications' => [
            'participant_checked_in' => 'Participant checked in.',
            'selected_participants_checked_in' => 'Selected participants checked in.',
            'status_overridden' => 'Enrollment status overridden.',
        ],
    ],

    'check_in_codes' => [
        'actions' => [
            'generate_code' => 'Generate Code',
            'revoke' => 'Revoke',
        ],
        'fields' => [
            'code_length' => 'Code Length',
            'generated_code' => 'Generated Code',
            'max_uses' => 'Max Uses (optional)',
        ],
        'digits' => [
            'four' => '4 digits',
            'six' => '6 digits',
        ],
        'unlimited' => 'Unlimited',
        'notifications' => [
            'code_revoked' => 'Code revoked.',
        ],
    ],

    'edit' => [
        'scan_qr' => 'Scan QR',
        'qr_token' => 'QR Token',
        'qr_token_placeholder' => 'Scan or paste the participant token',
        'check_in_submit' => 'Check In',
        'participant_fallback' => 'Participant',
        'notifications' => [
            'check_in_success_title' => 'Check-in successful',
            'check_in_success_body' => ':name has been checked in.',
            'check_in_failed_title' => 'Check-in failed',
            'check_in_unexpected_error' => 'An unexpected error occurred. Please try again.',
        ],
    ],
];
