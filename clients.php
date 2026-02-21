<?php
require_once 'db.php';
requireLogin();
$currentPage = 'clients';
$db = getDB();

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ---- DELETE ----
if ($action === 'delete' && $id) {
  try {
    $db->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
    flash('success', 'Client supprimé avec succès.');
  } catch (PDOException $e) {
    flash('error', 'Impossible de supprimer ce client (projets associés).');
  }
  redirect('clients.php');
}

// ---- SAVE (create/edit) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'nom'       => trim($_POST['nom'] ?? ''),
    'email'     => trim($_POST['email'] ?? ''),
    'telephone' => trim($_POST['telephone'] ?? ''),
    'adresse'   => trim($_POST['adresse'] ?? ''),
    'ville'     => trim($_POST['ville'] ?? ''),
  ];
  $errors = [];
  if (!$data['nom'])   $errors[] = 'Le nom est obligatoire.';
  if (!$data['email']) $errors[] = 'L\'email est obligatoire.';

  if (!$errors) {
    if ($id) {
      $db->prepare("UPDATE clients SET nom=?, email=?, telephone=?, adresse=?, ville=? WHERE id=?")
        ->execute([...array_values($data), $id]);
      flash('success', 'Client mis à jour.');
    } else {
      $db->prepare("INSERT INTO clients (nom, email, telephone, adresse, ville) VALUES (?,?,?,?,?)")
        ->execute(array_values($data));
      flash('success', 'Client créé avec succès.');
    }
    redirect('clients.php');
  }
}

// ---- LOAD for edit ----
$client = [];
if (($action === 'edit' || $action === 'view') && $id) {
  $client = $db->prepare("SELECT * FROM clients WHERE id = ?")->execute([$id]) ? $db->prepare("SELECT * FROM clients WHERE id = ?")->execute([$id]) : [];
  $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
  $stmt->execute([$id]);
  $client = $stmt->fetch();
}

// ---- LIST ----
$search   = $_GET['q'] ?? '';
$clients  = $db->prepare("
  SELECT c.*, COUNT(p.id) AS nb_projets
  FROM clients c
  LEFT JOIN projets p ON p.client_id = c.id
  WHERE c.nom LIKE ? OR c.email LIKE ? OR c.ville LIKE ?
  GROUP BY c.id ORDER BY c.nom
");
$clients->execute(["%$search%", "%$search%", "%$search%"]);
$clients = $clients->fetchAll();

// ---- PROJETS DU CLIENT (vue détail) ----
$projetsClient = [];
if ($action === 'view' && $id) {
  $stmt = $db->prepare("SELECT p.*, (SELECT SUM(montant_ttc) FROM factures f WHERE f.projet_id=p.id) AS total_facture FROM projets p WHERE p.client_id = ? ORDER BY p.created_at DESC");
  $stmt->execute([$id]);
  $projetsClient = $stmt->fetchAll();
}

$pageTitle = 'Clients — Nextmux ERP';
if ($action === 'create') $pageTitle = 'Nouveau client';
if ($action === 'edit')   $pageTitle = 'Modifier client';
require_once 'header.php';
?>

<?php if ($action === 'list'): ?>
  <!-- ===== LIST ===== -->
  <div class="flex gap-3 mb-4" style="justify-content:space-between; align-items:center;">
    <form method="GET" class="flex gap-2">
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Rechercher un client…" style="width:260px;">
      <button type="submit" class="btn btn-ghost" title="Rechercher"><i class="fa-solid fa-search"></i></button>
    </form>
    <a href="clients.php?action=create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouveau client</a>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fa-solid fa-users"></i> Clients (<?= count($clients) ?>)</span>
    </div>
    <?php if ($clients): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Téléphone</th>
              <th>Ville</th>
              <th>Projets</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clients as $c): ?>
              <tr>
                <td><a href="clients.php?action=view&id=<?= $c['id'] ?>" style="color:var(--accent);text-decoration:none;font-weight:500"><?= h($c['nom']) ?></a></td>
                <td class="text-muted"><?= h($c['email']) ?></td>
                <td class="text-muted mono"><?= h($c['telephone'] ?? '—') ?></td>
                <td class="text-muted"><?= h($c['ville'] ?? '—') ?></td>
                <td><span class="badge badge-secondary"><?= $c['nb_projets'] ?></span></td>
                <td>
                  <div class="flex gap-2">
                    <a href="clients.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm" title="Modifier"><i class="fa-solid fa-edit"></i></a>
                    <a href="clients.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm" title="Supprimer"
                      onclick="return confirm('Supprimer <?= h(addslashes($c['nom'])) ?> ?')"><i class="fa-solid fa-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="icon"><i class="fa-solid fa-users"></i></div>
        <p>Aucun client trouvé.</p>
      </div>
    <?php endif; ?>
  </div>

