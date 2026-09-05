<?php

declare(strict_types=1);

return [
    'sections' => [
        'personal' => 'Personal',
        'professional' => 'Professional',
        'about' => 'About',
        'address' => 'Location',
        'skills' => 'Skills',
        'social_links' => 'Social Links',
        'availability' => 'Availability',
        'preferences' => 'Preferences',
        'connections' => 'Connections',
        'work_experiences' => 'Work experience',
    ],

    'fields' => [
        'nickname' => 'Nickname',
        'birthdate' => 'Birthdate',
        'headline' => 'Headline',
        'seniority_level' => 'Seniority Level',
        'years_experience' => 'Years of Experience',
        'about' => 'About',
        'skill' => 'Skill',
        'proficiency' => 'Level',
        'skill_years_experience' => 'Years',
        'platform' => 'Platform',
        'handle' => 'Handle / URL',
        'country' => 'Country',
        'state' => 'State',
        'city' => 'City',
        'avatar' => 'Photo',
        'cover' => 'Cover',
        'available_for_proposals' => 'Available for proposals',
        'start_availability' => 'Start availability',
        'company_name' => 'Company',
        'position' => 'Position',
        'experience_description' => 'Description',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'is_currently_working_here' => 'I currently work here',
        'expected_salary_min' => 'Expected salary (min)',
        'expected_salary_max' => 'Expected salary (max)',
        'is_open_to_remote' => 'Open to remote work',
        'willing_to_relocate' => 'Willing to relocate',
        'has_disability' => 'Person with a disability',
        'employment_types' => 'Employment type',
    ],

    'placeholders' => [
        'nickname' => 'How do you like to be called?',
        'headline' => 'Your job title or role',
        'about' => 'Tell us about yourself...',
        'handle' => '@username or https://...',
        'city_search' => 'Search city...',
    ],

    'hints' => [
        'adjust_framing' => 'Drag to choose which slice of the image stays visible.',
        'image_upload' => ':formats up to :max_mb MB. Recommended :width × :height px, at least :min_width × :min_height px after cropping. GIFs keep their animation, but cropping drops it.',
        'image_upload_with_gif_limit' => ':formats up to :max_mb MB, GIF up to :gif_mb MB. Recommended :width × :height px, at least :min_width × :min_height px after cropping. GIFs keep their animation, but cropping drops it.',
        'headline' => 'e.g. Frontend Developer, Product Designer',
        'available_for_proposals' => 'When active, recruiters will see a green badge on your profile',
        'city' => 'If your city is not listed, search for it.',
        'has_disability' => 'Sensitive information — used only for affirmative-action roles.',
        'expected_salary' => 'Monthly amount in BRL. Private, used only in proposals.',
        'skills' => 'Pick your skills and set your level and years of experience for each.',
    ],

    'validation' => [
        'image_dimensions' => 'After cropping, the image must be at least :min_width × :min_height px. The recommended size is :width × :height px.',
        'image_mimetypes' => 'Unsupported format. Upload a :formats image.',
        'image_unconverted_max_size' => 'A GIF can be at most :gif_mb MB. It is served exactly as it arrives, with no compression, so the file weighs on every profile visit.',
    ],

    'actions' => [
        'save' => 'Save profile',
        'add_social_link' => 'Add social link',
        'add_work_experience' => 'Add experience',
        'add_skill' => 'Add skill',
        'change_avatar' => 'Change photo',
        'change_cover' => 'Change cover',
        'adjust_avatar' => 'Adjust photo framing',
        'adjust_cover' => 'Adjust framing',
        'save_framing' => 'Save framing',
        'save_avatar' => 'Save photo',
        'save_cover' => 'Save cover',
    ],

    'notifications' => [
        'saved' => 'Profile saved successfully!',
        'avatar_updated' => 'Photo updated successfully!',
        'cover_updated' => 'Cover updated successfully!',
        'framing_updated' => 'Framing saved!',
        'no_profile' => 'Profile not found for this tenant.',
    ],

    'page' => [
        'subtitle' => 'Fill in the fields and watch your card being built in real time',
    ],

    'preview' => [
        'available' => 'Available',
        'years_experience' => ':count year of exp.|:count years of exp.',
        'skills' => 'Skills',
        'experience' => 'Experience',
        'can_start' => 'Can start:',
        'footer' => 'This card appears in the member listing and on your public profile.',
    ],
];
