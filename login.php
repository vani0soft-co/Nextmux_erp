<?php
require_once 'db.php';

// Si déjà connecté → aller au dashboard
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';

    if ($email === '' || $mdp === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, nom, email, mdp FROM utilisateurs WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // ══ DEBUG TEMPORAIRE ══════════════════════════════════
        if (isset($_GET['debug'])) {
            echo '<pre style="background:#111;color:#0f0;padding:20px;font-size:12px;position:fixed;top:0;left:0;right:0;z-index:9999;max-height:50vh;overflow:auto">';
            echo "Email saisi    : " . htmlspecialchars($email) . "\n";
            echo "Utilisateur BDD: " . ($user ? 'TROUVÉ (id=' . $user['id'] . ', nom=' . $user['nom'] . ')' : 'INTROUVABLE') . "\n";
            if ($user) {
                echo "Longueur hash  : " . strlen($user['mdp']) . " chars\n";
                echo "Hash début     : " . htmlspecialchars(substr($user['mdp'], 0, 10)) . "...\n";
                echo "password_verify: " . (password_verify($mdp, $user['mdp']) ? 'TRUE ✓' : 'FALSE ✗') . "\n";
            }
            echo '</pre>';
        }
        // ══ FIN DEBUG ═════════════════════════════════════════

        if ($user && password_verify($mdp, $user['mdp'])) {
            // Connexion réussie
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'nom'   => $user['nom'],
                'email' => $user['email'],
            ];
            $redirect = $_GET['redirect'] ?? 'index.php';
            // Sécurité : n'autoriser que les redirections relatives
            if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+\.php/', $redirect)) {
                $redirect = 'index.php';
            }
            redirect($redirect);
        } else {
            $error = 'Identifiants incorrects. Vérifiez votre email et mot de passe.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — <?= APP_NAME ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root {
      --bg:      #0d1117;
      --bg2:     #161b22;
      --bg3:     #21262d;
      --border:  #30363d;
      --text:    #e6edf3;
      --muted:   #8b949e;
      --accent:  #3b82f6;
      --accent-h:#2563eb;
      --green:   #3fb950;
      --red:     #f85149;
      --radius:  8px;
      --shadow:  0 4px 24px rgba(0,0,0,.4);
    }
    :root[data-theme="light"] {
      --bg:      #ffffff;
      --bg2:     #f6f8fa;
      --bg3:     #eaeef2;
      --border:  #d0d7de;
      --text:    #24292f;
      --muted:   #57606a;
      --accent:  #0969da;
      --accent-h:#033d8b;
      --red:     #cf222e;
      --shadow:  0 4px 24px rgba(0,0,0,.1);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      font-size: 14px;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background-image:
        radial-gradient(ellipse 80% 50% at 20% 30%, rgba(59,130,246,.08) 0%, transparent 60%),
        radial-gradient(ellipse 60% 40% at 80% 70%, rgba(163,113,247,.06) 0%, transparent 55%);
    }

    /* ── Carte login ── */
    .login-card {
      width: 100%;
      max-width: 420px;
      background: var(--bg2);
      border: 1px solid var(--border);
      border-radius: 14px;
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    /* ── En-tête ── */
    .login-header {
      padding: 32px 32px 24px;
      border-bottom: 1px solid var(--border);
      text-align: center;
      background: var(--bg3);
    }

    .login-logo {
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 2px;
      font-size: 1.8rem;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .login-logo img {
      height: 1.2em;
      width: auto;
      object-fit: contain;
      display: inline-block;
      vertical-align: middle;
    }

    .login-logo .logo-text span { color: var(--accent); }

    .login-header p {
      font-size: 0.82rem;
      color: var(--muted);
    }

    /* ── Corps formulaire ── */
    .login-body { padding: 28px 32px 32px; }

    .form-group { margin-bottom: 18px; }

    label {
      display: block;
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 7px;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: .9rem;
      pointer-events: none;
    }

    input[type="email"],
    input[type="password"],
    input[type="text"] {
      width: 100%;
      background: var(--bg3);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      padding: 10px 12px 10px 38px;
      font-size: .875rem;
      font-family: inherit;
      transition: border-color .15s, box-shadow .15s;
      outline: none;
    }

    input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(59,130,246,.15);
    }

    /* Toggle visibilité mot de passe */
    .toggle-pwd {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--muted);
      cursor: pointer;
      padding: 4px;
      font-size: .9rem;
      transition: color .15s;
    }
    .toggle-pwd:hover { color: var(--text); }

    /* Message d'erreur */
    .alert-error {
      background: rgba(248,81,73,.1);
      border: 1px solid rgba(248,81,73,.3);
      color: var(--red);
      border-radius: var(--radius);
      padding: 10px 14px;
      font-size: .8rem;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Bouton de connexion */
    .btn-login {
      width: 100%;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: var(--radius);
      padding: 11px;
      font-size: .9rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      transition: background .15s, transform .1s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 4px;
    }

    .btn-login:hover  { background: var(--accent-h); }
    .btn-login:active { transform: scale(.98); }

    /* Footer de la carte */
    .login-footer {
      padding: 14px 32px;
      border-top: 1px solid var(--border);
      text-align: center;
      font-size: .75rem;
      color: var(--muted);
    }

    /* Bouton thème */
    .theme-btn {
      position: fixed;
      top: 16px;
      right: 16px;
      background: var(--bg2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1rem;
      transition: background .15s;
    }
    .theme-btn:hover { background: var(--bg3); }

    /* Responsive */
    @media (max-width: 479px) {
      body { padding: 16px; align-items: flex-start; padding-top: 48px; }
      .login-header { padding: 24px 20px 18px; }
      .login-body   { padding: 20px 20px 24px; }
      .login-footer { padding: 12px 20px; }
    }
  </style>
</head>
<body>

  <button class="theme-btn" id="themeBtn" title="Changer de thème">🌙</button>

  <div class="login-card">
    <div class="login-header">
      <div class="login-logo">
        <img src="img/logo_nextmux.png" alt="N">ext<span>mux</span>
      </div>
      <p>Connectez-vous pour accéder à l'application</p>
    </div>

    <div class="login-body">
      <?php if ($error): ?>
        <div class="alert-error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <div class="form-group">
          <label for="email">Adresse e-mail</label>
          <div class="input-wrap">
            <i class="fa-solid fa-envelope"></i>
            <input
              type="email"
              id="email"
              name="email"
              value="<?= h($_POST['email'] ?? '') ?>"
              placeholder="votre@email.com"
              autocomplete="email"
              required
              autofocus
            >
          </div>
        </div>

        <div class="form-group">
          <label for="mdp">Mot de passe</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock"></i>
            <input
              type="password"
              id="mdp"
              name="mdp"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            >
            <button type="button" class="toggle-pwd" id="togglePwd" title="Afficher/masquer le mot de passe">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login">
          <i class="fa-solid fa-right-to-bracket"></i>
          Se connecter
        </button>
      </form>
    </div>

    <div class="login-footer">
      <?= APP_NAME ?> v<?= APP_VERSION ?> &mdash; Accès réservé
    </div>
  </div>

  <script>
    // ── Thème ──
    const root     = document.documentElement;
    const themeBtn = document.getElementById('themeBtn');
    const saved    = localStorage.getItem('theme') || 'dark';

    const applyTheme = t => {
      root.setAttribute('data-theme', t);
      themeBtn.textContent = t === 'dark' ? '☀️' : '🌙';
      localStorage.setItem('theme', t);
    };

    applyTheme(saved);
    themeBtn.addEventListener('click', () => {
      applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });

    // ── Toggle visibilité mot de passe ──
    const togglePwd = document.getElementById('togglePwd');
    const mdpInput  = document.getElementById('mdp');
    const eyeIcon   = document.getElementById('eyeIcon');

    togglePwd.addEventListener('click', () => {
      const visible = mdpInput.type === 'text';
      mdpInput.type = visible ? 'password' : 'text';
      eyeIcon.className = visible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    });
  </script>
</body>
</html>
