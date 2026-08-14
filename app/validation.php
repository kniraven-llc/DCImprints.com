<?php

declare(strict_types=1);

function validate_contact_form(array $input): array
{
    $errors = [];

    $name = trim((string) ($input['name'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));
    $organization = trim((string) ($input['organization'] ?? ''));
    $message = trim((string) ($input['message'] ?? ''));

    if ($name === '' || mb_strlen($name) > 100) {
        $errors['name'] = 'Enter your name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (mb_strlen($phone) > 40) {
        $errors['phone'] = 'Enter a valid phone number.';
    }

    if (mb_strlen($organization) > 150) {
        $errors['organization'] = 'The organization name is too long.';
    }

    if ($message === '' || mb_strlen($message) > 5000) {
        $errors['message'] = 'Describe your project in 5,000 characters or fewer.';
    }

    return [$errors, compact('name', 'email', 'phone', 'organization', 'message')];
}