<?php elseif ($action === 'view' && $client): ?>
  <!-- ===== VIEW ===== -->
  <div class="flex gap-2 mb-4">
    <a href="clients.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> Retour</a>
    <a href="clients.php?action=edit&id=<?= $client['id'] ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-edit"></i> Modifier</a>
    <a href="projets.php?action=create&client_id=<?= $client['id'] ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-plus"></i> Nouveau projet</a>
  </div>

  <div class="grid-2 mb-6">
    <div class="card">
      <div class="card-header"><span class="card-title">Informations client</span></div>
      <div class="card-body">
        <table style="font-size:0.875rem;">
          <tr>
            <td style="color:var(--muted);width:120px;padding:6px 0">Nom</td>
            <td style="font-weight:600"><?= h($client['nom']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:6px 0">Email</td>
            <td><?= h($client['email']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:6px 0">Téléphone</td>
            <td><?= h($client['telephone'] ?? '—') ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:6px 0">Ville</td>
            <td><?= h($client['ville'] ?? '—') ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:6px 0">Adresse</td>
            <td><?= h($client['adresse'] ?? '—') ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:6px 0">Depuis</td>
            <td><?= formatDate($client['created_at']) ?></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Projets de <?= h($client['nom']) ?></span>
      <a href="projets.php?action=create&client_id=<?= $client['id'] ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Projet</a>
    </div>
    <?php if ($projetsClient): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Titre</th>
              <th>Budget</th>
              <th>Facturé TTC</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($projetsClient as $p): ?>
              <tr>
                <td><a href="projets.php?action=view&id=<?= $p['id'] ?>" style="color:var(--accent);text-decoration:none"><?= h($p['titre']) ?></a></td>
                <td class="mono"><?= money($p['budget']) ?></td>
                <td class="mono"><?= money($p['total_facture'] ?? 0) ?></td>
                <td><?= statutBadgeProjet($p['statut']) ?></td>
                <td><a href="projets.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" title="Modifier"><i class="fa-solid fa-edit"></i></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="icon"><i class="fa-solid fa-folder-open"></i></div>
        <p>Aucun projet pour ce client.</p>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- ===== FORM CREATE / EDIT ===== -->
  <a href="clients.php" class="btn btn-ghost btn-sm mb-4"><i class="fa-solid fa-arrow-left"></i> Retour</a>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('h', $errors)) ?></div>
  <?php endif; ?>

  <div class="card" style="max-width:600px;">
    <div class="card-header">
      <span class="card-title"><?= $id ? 'Modifier le client' : 'Nouveau client' ?></span>
    </div>
    <div class="card-body">
      <form method="POST" action="clients.php<?= $id ? "?action=edit&id=$id" : '?action=create' ?>">
        <div class="form-group">
          <label>Nom / Raison sociale *</label>
          <input type="text" name="nom" value="<?= h($client['nom'] ?? ($_POST['nom'] ?? '')) ?>" required>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" value="<?= h($client['email'] ?? ($_POST['email'] ?? '')) ?>" required>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Téléphone</label>
            <input type="text" name="telephone" value="<?= h($client['telephone'] ?? ($_POST['telephone'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label>Ville</label>
            <input type="text" name="ville" value="<?= h($client['ville'] ?? ($_POST['ville'] ?? '')) ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Adresse</label>
          <textarea name="adresse"><?= h($client['adresse'] ?? ($_POST['adresse'] ?? '')) ?></textarea>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
          <a href="clients.php" class="btn btn-ghost">Annuler</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>