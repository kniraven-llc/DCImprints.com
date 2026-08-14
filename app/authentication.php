<?php

declare(strict_types=1);

/**
 * Maximum number of consecutive failed login attempts before a temporary
 * lockout is applied.
 */
const DC_ADMIN_MAX_LOGIN_ATTEMPTS = 5;

/**
 * Length of an administrator login lockout.
 */
const DC_ADMIN_LOCKOUT_MINUTES = 15;

/**
 * A valid password hash used when no matching administrator exists.
 *
 * Running password_verify() against a real hash helps prevent obvious timing
 * differences between an invalid username and an invalid password.
 */
const DC_ADMIN_DUMMY_PASSWORD_HASH =
    '$2y$12$k8zUJPnTC/hrhNCaaufjX.g4gyw4j/C0/tiwAstHv4zBqHDv2/l6a';

/**
 * Remove administrator authentication information from the session.
 */
function clear_admin_session(
    bool $regenerateSessionId = true
): void {
    unset(
        $_SESSION['admin_authenticated'],
        $_SESSION['admin_user_id'],
        $_SESSION['admin_username'],
        $_SESSION['admin_display_name'],
        $_SESSION['admin_must_change_password']
    );

    if (
        $regenerateSessionId
        && session_status() === PHP_SESSION_ACTIVE
    ) {
        session_regenerate_id(true);
    }
}

/**
 * Retrieve the currently authenticated administrator.
 *
 * The database is treated as the source of truth. If the account was
 * disabled or deleted after login, the existing session is rejected.
 *
 * @return array<string, mixed>|null
 */
function current_admin_user(
    bool $refresh = false
): ?array {
    static $cachedUser = null;
    static $cacheInitialized = false;

    if (
        !$refresh
        && $cacheInitialized
    ) {
        return $cachedUser;
    }

    $cacheInitialized = true;
    $cachedUser = null;

    if (
        ($_SESSION['admin_authenticated'] ?? false)
        !== true
    ) {
        return null;
    }

    $adminUserId = (int) (
        $_SESSION['admin_user_id']
        ?? 0
    );

    if ($adminUserId < 1) {
        clear_admin_session(false);
        return null;
    }

    $pdo = database();

    if ($pdo === null) {
        /*
         * Administrator access fails closed when the database is
         * unavailable. Public content still has its normal fallbacks.
         */
        clear_admin_session(false);
        return null;
    }

    try {
        $statement = $pdo->prepare(
            'SELECT
                id,
                username,
                display_name,
                email_address,
                is_active,
                must_change_password,
                last_login_at,
                password_changed_at,
                created_at,
                updated_at
             FROM admin_users
             WHERE id = :id
               AND is_active = 1
             LIMIT 1'
        );

        $statement->execute([
            'id' => $adminUserId,
        ]);

        $user = $statement->fetch();

        if (!is_array($user)) {
            clear_admin_session(false);
            return null;
        }

        $user['id'] =
            (int) $user['id'];

        $user['is_active'] =
            (int) $user['is_active'];

        $user['must_change_password'] =
            (int) $user[
                'must_change_password'
            ];

        $_SESSION['admin_authenticated'] =
            true;

        $_SESSION['admin_user_id'] =
            $user['id'];

        $_SESSION['admin_username'] =
            (string) $user['username'];

        $_SESSION['admin_display_name'] =
            (string) $user['display_name'];

        $_SESSION[
            'admin_must_change_password'
        ] =
            $user['must_change_password']
            === 1;

        $cachedUser = $user;

        return $cachedUser;
    } catch (Throwable $exception) {
        log_message(
            'Unable to validate administrator session: '
            . $exception->getMessage()
        );

        clear_admin_session(false);

        return null;
    }
}

function admin_authenticated(): bool
{
    return current_admin_user() !== null;
}

function require_admin(): void
{
    if (!admin_authenticated()) {
        flash(
            'error',
            'Please sign in to continue.'
        );

        redirect('/admin/login/');
    }
}

/**
 * Record an unsuccessful login attempt for a real administrator account.
 *
 * The account is temporarily locked after the configured number of
 * consecutive unsuccessful attempts.
 */
