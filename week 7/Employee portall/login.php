<?php
declare(strict_types=1);

/**
 * Employee Portal Login
 *
 * Authenticates employees by email address or employee ID using PDO prepared
 * statements and PHP password hashing APIs.
 */

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

/** @var PDO $pdo */
$pdo = require __DIR__ . '/db.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function passwordColumnExists(PDO $pdo): bool
{
    $statement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name"
    );
    $statement->execute([
        'table_name' => 'employees',
        'column_name' => 'password_hash',
    ]);

    return (int) $statement->fetchColumn() > 0;
}

if (!empty($_SESSION['employee_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$identifier = '';
$isAuthReady = passwordColumnExists($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals(csrfToken(), $submittedToken)) {
        $errors[] = 'Your session expired. Please refresh and try again.';
    }

    if (!$isAuthReady) {
        $errors[] = 'Authentication is not configured yet. Run auth_schema.sql in the employee_portal database.';
    }

    if ($identifier === '') {
        $errors[] = 'Enter your email address or employee ID.';
    }

    if ($password === '') {
        $errors[] = 'Enter your password.';
    }

    if (!$errors) {
        $employeeStatement = $pdo->prepare(
            "SELECT id, employee_id, first_name, last_name, email, password_hash
             FROM employees
             WHERE email = :identifier OR employee_id = :identifier
             LIMIT 1"
        );
        $employeeStatement->execute(['identifier' => $identifier]);
        $employee = $employeeStatement->fetch();

        if (!$employee || empty($employee['password_hash']) || !password_verify($password, $employee['password_hash'])) {
            $errors[] = 'The login details you entered are incorrect.';
        } else {
            session_regenerate_id(true);

            $_SESSION['employee_id'] = (int) $employee['id'];
            $_SESSION['employee_name'] = trim($employee['first_name'] . ' ' . $employee['last_name']);

            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="flex min-h-screen">
        <section class="hidden w-1/2 bg-slate-950 px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-600 text-lg font-bold shadow-sm">EP</div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-200">Employee Portal</p>
                    <p class="text-xs text-slate-400">Secure Workforce Access</p>
                </div>
            </div>

            <div class="max-w-lg">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-300">Corporate dashboard</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight">Sign in to manage your workday with clarity.</h1>
                <p class="mt-5 text-base leading-7 text-slate-300">
                    Access assigned tasks, completed work, team meetings, and employee profile details from one responsive portal.
                </p>
            </div>

        </section>

        <section class="flex w-full items-center justify-center px-4 py-10 sm:px-6 lg:w-1/2 lg:px-12">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-600 text-lg font-bold text-white shadow-sm">EP</div>
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Employee Portal</p>
                            <p class="text-xs text-slate-500">Secure Workforce Access</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Welcome back</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Login to your account</h2>
                    </div>

                    <?php if (!$isAuthReady): ?>
                        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Run <span class="font-semibold">auth_schema.sql</span> first to add secure password support.
                        </div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <ul class="space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo h($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form class="mt-6 space-y-5" action="login.php" method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">

                        <div>
                            <label for="identifier" class="block text-sm font-semibold text-slate-700">Email or Employee ID</label>
                            <input
                                id="identifier"
                                name="identifier"
                                type="text"
                                value="<?php echo h($identifier); ?>"
                                autocomplete="username"
                                required
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                            >
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                            >
                        </div>

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200"
                        >
                            Sign In
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-600">
                        New employee?
                        <a href="register.php" class="font-semibold text-blue-700 hover:text-blue-800">Create an account</a>
                    </p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
