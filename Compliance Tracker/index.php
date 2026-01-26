<?php<?php

require_once __DIR__ . '/config.php';require_once __DIR__ . '/config.php';

require_once __DIR__ . '/auth.php';require_once __DIR__ . '/auth.php';



$error = '';$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Basic CSRF check    // Basic CSRF check

    if (empty($_POST['csrf']) || !hash_equals(csrf_token(), $_POST['csrf'])) {    if (empty($_POST['csrf']) || !hash_equals(csrf_token(), $_POST['csrf'])) {

        $error = 'Invalid CSRF token.';        $error = 'Invalid CSRF token.';

    } else {    } else {

        $identifier = trim($_POST['identifier'] ?? '');        $identifier = trim($_POST['identifier'] ?? '');

        $password = $_POST['password'] ?? '';        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {        if (empty($identifier) || empty($password)) {

            $error = 'Provide username/email and password.';            $error = 'Provide username/email and password.';

        } else {        } else {

            if (attempt_login($identifier, $password)) {            if (attempt_login($identifier, $password)) {

                // Redirect based on role                // Redirect based on role

                $role = $_SESSION['role'] ?? '';                $role = $_SESSION['role'] ?? '';

                if ($role === 'admin') {                if ($role === 'admin') {

                    header('Location: admin.php');                    header('Location: admin.php');

                    exit;                    exit;

                } else {                } else {

                    header('Location: employee.php');                    header('Location: employee.php');

                    exit;                    exit;

                }                }

            } else {            } else {

                $error = 'Invalid credentials.';                $error = 'Invalid credentials.';

            }            }

        }        }

    }    }

}}

?><!doctype html>?><!doctype html>

<html lang="en"><html lang="en">

<head><head>

    <meta charset="utf-8">    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Compliance Tracker — Login</title>    <title>Compliance Tracker — Login</title>

    <link rel="stylesheet" href="assets/style.css">    <link rel="stylesheet" href="assets/style.css">

</head></head>

<body class="center-page"><body class="center-page">

    <main class="card login-card">    <main class="card login-card">

        <h1>Compliance Tracker</h1>        <h1>Compliance Tracker</h1>

        <p class="muted">Sign in to continue</p>        <p class="muted">Sign in to continue</p>

        <?php if ($error): ?>        <?php if ($error): ?>

            <div class="error"><?= e($error) ?></div>            <div class="error"><?= e($error) ?></div>

        <?php endif; ?>        <?php endif; ?>

        <form method="post" autocomplete="off">        <form method="post" autocomplete="off">

            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <label>Username or Email            <label>Username or Email

                <input type="text" name="identifier" required>                <input type="text" name="identifier" required>

            </label>            </label>

            <label>Password            <label>Password

                <input type="password" name="password" required>                <input type="password" name="password" required>

            </label>            </label>

            <button class="btn" type="submit">Sign In</button>            <button class="btn" type="submit">Sign In</button>

        </form>        </form>

        <p class="muted small">If you haven't created an admin account yet, run <code>create_admin.php</code> once from the browser.</p>        <p class="muted small">If you haven't created an admin account yet, run <code>create_admin.php</code> once from the browser.</p>

    </main>    </main>

</body></body>

</html></html>

