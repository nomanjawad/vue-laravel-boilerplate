<?php
/**
 * Token-gated post-deploy recovery tool.
 *
 * Lives outside the Laravel boot path so it runs even when the app is fatally
 * broken (missing extension, bad .env, white-screen). Reports environment
 * health and exposes a few one-click recovery actions.
 *
 * Disabled by default. To enable on a server, set DEBUG_TOKEN=<long-string>
 * in .env and visit /debug.php?t=<token>. Anything other than a matching token
 * returns a blank 404, so leaving this file deployed is safe.
 *
 * SECURITY: this file uses exec() (one of the few in the codebase). It is the
 * recovery hatch, not part of the app. Keep DEBUG_TOKEN secret.
 */

// --- Resolve the token from .env without booting Laravel ---------------------
$envPath = __DIR__.'/../.env';
$envToken = null;
$dbCreds = ['host' => '127.0.0.1', 'port' => '3306', 'database' => '', 'username' => '', 'password' => ''];

if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k);
        $v = trim($v, " \"'\t");
        if ($k === 'DEBUG_TOKEN') {
            $envToken = $v;
        }
        if ($k === 'DB_HOST') {
            $dbCreds['host'] = $v;
        }
        if ($k === 'DB_PORT') {
            $dbCreds['port'] = $v;
        }
        if ($k === 'DB_DATABASE') {
            $dbCreds['database'] = $v;
        }
        if ($k === 'DB_USERNAME') {
            $dbCreds['username'] = $v;
        }
        if ($k === 'DB_PASSWORD') {
            $dbCreds['password'] = $v;
        }
    }
}

$incomingToken = $_GET['t'] ?? $_POST['t'] ?? null;

if (! $envToken || ! $incomingToken || ! hash_equals($envToken, $incomingToken)) {
    http_response_code(404);
    exit;
}

// --- Optional one-click recovery actions ------------------------------------
$actionOutput = null;
$action = $_POST['action'] ?? null;
if ($action && ! empty($_POST['confirm'])) {
    $base = dirname(__DIR__);
    $cmd = match ($action) {
        'optimize:clear'      => "cd {$base} && php artisan optimize:clear 2>&1",
        'storage:link'        => "cd {$base} && php artisan storage:link 2>&1",
        'migrate'             => "cd {$base} && php artisan migrate --force 2>&1",
        'responsecache:clear' => "cd {$base} && php artisan responsecache:clear 2>&1",
        default               => null,
    };
    if ($cmd) {
        $actionOutput = shell_exec($cmd);
    }
}

// --- Checks ------------------------------------------------------------------
$checks = [];

$checks['PHP version'] = ['ok' => version_compare(PHP_VERSION, '8.3', '>='), 'detail' => PHP_VERSION];
foreach (['gd', 'pdo_mysql', 'openssl', 'mbstring', 'bcmath', 'intl', 'zip', 'curl'] as $ext) {
    $checks["ext: {$ext}"] = ['ok' => extension_loaded($ext), 'detail' => extension_loaded($ext) ? 'loaded' : 'MISSING'];
}

$checks['.env present'] = ['ok' => file_exists($envPath), 'detail' => $envPath];
$checks['DB_DATABASE set'] = ['ok' => $dbCreds['database'] !== '', 'detail' => $dbCreds['database'] ?: 'empty'];

try {
    $dsn = "mysql:host={$dbCreds['host']};port={$dbCreds['port']};dbname={$dbCreds['database']}";
    $pdo = new PDO($dsn, $dbCreds['username'], $dbCreds['password'], [PDO::ATTR_TIMEOUT => 3]);
    $pdo->query('SELECT 1');
    $checks['DB connection'] = ['ok' => true, 'detail' => 'reachable'];
} catch (Throwable $e) {
    $checks['DB connection'] = ['ok' => false, 'detail' => $e->getMessage()];
}

$storageLink = __DIR__.'/storage';
$checks['public/storage symlink'] = [
    'ok' => is_link($storageLink) || is_dir($storageLink),
    'detail' => is_link($storageLink) ? readlink($storageLink) : (is_dir($storageLink) ? 'directory' : 'missing'),
];

$checks['Vite manifest'] = ['ok' => file_exists(__DIR__.'/build/manifest.json'), 'detail' => __DIR__.'/build/manifest.json'];
$checks['no stray public/hot'] = ['ok' => ! file_exists(__DIR__.'/hot'), 'detail' => file_exists(__DIR__.'/hot') ? 'PRESENT (delete it)' : 'absent'];

$writable = __DIR__.'/../storage';
$checks['storage writable'] = ['ok' => is_writable($writable), 'detail' => $writable];

// --- Tail laravel.log --------------------------------------------------------
$logTail = 'no log';
$logPath = __DIR__.'/../storage/logs/laravel.log';
if (file_exists($logPath)) {
    $logTail = shell_exec("tail -n 80 ".escapeshellarg($logPath)) ?: '(empty)';
}

// --- Render ------------------------------------------------------------------
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>webTemplate debug</title>
<style>
    body{font:14px/1.4 ui-monospace,Menlo,Consolas,monospace;background:#0f172a;color:#e2e8f0;padding:20px;margin:0}
    h1,h2{color:#fff;margin:0 0 12px}
    table{border-collapse:collapse;width:100%;max-width:900px;margin-bottom:24px}
    td{padding:6px 10px;border-bottom:1px solid #1e293b;vertical-align:top}
    .ok{color:#10b981}
    .bad{color:#ef4444;font-weight:600}
    pre{background:#020617;padding:10px;border-radius:6px;overflow:auto;color:#cbd5e1;max-height:300px}
    form{display:inline-block;margin:0 6px 6px 0}
    button{background:#1e3a8a;color:#fff;border:0;padding:6px 10px;border-radius:4px;cursor:pointer}
    button:hover{background:#1e40af}
    .actions button.danger{background:#7f1d1d}
    .actions button.danger:hover{background:#991b1b}
</style>
</head>
<body>
<h1>webTemplate debug</h1>
<p>Token-gated recovery tool. Disable by removing <code>DEBUG_TOKEN</code> from <code>.env</code>.</p>

<h2>Environment</h2>
<table>
    <?php foreach ($checks as $name => $r): ?>
    <tr>
        <td><?= htmlspecialchars((string) $name) ?></td>
        <td class="<?= $r['ok'] ? 'ok' : 'bad' ?>"><?= $r['ok'] ? '✓' : '✗' ?></td>
        <td><?= htmlspecialchars((string) $r['detail']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h2>Recovery actions</h2>
<p class="actions">
<?php foreach (['optimize:clear', 'storage:link', 'migrate', 'responsecache:clear'] as $a): ?>
    <form method="post">
        <input type="hidden" name="t" value="<?= htmlspecialchars($incomingToken) ?>">
        <input type="hidden" name="action" value="<?= $a ?>">
        <input type="hidden" name="confirm" value="1">
        <button type="submit" class="<?= $a === 'migrate' ? 'danger' : '' ?>"><?= $a ?></button>
    </form>
<?php endforeach; ?>
</p>

<?php if ($actionOutput): ?>
<h2>Output (<?= htmlspecialchars((string) $action) ?>)</h2>
<pre><?= htmlspecialchars($actionOutput) ?></pre>
<?php endif; ?>

<h2>Last 80 lines of laravel.log</h2>
<pre><?= htmlspecialchars($logTail) ?></pre>
</body>
</html>
