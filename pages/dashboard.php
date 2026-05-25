<?php
require_once 'auth.php';
requireLogin();

// 1. Initialize Database and Manager FIRST
require_once 'connect.php';
require_once 'goalManager.php';

$db = (new Database())->getConnection();
$goalMan = new goalManager($db); // Now $goalMan is DEFINED

// 2. Load Translations SECOND
require_once 'translate.php';

// 3. Now use the variables
$current_lang = $current_lang ?? 'en';
$dir = ($current_lang === 'ar') ? 'rtl' : 'ltr';

$userGoals = $goalMan->getUserGoals($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= $dir ?>" data-bs-theme="light" data-theme="light">

<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('dashboard.title') ?></title>
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

    <!-- ── Navbar ─────────────────────────────────────────── -->
    <header>
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">

                <!-- Brand -->
                <a href="dashboard.php" class="navbar-brand">
                    <h3 class="text-brand-primary mb-0">Milestone</h3>
                </a>

                <!-- Mobile toggle -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarMain" aria-controls="navbarMain"
                    aria-expanded="false" aria-label="<?= __('nav.toggle_navigation') ?>">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Nav items -->
                <div class="collapse navbar-collapse justify-content-end align-items-center" id="navbarMain">
                    <ul class="navbar-nav align-items-lg-center gap-lg-1">

                        <li class="nav-item">
                            <a class="nav-link" href="tasks.php"><?= __('nav.tasks') ?></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php"><?= __('nav.goals') ?></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="achievements.php"><?= __('nav.achievements') ?></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php"><?= __('nav.analytics') ?></a>
                        </li>

                        <!-- Desktop divider -->
                        <li class="nav-item py-2 py-lg-1 col-12 col-lg-auto d-none d-lg-flex">
                            <div class="vr h-100 mx-2"></div>
                        </li>

                        <hr class="d-lg-none my-2" style="border-color: var(--color-border)">

                        <!-- Language -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                title="<?= __('nav.language') ?>">
                                <i class="bi bi-translate"></i>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="lang_switch.php?lang=ar" data-lang="ar">
                                        <?= __('languages.arabic') ?>
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="lang_switch.php?lang=en" data-lang="en">
                                        <?= __('languages.english') ?>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Theme -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
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

                        <!-- User avatar -->
                        <li class="nav-item dropdown">

                            <a class="nav-link dropdown-toggle avatar-link"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">

                                <span class="avatar">
                                    <?php echo htmlspecialchars(strtoupper($_SESSION['username'][0])); ?>
                                </span>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end">

                                <li>
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-person-circle"></i>
                                        <?= __('user.profile') ?>
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-gear"></i>
                                        <?= __('user.settings') ?>
                                    </a>
                                </li>

                                <li>
                                    <hr class="dropdown-divider"
                                        style="border-color: var(--color-border-light); margin: 0.35rem 0;">
                                </li>

                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right"></i>
                                        <?= __('user.sign_out') ?>
                                    </a>
                                </li>

                            </ul>
                        </li>

                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- ── Main Content ────────────────────────────────────── -->
    <main>

        <!-- Welcome -->
        <section class="welcome-section">
            <div class="container">
                <h1>
                    <?= __('dashboard.welcome') ?>, <?php echo htmlspecialchars(ucfirst($_SESSION['username'])); ?>
                    <span role="img" aria-label="wave">👋</span>
                </h1>
                <p class="welcome-subtitle"><?= __('dashboard.subtitle') ?></p>
            </div>
        </section>

        <!-- Add Goal -->
        <section class="add-goal">
            <div class="container">

                <!-- Trigger button -->
                <div class="mb-3">

                    <button class="add-goal-btn btn" id="addGoalBtn">
                        <i class="bi bi-plus-lg"></i>
                        <span><?= __('form.create_goal_btn') ?></span>
                    </button>

                </div>

                <!-- Inline creation form -->
                <form class="add-goal-form d-none" id="addGoalForm" novalidate>

                    <p class="add-goal-form-title"><?= __('form.new_goal_title') ?></p>

                    <!-- Close -->
                    <div class="close-btn">
                        <i class="bi bi-x-circle"
                            role="button"
                            title="<?= __('common.close') ?>"
                            id="closeGoalForm"></i>
                    </div>

                    <div class="form-group">

                        <label for="goal-title"><?= __('form.goal_title_label') ?></label>
<input type="text" class="form-control" id="goal-title" placeholder="<?= __('form.goal_title_placeholder') ?>" required autocomplete="off">

                        <div class="invalid-feedback"></div>

                    </div>

                    <div class="form-group">

                        <label for="category-select"><?= __('form.category_label') ?></label>
<select class="form-control" id="category-select">
    <option value="Health">🏃 <?= __('category.health') ?></option>
    <option value="Work">💼 <?= __('category.work') ?></option>
    <option value="Personal">🌱 <?= __('category.personal') ?></option>
    <option value="Religion">☪️ <?= __('category.religion') ?></option>
    <option value="Other">✦ <?= __('category.other') ?></option>
</select>
                    </div>

                    <div class="form-actions">

                        <button type="button"
                            class="btn btn-secondary"
                            id="cancelGoalBtn">

                            <?= __('common.cancel') ?>
                        </button>

                        <button type="submit" class="save-btn btn btn-primary">
                            <i class="bi bi-check2"></i>

                            <?= __('goals.save_goal') ?>
                        </button>

                    </div>

                </form>

            </div>
        </section>

        <!-- Goals List -->
        <section class="goals-list-section">
            <div class="container">

                <h3>🎯 <?= __('goals.life_goals') ?></h3>

                <div class="goals-progress my-4" id="goals-progress"></div>

                <div class="goal-filters" aria-label="<?= __('goals.filters') ?>">
                    <div class="goal-filter-field goal-search-field">
                        <label for="goal-search"><?= __('goals.search') ?></label>
                        <div class="goal-search-control">
                            <i class="bi bi-search"></i>
                            <input type="search" class="form-control" id="goal-search" placeholder="<?= __('goals.search_placeholder') ?>">
                        </div>
                    </div>

                    <div class="goal-filter-field">
                        <label for="goal-category-filter"><?= __('goals.category_filter') ?></label>
                        <select class="form-control" id="goal-category-filter">
                            <option value="all"><?= __('goals.filter_all_categories') ?></option>
                            <option value="health"><?= __('category.health') ?></option>
                            <option value="work"><?= __('category.work') ?></option>
                            <option value="personal"><?= __('category.personal') ?></option>
                            <option value="religion"><?= __('category.religion') ?></option>
                            <option value="other"><?= __('category.other') ?></option>
                        </select>
                    </div>

                    <div class="goal-filter-field">
                        <label for="goal-status-filter"><?= __('goals.status_filter') ?></label>
                        <select class="form-control" id="goal-status-filter">
                            <option value="all"><?= __('goals.filter_all_statuses') ?></option>
                            <option value="pending"><?= __('goals.status_pending') ?></option>
                            <option value="completed"><?= __('goals.status_completed') ?></option>
                        </select>
                    </div>

                    <div class="goal-filter-field">
                        <label for="goal-task-filter"><?= __('goals.task_filter') ?></label>
                        <select class="form-control" id="goal-task-filter">
                            <option value="all"><?= __('goals.filter_all_task_states') ?></option>
                            <option value="no-tasks"><?= __('goals.filter_no_tasks') ?></option>
                            <option value="in-progress"><?= __('goals.filter_tasks_in_progress') ?></option>
                            <option value="tasks-done"><?= __('goals.filter_tasks_done') ?></option>
                        </select>
                    </div>

                    <button type="button" class="btn btn-secondary goal-filter-reset" id="resetGoalFilters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span><?= __('goals.reset_filters') ?></span>
                    </button>
                </div>

                <div class="goal-cards" id="goals-container">

                    <!-- Loading state -->
                    <div class="text-center text-muted py-4"
                        style="grid-column: 1/-1;">

                        <div class="spinner-border spinner-border-sm me-2"
                            role="status"
                            aria-hidden="true"></div>

                        <span style="font-size: var(--text-sm);">
                            <?= __('goals.loading') ?>
                        </span>

                    </div>

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

    <script src="../js/dashboard.js" defer></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <!-- Navbar scroll effect -->
    <script>
        const nav = document.querySelector('.navbar');

        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 8);
        }, {
            passive: true
        });
    </script>

</body>

</html>
