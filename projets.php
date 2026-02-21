<?php
require_once 'db.php';
requireLogin();
$currentPage = 'projets';
$db = getDB();

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// DELETE
if ($action === 'delete' && $id) {
  $db->prepare("DELETE FROM projets WHERE id = ?")->execute([$id]);
  flash('success', 'Projet supprimé.');
  redirect('projets.php');
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'client_id'   => (int)($_POST['client_id'] ?? 0),
    'titre'       => trim($_POST['titre'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'budget'      => (float)str_replace(',', '.', $_POST['budget'] ?? '0'),
    'statut'      => $_POST['statut'] ?? 'en_cours',
    'date_debut'  => $_POST['date_debut'] ?: null,
    'date_fin'    => $_POST['date_fin'] ?: null,
  ];
  $errors = [];
  if (!$data['client_id']) $errors[] = 'Client obligatoire.';
  if (!$data['titre'])     $errors[] = 'Titre obligatoire.';

  if (!$errors) {
    if ($id) {
      $db->prepare("UPDATE projets SET client_id=?,titre=?,description=?,budget=?,statut=?,date_debut=?,date_fin=? WHERE id=?")
        ->execute([...array_values($data), $id]);
      flash('success', 'Projet mis à jour.');
    } else {
      $db->prepare("INSERT INTO projets (client_id,titre,description,budget,statut,date_debut,date_fin) VALUES(?,?,?,?,?,?,?)")
        ->execute(array_values($data));
      flash('success', 'Projet créé.');
    }
    redirect('projets.php');
  }
}

// LOAD
$projet = [];
if (in_array($action, ['edit', 'view']) && $id) {
  $stmt = $db->prepare("SELECT p.*, c.nom AS client_nom FROM projets p JOIN clients c ON c.id=p.client_id WHERE p.id=?");
  $stmt->execute([$id]);
  $projet = $stmt->fetch();
}

// Tâches + Finance pour vue détail
$taches = $factures = $depenses = [];
$finProjet = null;
if ($action === 'view' && $id) {
  $taches  = $db->prepare("SELECT * FROM taches WHERE projet_id=? ORDER BY FIELD(priorite,'critique','haute','normale','basse'), date_echeance")->execute([$id]) ? [] : [];
  $s = $db->prepare("SELECT * FROM taches WHERE projet_id=? ORDER BY FIELD(priorite,'critique','haute','normale','basse'), date_echeance");
  $s->execute([$id]);
  $taches = $s->fetchAll();
  $s = $db->prepare("SELECT f.*, COALESCE(SUM(p.montant),0) AS paye FROM factures f LEFT JOIN paiements p ON p.facture_id=f.id WHERE f.projet_id=? GROUP BY f.id ORDER BY f.date_emission DESC");
  $s->execute([$id]);
  $factures = $s->fetchAll();
  $s = $db->prepare("SELECT * FROM depenses WHERE projet_id=? ORDER BY date DESC");
  $s->execute([$id]);
  $depenses = $s->fetchAll();
  $s = $db->prepare("SELECT * FROM v_finance_projets WHERE projet_id=?");
  $s->execute([$id]);
  $finProjet = $s->fetch();
}

// Liste
$search  = $_GET['q'] ?? '';
$statut  = $_GET['statut'] ?? '';
$projets = $db->prepare("
  SELECT p.*, c.nom AS client_nom,
    COUNT(DISTINCT t.id) AS nb_taches,
    SUM(t.statut='termine') AS nb_terminees
  FROM projets p
  JOIN clients c ON c.id=p.client_id
  LEFT JOIN taches t ON t.projet_id=p.id
  WHERE (p.titre LIKE ? OR c.nom LIKE ?)
    AND (? = '' OR p.statut = ?)
  GROUP BY p.id ORDER BY p.created_at DESC
");
$projets->execute(["%$search%", "%$search%", $statut, $statut]);
$projets = $projets->fetchAll();

// All clients for select
$allClients = $db->query("SELECT id, nom FROM clients ORDER BY nom")->fetchAll();

$pageTitle = 'Projets — Nextmux ERP';
require_once 'header.php';
?>

<?php if ($action === 'list'): ?>
  <div class="flex gap-3 mb-4" style="justify-content:space-between;align-items:center;">
    <form method="GET" class="flex gap-2">
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Rechercher…" style="width:200px;">
      <select name="statut" style="width:140px;">
        <option value="">Tous statuts</option>
        <option value="prospect" <?= $statut === 'prospect'  ? 'selected' : '' ?>>Prospect</option>
        <option value="en_cours" <?= $statut === 'en_cours'  ? 'selected' : '' ?>>En cours</option>
        <option value="suspendu" <?= $statut === 'suspendu'  ? 'selected' : '' ?>>Suspendu</option>
        <option value="termine" <?= $statut === 'termine'   ? 'selected' : '' ?>>Terminé</option>
      </select>
      <button type="submit" class="btn btn-ghost" title="Rechercher"><i class="fa-solid fa-search"></i></button>
    </form>
    <a href="projets.php?action=create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouveau projet</a>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-folder"></i> Projets (<?= count($projets) ?>)</span></div>
    <?php if ($projets): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Titre</th>
              <th>Client</th>
              <th>Budget</th>
              <th>Statut</th>
              <th>Fin prévue</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($projets as $p):
              $pct = $p['nb_taches'] > 0 ? round(($p['nb_terminees'] / $p['nb_taches']) * 100) : 0;
            ?>
              <tr>
                <td><a href="projets.php?action=view&id=<?= $p['id'] ?>" style="color:var(--accent);text-decoration:none;font-weight:500"><?= h($p['titre']) ?></a></td>
                <td class="text-muted"><?= h($p['client_nom']) ?></td>
                <td class="mono"><?= money($p['budget']) ?></td>
                <td><?= statutBadgeProjet($p['statut']) ?></td>
                <td class="text-muted"><?= formatDate($p['date_fin']) ?></td>
                <td>
                  <div class="flex gap-2">
                    <a href="projets.php?action=view&id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" title="Voir"><i class="fa-solid fa-eye"></i></a>
                    <a href="projets.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" title="Modifier"><i class="fa-solid fa-edit"></i></a>
                    <a href="projets.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" title="Supprimer"
                      onclick="return confirm('Supprimer ce projet ?')"><i class="fa-solid fa-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="icon"><i class="fa-solid fa-folder-open"></i></div>
        <p>Aucun projet.</p>
      </div>
    <?php endif; ?>
  </div>

<?php elseif ($action === 'view' && $projet): ?>
  <!-- DETAIL VIEW -->
  <div class="flex gap-2 mb-4">
    <a href="projets.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> Retour</a>
    <a href="projets.php?action=edit&id=<?= $id ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-edit"></i> Modifier</a>
    <a href="taches.php?action=create&projet_id=<?= $id ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-plus"></i> Tâche</a>
    <a href="factures.php?action=create&projet_id=<?= $id ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-plus"></i> Facture</a>
  </div>

  <?php if ($finProjet): ?>
    <div class="stat-grid mb-6" style="grid-template-columns:repeat(4,1fr);">
      <div class="stat-card">
        <div class="stat-label">Budget</div>
        <div class="stat-value"><?= money($finProjet['budget']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Facturé TTC</div>
        <div class="stat-value blue"><?= money($finProjet['total_facture']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Encaissé</div>
        <div class="stat-value green"><?= money($finProjet['total_encaisse']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Dépenses</div>
        <div class="stat-value red"><?= money($finProjet['total_depenses']) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <div class="grid-2 mb-4">
    <div class="card">
      <div class="card-header"><span class="card-title">Informations projet</span></div>
      <div class="card-body">
        <table style="font-size:0.875rem;">
          <tr>
            <td style="color:var(--muted);width:100px;padding:5px 0">Titre</td>
            <td style="font-weight:600"><?= h($projet['titre']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:5px 0">Client</td>
            <td><?= h($projet['client_nom']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:5px 0">Statut</td>
            <td><?= statutBadgeProjet($projet['statut']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:5px 0">Début</td>
            <td><?= formatDate($projet['date_debut']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:5px 0">Fin prévue</td>
            <td><?= formatDate($projet['date_fin']) ?></td>
          </tr>
          <?php if ($projet['description']): ?>
            <tr>
              <td style="color:var(--muted);padding:5px 0">Description</td>
              <td><?= nl2br(h($projet['description'])) ?></td>
            </tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Tâches résumé -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-check-circle"></i> Tâches (<?= count($taches) ?>)</span>
        <a href="taches.php?action=create&projet_id=<?= $id ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-plus"></i> Tâche</a>
      </div>
      <?php if ($taches): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Titre</th>
                <th>Priorité</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($taches as $t): ?>
                <tr>
                  <td><?= h($t['titre']) ?></td>
                  <td><?= prioriteBadge($t['priorite']) ?></td>
                  <td><?= statutBadgeTache($t['statut']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="icon"><i class="fa-solid fa-check-circle"></i></div>
          <p>Aucune tâche.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Factures -->
  <div class="card mb-4">
    <div class="card-header">
      <span class="card-title"><i class="fa-solid fa-receipt"></i> Factures</span>
      <a href="factures.php?action=create&projet_id=<?= $id ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-plus"></i> Facture</a>
    </div>
    <?php if ($factures): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Numéro</th>
              <th>Montant HT</th>
              <th>TTC</th>
              <th>Payé</th>
              <th>Reste</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($factures as $f): ?>
              <tr>
                <td class="mono"><?= h($f['numero']) ?></td>
                <td class="mono"><?= money($f['montant_ht']) ?></td>
                <td class="mono"><?= money($f['montant_ttc']) ?></td>
                <td class="mono green"><?= money($f['paye']) ?></td>
                <td class="mono <?= ($f['montant_ttc'] - $f['paye']) > 0 ? 'red' : '' ?>"><?= money($f['montant_ttc'] - $f['paye']) ?></td>
                <td><?= statutBadgeFacture($f['statut']) ?></td>
                <td><a href="factures.php?action=view&id=<?= $f['id'] ?>" class="btn btn-ghost btn-sm" title="Voir"><i class="fa-solid fa-eye"></i></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="icon"><i class="fa-solid fa-receipt"></i></div>
        <p>Aucune facture pour ce projet.</p>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- FORM -->
  <a href="projets.php" class="btn btn-ghost btn-sm mb-4"><i class="fa-solid fa-arrow-left"></i> Retour</a>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('h', $errors)) ?></div>
  <?php endif; ?>

  <div class="card" style="max-width:680px;">
    <div class="card-header"><span class="card-title"><?= $id ? 'Modifier le projet' : 'Nouveau projet' ?></span></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label>Client *</label>
          <select name="client_id" required>
            <option value="">-- Choisir un client --</option>
            <?php foreach ($allClients as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (($projet['client_id'] ?? $_GET['client_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                <?= h($c['nom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Titre du projet *</label>
          <input type="text" name="titre" value="<?= h($projet['titre'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description"><?= h($projet['description'] ?? '') ?></textarea>
        </div>
        <div class="grid-3">
          <div class="form-group">
            <label>Budget (FCFA HT)</label>
            <input type="number" step="0.01" name="budget" value="<?= h($projet['budget'] ?? '0') ?>">
          </div>
          <div class="form-group">
            <label>Date début</label>
            <input type="date" name="date_debut" value="<?= h($projet['date_debut'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Date fin prévue</label>
            <input type="date" name="date_fin" value="<?= h($projet['date_fin'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Statut</label>
          <select name="statut">
            <?php foreach (['prospect', 'en_cours', 'suspendu', 'termine'] as $s): ?>
              <option value="<?= $s ?>" <?= ($projet['statut'] ?? 'en_cours') === $s ? 'selected' : '' ?>>
                <?= ucfirst(str_replace('_', ' ', $s)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
          <a href="projets.php" class="btn btn-ghost">Annuler</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>