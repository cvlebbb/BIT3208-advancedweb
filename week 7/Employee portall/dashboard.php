<?php
declare(strict_types=1);

/**
 * Employee Portal Dashboard
 *
 * This page retrieves the current employee, supervisor, tasks, and meetings
 * using PDO prepared statements, then renders a protected responsive corporate
 * dashboard with escaped output throughout.
 */

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

/** @var PDO $pdo */
$pdo = require __DIR__ . '/db.php';

// Dashboard access requires an authenticated employee session.
if (empty($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

/**
 * Escape output before rendering it into HTML.
 */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format database dates into a readable dashboard label.
 */
function formatDashboardDate(?string $date): string
{
    if (!$date) {
        return 'Not scheduled';
    }

    try {
        return (new DateTimeImmutable($date))->format('M j, Y');
    } catch (Exception) {
        return $date;
    }
}

/**
 * Format database times into a readable dashboard label.
 */
function formatDashboardTime(?string $time): string
{
    if (!$time) {
        return 'Time pending';
    }

    try {
        return (new DateTimeImmutable($time))->format('g:i A');
    } catch (Exception) {
        return $time;
    }
}

/**
 * Resolve the due-date column safely. The prompt names the column as
 * "due date", while many schemas use "due_date"; this keeps both supported.
 */
function resolveTaskDueDateColumn(PDO $pdo): string
{
    $columnStatement = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME IN ('due_date', 'due date')
         ORDER BY FIELD(COLUMN_NAME, 'due_date', 'due date')
         LIMIT 1"
    );
    $columnStatement->execute(['table_name' => 'tasks']);

    $columnName = $columnStatement->fetchColumn();

    if (in_array($columnName, ['due_date', 'due date'], true)) {
        return '`' . str_replace('`', '``', $columnName) . '`';
    }

    return '`due_date`';
}

/**
 * Convert employee status values into distinct badge classes.
 */
function employeeStatusBadgeClasses(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'remote' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'on leave' => 'bg-amber-50 text-amber-700 ring-amber-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
}

/**
 * Return urgency styling and text for pending tasks.
 */
function taskUrgencyDetails(?string $dueDate): array
{
    if (!$dueDate) {
        return [
            'label' => 'No due date',
            'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'card' => 'border-slate-200 bg-white',
        ];
    }

    try {
        $today = new DateTimeImmutable('today');
        $deadline = new DateTimeImmutable($dueDate);
        $daysRemaining = (int) $today->diff($deadline)->format('%r%a');

        if ($daysRemaining < 0) {
            return [
                'label' => 'Overdue',
                'badge' => 'bg-rose-50 text-rose-700 ring-rose-200',
                'card' => 'border-rose-200 bg-rose-50/70',
            ];
        }

        if ($daysRemaining === 0) {
            return [
                'label' => 'Due today',
                'badge' => 'bg-orange-50 text-orange-700 ring-orange-200',
                'card' => 'border-orange-200 bg-orange-50/60',
            ];
        }

        if ($daysRemaining <= 3) {
            return [
                'label' => 'Due soon',
                'badge' => 'bg-amber-50 text-amber-700 ring-amber-200',
                'card' => 'border-amber-200 bg-white',
            ];
        }

        return [
            'label' => 'On track',
            'badge' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'card' => 'border-slate-200 bg-white',
        ];
    } catch (Exception) {
        return [
            'label' => 'Review date',
            'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'card' => 'border-slate-200 bg-white',
        ];
    }
}

// Use the authenticated employee primary key from the active session.
$sessionEmployeeId = filter_var($_SESSION['employee_id'] ?? null, FILTER_VALIDATE_INT);
$currentEmployeeId = $sessionEmployeeId ?: 2;

// Retrieve employee profile information with the supervisor name in one query.
$employeeStatement = $pdo->prepare(
    "SELECT
        employee.id,
        employee.employee_id,
        employee.first_name,
        employee.last_name,
        employee.email,
        employee.job_title,
        employee.sector_department,
        employee.status,
        employee.supervisor_id,
        CONCAT_WS(' ', supervisor.first_name, supervisor.last_name) AS supervisor_name
     FROM employees AS employee
     LEFT JOIN employees AS supervisor ON supervisor.id = employee.supervisor_id
     WHERE employee.id = :employee_id
     LIMIT 1"
);
$employeeStatement->execute(['employee_id' => $currentEmployeeId]);
$employee = $employeeStatement->fetch();

if (!$employee) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Employee Not Found | Employee Portal</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <main class="flex min-h-screen items-center justify-center px-6">
            <section class="w-full max-w-lg rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Employee Portal</p>
                <h1 class="mt-3 text-2xl font-bold text-slate-950">Employee record not found</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    The requested employee profile could not be loaded. Please confirm the session employee ID or create employee ID 2 in the database.
                </p>
            </section>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$taskDueDateColumn = resolveTaskDueDateColumn($pdo);

// Retrieve all tasks for the employee and split them by status for rendering.
$tasksStatement = $pdo->prepare(
    "SELECT
        id,
        employee_id,
        task_title,
        description,
        status,
        {$taskDueDateColumn} AS due_date
     FROM tasks
     WHERE employee_id = :employee_id
     ORDER BY
        CASE WHEN status = 'Pending' THEN 0 ELSE 1 END,
        due_date IS NULL,
        due_date ASC,
        id DESC"
);
$tasksStatement->execute(['employee_id' => $currentEmployeeId]);
$tasks = $tasksStatement->fetchAll();

$pendingTasks = array_values(array_filter($tasks, static function (array $task): bool {
    return strtolower((string) $task['status']) === 'pending';
}));

$completedTasks = array_values(array_filter($tasks, static function (array $task): bool {
    return strtolower((string) $task['status']) === 'completed';
}));

// Retrieve upcoming meetings in chronological order.
$meetingsStatement = $pdo->prepare(
    "SELECT
        id,
        employee_id,
        meeting_title,
        description,
        meeting_date,
        meeting_time,
        platform_location
     FROM meetings
     WHERE employee_id = :employee_id
     ORDER BY meeting_date ASC, meeting_time ASC, id ASC"
);
$meetingsStatement->execute(['employee_id' => $currentEmployeeId]);
$meetings = $meetingsStatement->fetchAll();

$employeeFullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$supervisorName = trim((string) $employee['supervisor_name']) ?: 'Not assigned';
$todayLabel = (new DateTimeImmutable('today'))->format('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard | Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="border-b border-slate-200 bg-slate-950 text-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-72 lg:border-b-0">
            <div class="flex h-full flex-col px-5 py-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-600 text-lg font-bold shadow-sm">
                        EP
                    </div>
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-blue-200">Employee Portal</p>
                        <p class="text-xs text-slate-400">Corporate Workspace</p>
                    </div>
                </div>

                <nav class="mt-6 flex gap-2 overflow-x-auto pb-1 text-sm lg:mt-10 lg:block lg:space-y-2 lg:overflow-visible lg:pb-0" aria-label="Primary navigation">
                    <a href="#overview" class="flex min-w-fit items-center gap-3 rounded-lg bg-white/10 px-4 py-3 font-medium text-white ring-1 ring-white/10">
                        <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                        Overview
                    </a>
                    <a href="#tasks" class="flex min-w-fit items-center gap-3 rounded-lg px-4 py-3 font-medium text-slate-300 hover:bg-white/10 hover:text-white">
                        <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                        Tasks
                    </a>
                    <a href="#meetings" class="flex min-w-fit items-center gap-3 rounded-lg px-4 py-3 font-medium text-slate-300 hover:bg-white/10 hover:text-white">
                        <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                        Meetings
                    </a>
                    <a href="#profile" class="flex min-w-fit items-center gap-3 rounded-lg px-4 py-3 font-medium text-slate-300 hover:bg-white/10 hover:text-white">
                        <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                        Profile
                    </a>
                </nav>

                <div class="mt-6 rounded-lg border border-white/10 bg-white/5 p-4 lg:mt-auto">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Signed in as</p>
                    <p class="mt-2 text-sm font-semibold text-white"><?php echo h($employeeFullName); ?></p>
                    <p class="mt-1 text-xs text-slate-400"><?php echo h($employee['job_title']); ?></p>
                    <a href="logout.php" class="mt-4 inline-flex rounded-lg bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/10 hover:bg-white/15">
                        Sign out
                    </a>
                </div>
            </div>
        </aside>

        <div class="flex-1 lg:pl-72">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div>
                        <p class="text-sm font-medium text-blue-700"><?php echo h($todayLabel); ?></p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Welcome back, <?php echo h($employee['first_name']); ?></h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full px-3 py-1 text-sm font-semibold ring-1 <?php echo h(employeeStatusBadgeClasses($employee['status'])); ?>">
                            <?php echo h($employee['status']); ?>
                        </span>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600">
                            Employee ID: <span class="font-semibold text-slate-950"><?php echo h($employee['employee_id']); ?></span>
                        </div>
                        <a href="logout.php" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:border-blue-200 hover:text-blue-700">
                            Logout
                        </a>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <section id="overview" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Dashboard summary">
                    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Pending Tasks</p>
                        <p class="mt-3 text-3xl font-bold text-slate-950"><?php echo h((string) count($pendingTasks)); ?></p>
                    </article>
                    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Completed Tasks</p>
                        <p class="mt-3 text-3xl font-bold text-emerald-700"><?php echo h((string) count($completedTasks)); ?></p>
                    </article>
                    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Upcoming Meetings</p>
                        <p class="mt-3 text-3xl font-bold text-blue-700"><?php echo h((string) count($meetings)); ?></p>
                    </article>
                    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Supervisor</p>
                        <p class="mt-3 truncate text-lg font-bold text-slate-950"><?php echo h($supervisorName); ?></p>
                    </article>
                </section>

                <section class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div class="space-y-8">
                        <section id="tasks" aria-labelledby="pending-tasks-heading">
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Current Work</p>
                                    <h2 id="pending-tasks-heading" class="mt-1 text-xl font-bold text-slate-950">Pending Tasks</h2>
                                </div>
                                <p class="text-sm text-slate-500">Only tasks marked Pending are shown here.</p>
                            </div>

                            <?php if (!$pendingTasks): ?>
                                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600 shadow-sm">
                                    There are no pending tasks for this employee.
                                </div>
                            <?php else: ?>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <?php foreach ($pendingTasks as $task): ?>
                                        <?php $urgency = taskUrgencyDetails($task['due_date']); ?>
                                        <article class="rounded-lg border p-5 shadow-sm <?php echo h($urgency['card']); ?>">
                                            <div class="flex items-start justify-between gap-3">
                                                <h3 class="text-base font-bold text-slate-950"><?php echo h($task['task_title']); ?></h3>
                                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 <?php echo h($urgency['badge']); ?>">
                                                    <?php echo h($urgency['label']); ?>
                                                </span>
                                            </div>
                                            <p class="mt-3 text-sm leading-6 text-slate-600"><?php echo h($task['description']); ?></p>
                                            <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4 text-sm">
                                                <span class="font-medium text-slate-500">Due date</span>
                                                <time class="font-semibold text-slate-950" datetime="<?php echo h($task['due_date']); ?>">
                                                    <?php echo h(formatDashboardDate($task['due_date'])); ?>
                                                </time>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section aria-labelledby="completed-tasks-heading">
                            <div class="mb-4">
                                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Progress</p>
                                <h2 id="completed-tasks-heading" class="mt-1 text-xl font-bold text-slate-950">Completed Tasks</h2>
                            </div>

                            <?php if (!$completedTasks): ?>
                                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600 shadow-sm">
                                    Completed tasks will appear here once work is marked Completed.
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($completedTasks as $task): ?>
                                        <article class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-5 opacity-90 shadow-sm">
                                            <div class="flex gap-4">
                                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42.002L3.29 9.236a1 1 0 1 1 1.42-1.41l4.04 4.067 6.54-6.597a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <h3 class="font-bold text-slate-950"><?php echo h($task['task_title']); ?></h3>
                                                    <p class="mt-2 text-sm leading-6 text-slate-600"><?php echo h($task['description']); ?></p>
                                                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-emerald-700">
                                                        Completed
                                                        <?php if (!empty($task['due_date'])): ?>
                                                            by <?php echo h(formatDashboardDate($task['due_date'])); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>

                    <aside id="profile" class="space-y-8">
                        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="profile-heading">
                            <div class="flex items-center gap-4">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-600 text-lg font-bold text-white shadow-sm">
                                    <?php echo h(strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1))); ?>
                                </div>
                                <div class="min-w-0">
                                    <h2 id="profile-heading" class="truncate text-xl font-bold text-slate-950"><?php echo h($employeeFullName); ?></h2>
                                    <p class="mt-1 text-sm text-slate-500"><?php echo h($employee['job_title']); ?></p>
                                </div>
                            </div>

                            <dl class="mt-6 space-y-4 text-sm">
                                <div>
                                    <dt class="font-medium text-slate-500">Employee ID</dt>
                                    <dd class="mt-1 font-semibold text-slate-950"><?php echo h($employee['employee_id']); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-slate-500">Email</dt>
                                    <dd class="mt-1 break-words font-semibold text-slate-950"><?php echo h($employee['email']); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-slate-500">Sector / Department</dt>
                                    <dd class="mt-1 font-semibold text-slate-950"><?php echo h($employee['sector_department']); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-slate-500">Supervisor</dt>
                                    <dd class="mt-1 font-semibold text-slate-950"><?php echo h($supervisorName); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-slate-500">Status</dt>
                                    <dd class="mt-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold ring-1 <?php echo h(employeeStatusBadgeClasses($employee['status'])); ?>">
                                            <?php echo h($employee['status']); ?>
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section id="meetings" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="meetings-heading">
                            <div class="mb-5">
                                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Schedule</p>
                                <h2 id="meetings-heading" class="mt-1 text-xl font-bold text-slate-950">Upcoming Meetings</h2>
                            </div>

                            <?php if (!$meetings): ?>
                                <div class="rounded-lg border border-dashed border-slate-300 p-5 text-sm text-slate-600">
                                    No upcoming meetings are scheduled.
                                </div>
                            <?php else: ?>
                                <ol class="relative space-y-5 border-l border-slate-200 pl-5">
                                    <?php foreach ($meetings as $meeting): ?>
                                        <li class="relative">
                                            <span class="absolute -left-[1.62rem] top-1.5 h-3 w-3 rounded-full border-2 border-white bg-blue-600 ring-2 ring-blue-100"></span>
                                            <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                    <h3 class="font-bold text-slate-950"><?php echo h($meeting['meeting_title']); ?></h3>
                                                    <div class="text-sm font-semibold text-blue-700">
                                                        <time datetime="<?php echo h($meeting['meeting_date']); ?>"><?php echo h(formatDashboardDate($meeting['meeting_date'])); ?></time>
                                                        <span aria-hidden="true">/</span>
                                                        <time datetime="<?php echo h($meeting['meeting_time']); ?>"><?php echo h(formatDashboardTime($meeting['meeting_time'])); ?></time>
                                                    </div>
                                                </div>
                                                <p class="mt-3 text-sm leading-6 text-slate-600"><?php echo h($meeting['description']); ?></p>
                                                <p class="mt-3 rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200">
                                                    <?php echo h($meeting['platform_location']); ?>
                                                </p>
                                            </article>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endif; ?>
                        </section>
                    </aside>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