function record_failed_admin_login(
    int $adminUserId,
    int $currentFailureCount
): void {
    if ($adminUserId < 1) {
        return;
    }

    $pdo = database();

    if ($pdo === null) {
        return;
    }

    $newFailureCount =
        $currentFailureCount + 1;

    try {
        if (
            $newFailureCount
            >= DC_ADMIN_MAX_LOGIN_ATTEMPTS
        ) {
            $statement = $pdo->prepare(
                'UPDATE admin_users
                 SET
                    failed_login_count = 0,
                    locked_until = DATE_ADD(
                        NOW(),
                        INTERVAL '
                . DC_ADMIN_LOCKOUT_MINUTES
                . ' MINUTE
                    )
                 WHERE id = :id'
            );

            $statement->execute([
                'id' => $adminUserId,
            ]);

            return;
        }

        $statement = $pdo->prepare(
            'UPDATE admin_users
             SET failed_login_count =
                    :failed_login_count
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $adminUserId,
            'failed_login_count' =>
                $newFailureCount,
        ]);
    } catch (Throwable $exception) {
        log_message(
            'Unable to record failed administrator login: '
            . $exception->getMessage()
        );
    }
}

/**
 * Authenticate an administrator using the admin_users database table.
 */
function attempt_admin_login(
    string $username,
    string $password
): bool {
    $username = trim($username);

    if (
        $username === ''
        || $password === ''
    ) {
        password_verify(
            $password,
            DC_ADMIN_DUMMY_PASSWORD_HASH
        );

        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        password_verify(
            $password,
            DC_ADMIN_DUMMY_PASSWORD_HASH
        );

        log_message(
            'Administrator login failed because '
            . 'the database is unavailable.'
        );

        return false;
    }

    try {
        $statement = $pdo->prepare(
            'SELECT
                id,
                username,
                display_name,
                email_address,
                password_hash,
                is_active,
                must_change_password,
                failed_login_count,
                locked_until,
                CASE
                    WHEN locked_until IS NOT NULL
                     AND locked_until > NOW()
                    THEN 1
                    ELSE 0
                END AS is_locked
             FROM admin_users
             WHERE username = :username
             LIMIT 1'
        );

        $statement->execute([
            'username' => $username,
        ]);

        $user = $statement->fetch();

        if (!is_array($user)) {
            password_verify(
                $password,
                DC_ADMIN_DUMMY_PASSWORD_HASH
            );

            return false;
        }

        $adminUserId =
            (int) $user['id'];

        $isActive =
            (int) $user['is_active']
            === 1;

        $isLocked =
            (int) $user['is_locked']
            === 1;

        $passwordHash =
            (string) $user['password_hash'];

        /*
         * Password verification still runs for disabled and locked
         * accounts so their status is not exposed through an obvious
         * timing difference.
         */
        $passwordValid =
            password_verify(
                $password,
                $passwordHash
            );

        if (
            !$isActive
            || $isLocked
            || !$passwordValid
        ) {
            if (
                $isActive
                && !$isLocked
            ) {
                record_failed_admin_login(
                    $adminUserId,
                    (int) $user[
                        'failed_login_count'
                    ]
                );
            }

            return false;
        }

        $newPasswordHash = null;

        if (
            password_needs_rehash(
                $passwordHash,
                PASSWORD_DEFAULT
            )
        ) {
            $newPasswordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );
        }

        $pdo->beginTransaction();

        if (
            is_string($newPasswordHash)
            && $newPasswordHash !== ''
        ) {
            $updateStatement = $pdo->prepare(
                'UPDATE admin_users
                 SET
                    password_hash =
                        :password_hash,
                    failed_login_count = 0,
                    locked_until = NULL,
                    last_login_at = NOW()
                 WHERE id = :id'
            );

            $updateStatement->execute([
                'id' => $adminUserId,
                'password_hash' =>
                    $newPasswordHash,
            ]);
        } else {
            $updateStatement = $pdo->prepare(
                'UPDATE admin_users
                 SET
                    failed_login_count = 0,
                    locked_until = NULL,
                    last_login_at = NOW()
                 WHERE id = :id'
            );

            $updateStatement->execute([
                'id' => $adminUserId,
            ]);
        }

        $pdo->commit();

        session_regenerate_id(true);

        $_SESSION['admin_authenticated'] =
            true;

        $_SESSION['admin_user_id'] =
            $adminUserId;

        $_SESSION['admin_username'] =
            (string) $user['username'];

        $_SESSION['admin_display_name'] =
            (string) $user['display_name'];

        $_SESSION[
            'admin_must_change_password'
        ] =
            (int) $user[
                'must_change_password'
            ] === 1;

        current_admin_user(true);

        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        log_message(
            'Administrator login failed: '
            . $exception->getMessage()
        );

        return false;
    }
}

