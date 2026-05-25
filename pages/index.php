<?php
require_once 'connect.php';
require_once 'user.php';
require_once 'authManager.php';
require_once 'auth.php';

$db = (new Database())->getConnection();
$authManager = new AuthManager($db);

if (checkPersistentSession($authManager)) {
    header("Location: dashboard.php");
    exit;
}

require_once 'translate.php';

$current_lang = $current_lang ?? 'en';
$dir = ($current_lang === 'ar') ? 'rtl' : 'ltr';
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= $dir ?>" data-bs-theme="light" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milestone</title>
    <script src="../js/app-preferences.js"></script>

    <?php if ($current_lang === 'ar'): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php endif; ?>

    <link rel="stylesheet" href="../css/style.css">

    <!-- bootstrap icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- google fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Iosevka+Charon:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
</head>

<body>

    <header class="p-2 mb-3">
        <nav class="navbar navbar-expand-lg bg-body-tertiary fixed-top">
            <div class="container-xxl">

                <div class="logo-div justify-content-center align-item-center d-flex">
                    <a href="../pages/index.php" class="navbar-brand">
                        <h3 class="fw-bold text-brand-primary">Milestone</h3>
                    </a>
                </div>

                <!-- toggle nav -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent"
                    aria-expanded="false"
                    aria-label="Toggle navigation">

                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-end align-items-center" id="navbarSupportedContent">

                    <ul class="navbar-nav align-items-lg-center">

                        <li class="nav-item">
                            <a class="nav-link" href="">
                                <button class="btn btn-dark ">
                                    <?= __('login.sign-in') ?>
                                </button>
                            </a>
                        </li>

                        <li class="nav-item py-2 py-lg-1 col-12 col-lg-auto">
                            <div class="vr d-none d-lg-flex h-100 mx-lg-2 text-secondary"></div>
                            <hr class="d-lg-none my-2 text-white-50">
                        </li>

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

                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="lang_switch.php?lang=ar" data-lang="ar">
                                        <span><?= __('languages.arabic') ?></span>
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="lang_switch.php?lang=en" data-lang="en">
                                        <span><?= __('languages.english') ?></span>
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
                                aria-expanded="false">

                                <i class="bi bi-moon-stars-fill"></i>
                            </a>

                            <ul class="dropdown-menu theme-menu">
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

                    </ul>
                </div>

            </div>
        </nav>
    </header>

    

        <section id="register-login-form">

            <div class="container p-2 p-lg-5">

                <div class="row justify-content-center align-items-center">

                    <div class="login-form-section col col-lg-6">

                        <form class="form" id="myForm" novalidate>

                            <h2 class="login-desc-header fw-bold fs-1">
                                <?= __('login.sign-in') ?>
                            </h2>

                            <p class="text-muted">
                                <?= __('login.enter-email') ?>
                            </p>

                            <!-- Email check card -->
                            <div id="state-identity" class="auth-state state">

                                <div class="card text-dark mb-3 px-2 py-4">

                                    <div class="card-body">

                                        <div class="mb-4">

                                            <label for="InputEmail" class="form-label">
                                                <?= __('login.email-address') ?>
                                            </label>

                                            <input
                                                type="email"
                                                class="form-control"
                                                required
                                                id="InputEmail"
                                                aria-describedby="emailHelp"
                                                name="userEmail"
                                                placeholder="example@example.com">

                                            <div class="invalid-feedback d-none">
                                                <?= __('login.valid-email') ?>
                                            </div>

                                        </div>

                                        <button
                                            type="submit"
                                            class="btn check-btn bg-brand-primary rounded-pill text-white w-100"
                                            id="auth-btn">

                                            <i class="bi bi-arrow-right me-1"></i>
                                            <?= __('login.continue') ?>

                                        </button>

                                        <div class="separator d-flex gap-2 w-100 my-4">

                                            <div class="hr w-50">
                                                <hr>
                                            </div>

                                            <span><?= __('login.or') ?></span>

                                            <div class="hr w-50">
                                                <hr>
                                            </div>

                                        </div>

                                        <button
                                            type="button"
                                            class="btn border rounded-pill w-100 gap-2 justify-content-center bg-white d-flex align-items-center mb-2">

                                            <img src="../assets/google.svg" alt="Google" width="16" height="16">

                                            <span>
                                                <?= __('login.continue-google') ?>
                                            </span>

                                        </button>

                                    </div>
                                </div>
                            </div>

                            <!-- Email Exists form -->
                            <div id="state-login" class="login-state state">

                                <div class="card text-dark bg-light mb-3 px-2 py-4">

                                    <div class="card-header bg-light">

                                        <a href="" class="btn back-btn rounded-pill btn-secondary">

                                            <i class="bi bi-arrow-left-short"></i>

                                            <span>
                                                <?= __('login.back') ?>
                                            </span>

                                        </a>
                                    </div>

                                    <div class="card-body">

                                        <div class="account d-flex align-items-center gap-2 mb-3">

                                            <span>
                                                <?= __('login.account') ?>:
                                            </span>

                                            <span class="display-email-target badge text-bg-success rounded-pill"></span>

                                        </div>

                                        <div class="mb-3">

                                            <label for="login-password" class="form-label">
                                                <?= __('login.password') ?>
                                            </label>

                                            <div class="input-group mb-3">

                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    required
                                                    id="login-password"
                                                    name="userPass">

                                                <span
                                                    type="button"
                                                    class="input-group-text btn btn-secondary cursor"
                                                    id="show-login-password">

                                                    <i class="bi bi-eye"></i>

                                                </span>

                                            </div>
                                        </div>

                                        <div class="mb-3 form-check">

                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                id="remember-me-checkbox"
                                                name="rememberMeStatus">

                                            <label class="form-check-label" for="remember-me-checkbox">
                                                <?= __('login.remember-me') ?>
                                            </label>

                                        </div>

                                        <button
                                            type="submit"
                                            class="btn check-pass-btn rounded-pill bg-brand-primary text-white w-100"
                                            id="login-btn">

                                            <?= __('login.log-in') ?>

                                        </button>

                                        <p class="text-muted small mt-2">
                                            <a href="">
                                                <?= __('login.forget-password') ?>
                                            </a>
                                        </p>

                                    </div>
                                </div>
                            </div>

                            <!-- New Email form -->
                            <div id="state-signUp" class="signUp-state state">

                                <div class="card text-dark bg-light mb-3 px-2 py-4">

                                    <div class="card-header bg-light">

                                        <a href="" class="btn rounded-pill back-btn btn-secondary">

                                            <i class="bi bi-arrow-left-short"></i>

                                            <span>
                                                <?= __('login.back') ?>
                                            </span>

                                        </a>

                                    </div>

                                    <div class="card-body">

                                        <div class="account d-flex align-items-center gap-2 mb-3">

                                            <span class="text-muted small">
                                                <?= __('login.account') ?>:
                                            </span>

                                            <span class="display-email-target badge text-bg-success rounded-pill"></span>

                                        </div>

                                        <label for="signup-username" class="form-label">
                                            <?= __('login.username') ?>
                                        </label>

                                        <div class="input-group mb-3">

                                            <input
                                                type="text"
                                                maxlength="15"
                                                required
                                                id="signup-username"
                                                name="username"
                                                class="form-control"
                                                placeholder="<?= __('login.username-placeholder') ?>"
                                                aria-label="<?= __('login.username') ?>"
                                                aria-describedby="addon-wrapping">

                                            <div class="invalid-feedback d-none mt-3">

                                                <b><?= __('login.username-rules') ?></b>

                                                <ul>
                                                    <li><?= __('login.username-rule-1') ?></li>
                                                    <li><?= __('login.username-rule-2') ?></li>
                                                    <li><?= __('login.username-rule-3') ?></li>
                                                    <li><?= __('login.username-rule-4') ?></li>
                                                </ul>

                                            </div>
                                        </div>

                                        <div class="mb-3">

                                            <label for="signup-password" class="form-label">
                                                <?= __('login.password') ?>
                                            </label>

                                            <div class="input-group mb-3">

                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    name="NewUserPass"
                                                    required
                                                    minlength="12"
                                                    id="signup-password">

                                                <span
                                                    class="input-group-text btn border bg-white cursor"
                                                    id="show-signUp-password">

                                                    <i class="bi bi-eye"></i>

                                                </span>

                                                <div class="invalid-feedback d-none mt-3">

                                                    <p>
                                                        <?= __('login.password-rules') ?>
                                                    </p>

                                                </div>

                                            </div>

                                            <label for="confirm-password" class="form-label">
                                                <?= __('login.confirm-password') ?>
                                            </label>

                                            <div class="input-group mb-3">

                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    required
                                                    minlength="12"
                                                    id="confirm-password">

                                                <span
                                                    class="input-group-text btn bg-white border cursor"
                                                    id="show-confirm-password">

                                                    <i class="bi bi-eye"></i>

                                                </span>

                                                <div class="invalid-feedback mt-3">

                                                    <p>
                                                        <?= __('login.password-not-match') ?>
                                                    </p>

                                                </div>

                                            </div>
                                        </div>

                                        <button
                                            type="submit"
                                            class="btn check-pass-btn bg-brand-primary rounded-pill text-white w-100"
                                            id="signUp-pass">

                                            <?= __('login.sign-up') ?>

                                        </button>

                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>

        </section>

    

    <footer></footer>

    <script>
        const i18n = <?= json_encode($translations ?? []) ?>;

        function __(key) {
            return key.split('.').reduce((o, i) => (o ? o[i] : undefined), i18n) || key;
        }
    </script>

    <script src="../js/custom.js" defer></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

</body>

</html>
