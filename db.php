<?php
// ============================================================
//  includes/db.php — Connexion PDO centralisée
// ============================================================

define('DB_HOST', '127.0.0.1:3306');
define('DB_NAME', 'nextmux_erp');
define('DB_USER', 'root');        // À modifier
define('DB_PASS', '');            // À modifier
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME', 'Nextmux ERP');
define('APP_VERSION', '1.0');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;color:red;padding:20px;">
                Erreur de connexion MySQL : ' . htmlspecialchars($e->getMessage()) . '
                <br>Vérifiez vos paramètres dans includes/db.php
            </div>');
        }
    }
    return $pdo;
}

// Helpers utilitaires
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function formatDate(?string $date): string
{
    if (!$date) return '—';
    return (new DateTime($date))->format('d/m/Y');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function statutBadgeProjet(string $s): string
{
    $map = [
        'prospect'  => ['label' => 'Prospect',  'class' => 'badge-secondary'],
        'en_cours'  => ['label' => 'En cours',  'class' => 'badge-primary'],
        'suspendu'  => ['label' => 'Suspendu',  'class' => 'badge-warning'],
        'termine'   => ['label' => 'Terminé',   'class' => 'badge-success'],
    ];
    $b = $map[$s] ?? ['label' => $s, 'class' => 'badge-secondary'];
    return "<span class=\"badge {$b['class']}\">{$b['label']}</span>";
}

function statutBadgeFacture(string $s): string
{
    $map = [
        'brouillon'           => ['label' => 'Brouillon',       'class' => 'badge-secondary'],
        'envoyee'             => ['label' => 'Envoyée',         'class' => 'badge-primary'],
        'partiellement_payee' => ['label' => 'Part. payée',     'class' => 'badge-warning'],
        'payee'               => ['label' => 'Payée ✓',         'class' => 'badge-success'],
    ];
    $b = $map[$s] ?? ['label' => $s, 'class' => 'badge-secondary'];
    return "<span class=\"badge {$b['class']}\">{$b['label']}</span>";
}

function statutBadgeTache(string $s): string
{
    $map = [
        'a_faire'   => ['label' => 'À faire',  'class' => 'badge-secondary'],
        'en_cours'  => ['label' => 'En cours', 'class' => 'badge-primary'],
        'termine'   => ['label' => 'Terminée', 'class' => 'badge-success'],
    ];
    $b = $map[$s] ?? ['label' => $s, 'class' => 'badge-secondary'];
    return "<span class=\"badge {$b['class']}\">{$b['label']}</span>";
}

function prioriteBadge(string $s): string
{
    $map = [
        'basse'    => ['label' => '↓ Basse',    'class' => 'badge-light'],
        'normale'  => ['label' => '→ Normale',  'class' => 'badge-info'],
        'haute'    => ['label' => '↑ Haute',    'class' => 'badge-warning'],
        'critique' => ['label' => '‼ Critique', 'class' => 'badge-danger'],
    ];
    $b = $map[$s] ?? ['label' => $s, 'class' => 'badge-secondary'];
    return "<span class=\"badge {$b['class']}\">{$b['label']}</span>";
}

session_start();

// ── Authentification ──────────────────────────────────────────
function isLoggedIn(): bool
{
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        $current = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: login.php' . ($current ? '?redirect=' . $current : ''));
        exit;
    }
}
