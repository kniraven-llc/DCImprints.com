<?php

declare(strict_types=1);

/*
 * Administrator Account partial.
 *
 * Enforced limits:
 * - Display name: 100 characters
 * - Username: 60 characters
 * - Email address: 254 characters
 * - Password fields: 200 characters
 * - New passwords: minimum 12 characters
 */

const DC_ADMIN_DISPLAY_NAME_MAX = 100;
const DC_ADMIN_USERNAME_MAX = 60;
const DC_ADMIN_EMAIL_MAX = 254;
const DC_ADMIN_PASSWORD_MAX = 200;
const DC_ADMIN_PASSWORD_MIN = 12;

if (
    ($adminPartialMode ?? '')
    === 'process'
) {
    $action = trim(
        (string) (
            $_POST['action']
            ?? ''
        )
    );

    /*
     * Update administrator profile information.
     */
    if ($action === 'update_account') {
        $username = trim(
            (string) (
                $_POST['username']
                ?? ''
            )
        );

        $displayName = trim(
            (string) (
                $_POST['display_name']
                ?? ''
            )
        );

        $emailAddress = trim(
            (string) (
                $_POST['email_address']
                ?? ''
            )
        );

        if ($displayName === '') {
            flash(
                'error',
                'Display name is required.'
            );

            dc_admin_redirect(
                'account',
                'administrator-details'
            );
        }

        if (
            mb_strlen($displayName)
            > DC_ADMIN_DISPLAY_NAME_MAX
        ) {
            flash(
                'error',
                'Display name must be 100 characters or fewer.'
            );

            dc_admin_redirect(
                'account',
                'administrator-details'
            );
        }

        if ($username === '') {
            flash(
                'error',
                'Username is required.'
            );

            dc_admin_redirect(
                'account',
                'administrator-details'
            );
        }

        if (
            mb_strlen($username)
            > DC_ADMIN_USERNAME_MAX
        ) {
            flash(
                'error',
                'Username must be 60 characters or fewer.'
            );

            dc_admin_redirect(
                'account',
                'administrator-details'
            );
        }

        if (
            preg_match(
                '/^[A-Za-z0-9._-]+$/',
                $username
            ) !== 1
        ) {
            flash(
                'error',
                'Username may contain only letters, numbers, periods, underscores, and hyphens.'
            );

            dc_admin_redirect(
                'account',
                'administrator-details'
            );
        }

        if (
            mb_strlen($emailAddress)
            > DC_ADMIN_EMAIL_MAX
        ) {
            flash(
                'error',
                'Email address must be 254 characters or fewer.'
            );

            dc_admin_redirect(
                'account',
                'administrator-details'
            );
        }

        if (
            $emailAddress !== ''
            && filter_var(
                $emailAddress,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            flash(
                'error',
                'Enter a valid email address or leave the field empty.'
            );

            dc_admin_redirect(
                'account',
                'administrator-details'
            );
        }

        dc_admin_finish(
            update_current_admin_account(
                $username,
                $displayName,
                $emailAddress
            ),
            'Administrator account details updated.',
            'The account details could not be updated. Check for an invalid or duplicate username or email address.',
            'account',
            'administrator-details'
        );
    }

    /*
     * Change the administrator password.
     */
    if ($action === 'change_password') {
        $currentPassword =
            (string) (
                $_POST['current_password']
                ?? ''
            );

        $newPassword =
            (string) (
                $_POST['new_password']
                ?? ''
            );

        $confirmation =
            (string) (
                $_POST['confirm_password']
                ?? ''
            );

        if ($currentPassword === '') {
            flash(
                'error',
                'Current password is required.'
            );

            dc_admin_redirect(
                'account',
                'change-password'
            );
        }

        if (
            mb_strlen($currentPassword)
            > DC_ADMIN_PASSWORD_MAX
            || mb_strlen($newPassword)
            > DC_ADMIN_PASSWORD_MAX
            || mb_strlen($confirmation)
            > DC_ADMIN_PASSWORD_MAX
        ) {
            flash(
                'error',
                'Password fields must be 200 characters or fewer.'
            );

            dc_admin_redirect(
                'account',
                'change-password'
            );
        }

        if ($newPassword !== $confirmation) {
            flash(
                'error',
                'The new password and confirmation do not match.'
            );

            dc_admin_redirect(
                'account',
                'change-password'
            );
        }

        if (!valid_admin_password($newPassword)) {
            flash(
                'error',
                'Use 12–200 characters with at least one uppercase letter, one lowercase letter, and one number.'
            );

            dc_admin_redirect(
                'account',
                'change-password'
            );
        }

        dc_admin_finish(
            change_current_admin_password(
                $currentPassword,
                $newPassword
            ),
            'Administrator password changed.',
            'The password could not be changed. Check the current password.',
            'account',
            'change-password'
        );
    }

    return;
}

?>
<div class="row g-4">
    <div class="col-xl-6">
        <section
            class="card border-0 shadow-sm h-100"
            id="administrator-details"
        >
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-1">
                    Administrator Details
                </h2>

                <p class="small text-body-secondary mb-0">
                    Update the name, username, and recovery email associated
                    with this administrator account.
                </p>
            </div>

            <div class="card-body p-4">
                <form method="post">
                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="action"
                        value="update_account"
                    >

                    <div class="mb-3">
                        <label
                            class="form-label"
                            for="admin_display_name"
                        >
                            Display name
                        </label>

                        <input
                            class="form-control"
                            id="admin_display_name"
                            name="display_name"
                            maxlength="<?= DC_ADMIN_DISPLAY_NAME_MAX ?>"
                            data-character-count
                            value="<?= e(
                                (string) (
                                    $currentAdmin[
                                        'display_name'
                                    ]
                                    ?? ''
                                )
                            ) ?>"
                            required
                            autocomplete="name"
                        >
                    </div>

                    <div class="mb-3">
                        <label
                            class="form-label"
                            for="admin_username"
                        >
                            Username
                        </label>

                        <input
                            class="form-control"
                            id="admin_username"
                            name="username"
                            maxlength="<?= DC_ADMIN_USERNAME_MAX ?>"
                            data-character-count
                            pattern="[A-Za-z0-9._-]+"
                            value="<?= e(
                                (string) (
                                    $currentAdmin[
                                        'username'
                                    ]
                                    ?? ''
                                )
                            ) ?>"
                            required
                            autocomplete="username"
                        >

                        <div class="form-text">
                            Letters, numbers, periods, underscores, and hyphens
                            only.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label
                            class="form-label"
                            for="admin_email"
                        >
                            Email address
                        </label>

                        <input
                            class="form-control"
                            id="admin_email"
                            name="email_address"
                            type="email"
                            maxlength="<?= DC_ADMIN_EMAIL_MAX ?>"
                            data-character-count
                            value="<?= e(
                                (string) (
                                    $currentAdmin[
                                        'email_address'
                                    ]
                                    ?? ''
                                )
                            ) ?>"
                            autocomplete="email"
                        >

                        <div class="form-text">
                            Optional. Used as the administrator contact address.
                        </div>
                    </div>

                    <button
                        class="btn btn-primary"
                        type="submit"
                    >
                        Save Account Details
                    </button>
                </form>
            </div>
        </section>
    </div>

    <div
        class="col-xl-6"
        id="change-password"
    >
        <section
            class="card border-0 shadow-sm h-100"
        >
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-1">
                    Change Password
                </h2>

                <p class="small text-body-secondary mb-0">
                    Replace the password used to access this administration
                    area.
                </p>
            </div>

            <div class="card-body p-4">
                <form method="post">
                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="action"
                        value="change_password"
                    >

                    <div class="mb-3">
                        <label
                            class="form-label"
                            for="current_password"
                        >
                            Current password
                        </label>

                        <input
                            class="form-control"
                            id="current_password"
                            name="current_password"
                            type="password"
                            maxlength="<?= DC_ADMIN_PASSWORD_MAX ?>"
                            data-character-count
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="mb-3">
                        <label
                            class="form-label"
                            for="new_password"
                        >
                            New password
                        </label>

                        <input
                            class="form-control"
                            id="new_password"
                            name="new_password"
                            type="password"
                            minlength="<?= DC_ADMIN_PASSWORD_MIN ?>"
                            maxlength="<?= DC_ADMIN_PASSWORD_MAX ?>"
                            data-character-count
                            required
                            autocomplete="new-password"
                        >

                        <div class="form-text">
                            Use 12–200 characters with at least one uppercase
                            letter, one lowercase letter, and one number.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label
                            class="form-label"
                            for="confirm_password"
                        >
                            Confirm new password
                        </label>

                        <input
                            class="form-control"
                            id="confirm_password"
                            name="confirm_password"
                            type="password"
                            minlength="<?= DC_ADMIN_PASSWORD_MIN ?>"
                            maxlength="<?= DC_ADMIN_PASSWORD_MAX ?>"
                            data-character-count
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <button
                        class="btn btn-primary"
                        type="submit"
                    >
                        Change Password
                    </button>
                </form>
            </div>
        </section>
    </div>
</div>