function admin_must_change_password(): bool
{
    $user = current_admin_user();

    return $user !== null
        && (int) $user[
            'must_change_password'
        ] === 1;
}

function admin_display_name(): string
{
    $user = current_admin_user();

    if ($user === null) {
        return 'Administrator';
    }

    $displayName = trim(
        (string) $user['display_name']
    );

    return $displayName !== ''
        ? $displayName
        : (string) $user['username'];
}

/**
 * Determine whether a proposed administrator password satisfies the site's
 * minimum requirements.
 */
function valid_admin_password(
    string $password
): bool {
    if (
        mb_strlen($password) < 12
        || mb_strlen($password) > 200
    ) {
        return false;
    }

    return preg_match('/[a-z]/', $password) === 1
        && preg_match('/[A-Z]/', $password) === 1
        && preg_match('/\d/', $password) === 1;
}

/**
 * Change the password for the currently authenticated administrator.
 *
 * This will be used by the future Account section of the admin interface.
 */
function change_current_admin_password(
    string $currentPassword,
    string $newPassword
): bool {
    $user = current_admin_user();

    if (
        $user === null
        || !valid_admin_password(
            $newPassword
        )
    ) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'SELECT password_hash
             FROM admin_users
             WHERE id = :id
               AND is_active = 1
             LIMIT 1'
        );

        $statement->execute([
            'id' => (int) $user['id'],
        ]);

        $currentHash =
            $statement->fetchColumn();

        if (
            !is_string($currentHash)
            || !password_verify(
                $currentPassword,
                $currentHash
            )
        ) {
            return false;
        }

        $newHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        if (!is_string($newHash)) {
            return false;
        }

        $updateStatement = $pdo->prepare(
            'UPDATE admin_users
             SET
                password_hash =
                    :password_hash,
                must_change_password = 0,
                password_changed_at = NOW(),
                failed_login_count = 0,
                locked_until = NULL
             WHERE id = :id'
        );

        $updateStatement->execute([
            'id' => (int) $user['id'],
            'password_hash' => $newHash,
        ]);

        $_SESSION[
            'admin_must_change_password'
        ] = false;

        current_admin_user(true);

        return true;
    } catch (Throwable $exception) {
        log_message(
            'Unable to change administrator password: '
            . $exception->getMessage()
        );

        return false;
    }
}

/**
 * Update the basic administrator account details.
 *
 * Empty email addresses are stored as NULL. Usernames remain unique through
 * the database constraint.
 */
function update_current_admin_account(
    string $username,
    string $displayName,
    ?string $emailAddress
): bool {
    $user = current_admin_user();

    if ($user === null) {
        return false;
    }

    $username = trim($username);
    $displayName = trim($displayName);
    $emailAddress = trim(
        (string) $emailAddress
    );

    if (
        $username === ''
        || mb_strlen($username) > 100
        || !preg_match(
            '/^[A-Za-z0-9._-]+$/',
            $username
        )
        || $displayName === ''
        || mb_strlen($displayName) > 190
    ) {
        return false;
    }

    if (
        $emailAddress !== ''
        && (
            mb_strlen($emailAddress) > 254
            || !filter_var(
                $emailAddress,
                FILTER_VALIDATE_EMAIL
            )
        )
    ) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE admin_users
             SET
                username = :username,
                display_name = :display_name,
                email_address = :email_address
             WHERE id = :id
               AND is_active = 1'
        );

        $statement->bindValue(
            ':id',
            (int) $user['id'],
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':username',
            $username,
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':display_name',
            $displayName,
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':email_address',
            $emailAddress !== ''
                ? $emailAddress
                : null,
            $emailAddress !== ''
                ? PDO::PARAM_STR
                : PDO::PARAM_NULL
        );

        $statement->execute();

        $_SESSION['admin_username'] =
            $username;

        $_SESSION['admin_display_name'] =
            $displayName;

        current_admin_user(true);

        return true;
    } catch (PDOException $exception) {
        /*
         * A duplicate username or email is rejected by the database's
         * unique indexes. The admin page will display a general message.
         */
        log_message(
            'Unable to update administrator account: '
            . $exception->getMessage()
        );

        return false;
    }
}

function logout_admin(): void
{
    clear_admin_session(true);
}