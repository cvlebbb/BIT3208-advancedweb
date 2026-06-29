<?php
declare(strict_types=1);

/**
 * Employee Portal Registration
 *
 * Creates a new employee record and stores a secure password hash. All SQL
 * operations use PDO prepared statements.
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

$allowedStatuses = ['Active', 'Remote', 'On Leave'];
$isAuthReady = passwordColumnExists($pdo);
$errors = [];

$formData = [
    'employee_id' => '',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'job_title' => '',
    'sector_department' => '',
    'status' => 'Active',
    'supervisor_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $field => $defaultValue) {
        $formData[$field] = trim((string) ($_POST[$field] ?? $defaultValue));
    }

    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals(csrfToken(), $submittedToken)) {
        $errors[] = 'Your session expired. Please refresh and try again.';
    }

    if (!$isAuthReady) {
        $errors[] = 'Authentication is not configured yet. Run auth_schema.sql in the employee_portal database.';
    }

    foreach (['employee_id', 'first_name', 'last_name', 'email', 'job_title', 'sector_department'] as $requiredField) {
        if ($formData[$requiredField] === '') {
            $errors[] = ucfirst(str_replace('_', ' ', $requiredField)) . ' is required.';
        }
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!in_array($formData['status'], $allowedStatuses, true)) {
        $errors[] = 'Choose a valid employee status.';
    }

    if ($formData['supervisor_id'] !== '' && !filter_var($formData['supervisor_id'], FILTER_VALIDATE_INT)) {
        $errors[] = 'Supervisor ID must be a valid number.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        $duplicateStatement = $pdo->prepare(
            "SELECT employee_id, email
             FROM employees
             WHERE employee_id = :employee_id OR email = :email
             LIMIT 1"
        );
        $duplicateStatement->execute([
            'employee_id' => $formData['employee_id'],
            'email' => $formData['email'],
        ]);
        $duplicateEmployee = $duplicateStatement->fetch();

        if ($duplicateEmployee) {
            if ($duplicateEmployee['employee_id'] === $formData['employee_id']) {
                $errors[] = 'That employee ID is already registered.';
            }

            if ($duplicateEmployee['email'] === $formData['email']) {
                $errors[] = 'That email address is already registered.';
            }
        }
    }

    if (!$errors) {
        $insertStatement = $pdo->prepare(
            "INSERT INTO employees (
                employee_id,
                first_name,
                last_name,
                email,
                job_title,
                sector_department,
                status,
                supervisor_id,
                password_hash
             ) VALUES (
                :employee_id,
                :first_name,
                :last_name,
                :email,
                :job_title,
                :sector_department,
                :status,
                :supervisor_id,
                :password_hash
             )"
        );

        $insertStatement->execute([
            'employee_id' => $formData['employee_id'],
            'first_name' => $formData['first_name'],
            'last_name' => $formData['last_name'],
            'email' => $formData['email'],
            'job_title' => $formData['job_title'],
            'sector_department' => $formData['sector_department'],
            'status' => $formData['status'],
            'supervisor_id' => $formData['supervisor_id'] === '' ? null : (int) $formData['supervisor_id'],
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        session_regenerate_id(true);

        $_SESSION['employee_id'] = (int) $pdo->lastInsertId();
        $_SESSION['employee_name'] = trim($formData['first_name'] . ' ' . $formData['last_name']);

        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8">
        <section class="grid w-full gap-8 lg:grid-cols-[24rem_minmax(0,1fr)]">
            <aside class="rounded-lg bg-slate-950 p-8 text-white shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-600 text-lg font-bold shadow-sm">EP</div>
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-blue-200">Employee Portal</p>
                        <p class="text-xs text-slate-400">New Employee Access</p>
                    </div>
                </div>

                <div class="mt-12">
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-300">Registration</p>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight">Create your employee account.</h1>
                    <p class="mt-5 text-sm leading-6 text-slate-300">
                        Register once, then use your email address or employee ID to access your dashboard.
                    </p>
                </div>

                <div class="mt-10 rounded-lg border border-white/10 bg-white/5 p-4 text-sm text-slate-300">
                    Passwords are stored with one-way hashing. The portal never stores plain-text passwords.
                </div>
            </aside>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Get started</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Employee registration</h2>
                    </div>
                    <a href="login.php" class="text-sm font-semibold text-blue-700 hover:text-blue-800">Back to login</a>
                </div>

                <?php if (!$isAuthReady): ?>
                    <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Run <span class="font-semibold">auth_schema.sql</span> first to add secure password support.
                    </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="grid gap-1 sm:grid-cols-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo h($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form class="mt-6 grid gap-5 sm:grid-cols-2" action="register.php" method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">

                    <div>
                        <label for="employee_id" class="block text-sm font-semibold text-slate-700">Employee ID</label>
                        <input id="employee_id" name="employee_id" type="text" value="<?php echo h($formData['employee_id']); ?>" required class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
                        <input id="email" name="email" type="email" value="<?php echo h($formData['email']); ?>" autocomplete="email" required class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="first_name" class="block text-sm font-semibold text-slate-700">First Name</label>
                        <input id="first_name" name="first_name" type="text" value="<?php echo h($formData['first_name']); ?>" autocomplete="given-name" required class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-semibold text-slate-700">Last Name</label>
                        <input id="last_name" name="last_name" type="text" value="<?php echo h($formData['last_name']); ?>" autocomplete="family-name" required class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="job_title" class="block text-sm font-semibold text-slate-700">Job Title</label>
                        <input id="job_title" name="job_title" type="text" value="<?php echo h($formData['job_title']); ?>" required class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="sector_department" class="block text-sm font-semibold text-slate-700">Sector / Department</label>
                        <input id="sector_department" name="sector_department" type="text" value="<?php echo h($formData['sector_department']); ?>" required class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
                        <select id="status" name="status" class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                            <?php foreach ($allowedStatuses as $status): ?>
                                <option value="<?php echo h($status); ?>" <?php echo $formData['status'] === $status ? 'selected' : ''; ?>>
                                    <?php echo h($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="supervisor_id" class="block text-sm font-semibold text-slate-700">Supervisor Database ID</label>
                        <input id="supervisor_id" name="supervisor_id" type="number" min="1" value="<?php echo h($formData['supervisor_id']); ?>" class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div class="sm:col-span-2">
                        <button type="submit" class="flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            Create Account
                        </button>
                    </div>
                </form>
            </section>
        </section>
    </main>
</body>
</html>
