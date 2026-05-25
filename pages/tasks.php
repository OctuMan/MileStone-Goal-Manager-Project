<?php
require_once 'auth.php';
requireLogin();

require_once 'connect.php';
require_once 'goalManager.php';
require_once 'taskManager.php';
require_once 'translate.php';

$db = (new Database())->getConnection();
$goalMan = new goalManager($db);
$taskMan = new taskManager($db);

$current_lang = $current_lang ?? 'en';
$dir = ($current_lang === 'ar') ? 'rtl' : 'ltr';
$userGoals = $goalMan->getUserGoals($_SESSION['user_id']);
$selectedGoalId = isset($_GET['goal_id']) ? (int) $_GET['goal_id'] : 0;
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= $dir ?>" data-bs-theme="light" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('tasks.title') ?></title>
    <script src="../js/app-preferences.js"></script>

    <?php if ($current_lang === 'ar'): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php endif; ?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Iosevka+Charon:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">
                <a href="dashboard.php" class="navbar-brand">
                    <h3 class="text-brand-primary mb-0">Milestone</h3>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarMain" aria-controls="navbarMain"
                    aria-expanded="false" aria-label="<?= __('nav.toggle_navigation') ?>">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-end align-items-center" id="navbarMain">
                    <ul class="navbar-nav align-items-lg-center gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link active" href="tasks.php"><?= __('nav.tasks') ?></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><?= __('nav.goals') ?></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="achievements.php"><?= __('nav.achievements') ?></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php"><?= __('nav.analytics') ?></a>
                        </li>

                        <li class="nav-item py-2 py-lg-1 col-12 col-lg-auto d-none d-lg-flex">
                            <div class="vr h-100 mx-2"></div>
                        </li>

                        <hr class="d-lg-none my-2" style="border-color: var(--border)">

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false"
                                title="<?= __('nav.language') ?>">
                                <i class="bi bi-translate"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="lang_switch.php?lang=ar" data-lang="ar"><?= __('languages.arabic') ?></a></li>
                                <li><a class="dropdown-item" href="lang_switch.php?lang=en" data-lang="en"><?= __('languages.english') ?></a></li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false"
                                title="<?= __('theme.title') ?>">
                                <i class="bi bi-moon-stars-fill"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end theme-menu">
                                <li>
                                    <a class="dropdown-item" href="#" data-theme-value="light">
                                        <i class="bi bi-sun-fill me-2"></i>
                                        <span><?= __('theme.light') ?></span>
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="#" data-theme-value="dark">
                                        <i class="bi bi-moon-fill me-2"></i>
                                        <span><?= __('theme.dark') ?></span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle avatar-link" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="avatar"><?= htmlspecialchars(strtoupper($_SESSION['username'][0])) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle"></i><?= __('user.profile') ?></a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i><?= __('user.settings') ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i><?= __('user.sign_out') ?></a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="welcome-section">
            <div class="container">
                <h1><?= __('tasks.heading') ?></h1>
                <p class="welcome-subtitle"><?= __('tasks.subtitle') ?></p>
            </div>
        </section>

        <section class="tasks-workspace">
            <div class="container">
                <div class="task-layout">
                    <form class="task-composer" id="taskForm" novalidate>
                        <p class="add-goal-form-title"><?= __('tasks.new_task') ?></p>

                        <div class="form-group">
                            <label for="task-title"><?= __('tasks.task_title') ?></label>
                            <input type="text" class="form-control" id="task-title" required maxlength="255" autocomplete="off">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group">
                            <label for="task-goal"><?= __('tasks.related_goal') ?></label>
                            <select class="form-control" id="task-goal">
                                <option value="none"><?= __('tasks.no_goal') ?></option>
                                <?php foreach ($userGoals as $goal): ?>
                                    <option value="<?= (int) $goal['goal_id'] ?>" <?= ((int) $goal['goal_id'] === $selectedGoalId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($goal['goal_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="task-due"><?= __('tasks.due_date') ?></label>
                            <input type="date" class="form-control" id="task-due">
                        </div>

                        <div class="form-group">
                            <label for="task-notes"><?= __('tasks.notes') ?></label>
                            <textarea class="form-control task-notes" id="task-notes" rows="4"></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="reset" class="btn btn-secondary" id="resetTaskBtn">
                                <?= __('common.cancel') ?>
                            </button>
                            <button type="submit" class="btn btn-primary" id="saveTaskBtn">
                                <i class="bi bi-check2"></i>
                                <?= __('tasks.save_task') ?>
                            </button>
                        </div>
                    </form>

                    <section class="task-list-panel">
                        <div class="task-list-header">
                            <h3><?= __('tasks.list_title') ?></h3>
                            <div class="task-filter-group" role="group" aria-label="<?= __('tasks.filters') ?>">
                                <button type="button" class="task-filter active" data-filter="all"><?= __('tasks.filter_all') ?></button>
                                <button type="button" class="task-filter" data-filter="standalone"><?= __('tasks.filter_standalone') ?></button>
                                <button type="button" class="task-filter" data-filter="linked"><?= __('tasks.filter_linked') ?></button>
                            </div>
                        </div>

                        <div class="task-stats" id="task-stats"></div>
                        <div class="task-list" id="task-list">
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                                <?= __('tasks.loading') ?>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </section>
    </main>

    <script>
        const i18n = <?= json_encode($translations ?? []) ?>;

        function __(key) {
            return key.split('.').reduce((o, i) => (o ? o[i] : undefined), i18n) || key;
        }
    </script>
    <script src="../js/tasks.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script>
        const nav = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 8);
        }, { passive: true });
    </script>
</body>

</html>
