<?php
require_once 'auth.php';
requireLogin();

require_once 'connect.php';
require_once 'analyticsManager.php';
require_once 'translate.php';

$db = (new Database())->getConnection();
new analyticsManager($db);

$current_lang = $current_lang ?? 'en';
$dir = ($current_lang === 'ar') ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= $dir ?>" data-bs-theme="light" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('analytics.title') ?></title>
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
                        <li class="nav-item"><a class="nav-link" href="tasks.php"><?= __('nav.tasks') ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><?= __('nav.goals') ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="achievements.php"><?= __('nav.achievements') ?></a></li>
                        <li class="nav-item"><a class="nav-link active" href="analytics.php"><?= __('nav.analytics') ?></a></li>

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
        <section class="analytics-hero">
            <div class="container">
                <p class="achievement-kicker"><?= __('analytics.kicker') ?></p>
                <h1><?= __('analytics.heading') ?></h1>
                <p class="welcome-subtitle"><?= __('analytics.subtitle') ?></p>
            </div>
        </section>

        <section class="analytics-workspace">
            <div class="container">
                <div class="analytics-grid">
                    <section class="analytics-card next-action-card" id="next-action-card">
                        <div class="text-center text-muted py-4">
                            <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                            <?= __('analytics.loading') ?>
                        </div>
                    </section>

                    <section class="analytics-card analytics-summary" id="analytics-summary"></section>

                    <section class="analytics-card trend-card">
                        <div class="analytics-card-head">
                            <h3><?= __('analytics.weekly_output') ?></h3>
                            <span><?= __('analytics.last_six_weeks') ?></span>
                        </div>
                        <div class="weekly-bars" id="weekly-bars"></div>
                    </section>

                    <section class="analytics-card category-card">
                        <div class="analytics-card-head">
                            <h3><?= __('analytics.category_balance') ?></h3>
                            <span><?= __('analytics.balance_hint') ?></span>
                        </div>
                        <div class="category-analytics" id="category-analytics"></div>
                    </section>

                    <section class="analytics-card risk-card">
                        <div class="analytics-card-head">
                            <h3><?= __('analytics.attention_zone') ?></h3>
                            <span><?= __('analytics.attention_hint') ?></span>
                        </div>
                        <div class="analytics-list" id="attention-list"></div>
                    </section>

                    <section class="analytics-card stuck-card">
                        <div class="analytics-card-head">
                            <h3><?= __('analytics.stuck_goals') ?></h3>
                            <span><?= __('analytics.stuck_hint') ?></span>
                        </div>
                        <div class="analytics-list" id="stuck-goals"></div>
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
    <script src="../js/analytics.js" defer></script>
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
