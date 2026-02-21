<?php
require_once 'db.php';
requireLogin();
$currentPage = 'taches';
$db = getDB();

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// DELETE
if ($action === 'delete' && $id) {
  $db->prepare("DELETE FROM taches WHERE id=?")->execute([$id]);
  flash('success', 'Tâche supprimée.');
  redirect('taches.php');
}

// QUICK STATUS UPDATE (AJAX-style GET)
if ($action === 'status' && $id) {
  $newStatut = $_GET['s'] ?? 'a_faire';
  $db->prepare("UPDATE taches SET statut=? WHERE id=?")->execute([$newStatut, $id]);
  flash('success', 'Statut mis à jour.');
  redirect('taches.php');
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'projet_id'     => (int)($_POST['projet_id'] ?? 0),
    'titre'         => trim($_POST['titre'] ?? ''),
    'description'   => trim($_POST['description'] ?? ''),
    'statut'        => $_POST['statut'] ?? 'a_faire',
    'priorite'      => $_POST['priorite'] ?? 'normale',
    'date_echeance' => $_POST['date_echeance'] ?: null,
  ];
  $errors = [];
  if (!$data['projet_id']) $errors[] = 'Projet obligatoire.';
  if (!$data['titre'])     $errors[] = 'Titre obligatoire.';

  if (!$errors) {
    if ($id) {
      $db->prepare("UPDATE taches SET projet_id=?,titre=?,description=?,statut=?,priorite=?,date_echeance=? WHERE id=?")
        ->execute([...array_values($data), $id]);
      flash('success', 'Tâche mise à jour.');
    } else {
      $db->prepare("INSERT INTO taches (projet_id,titre,description,statut,priorite,date_echeance) VALUES(?,?,?,?,?,?)")
        ->execute(array_values($data));
      flash('success', 'Tâche créée.');
    }
    redirect('taches.php');
  }
}

// LOAD
$tache = [];
if ($action === 'edit' && $id) {
  $stmt = $db->prepare("SELECT * FROM taches WHERE id=?");
  $stmt->execute([$id]);
  $tache = $stmt->fetch();
}

// LIST
$filtre_projet  = $_GET['projet_id'] ?? '';
$filtre_statut  = $_GET['statut'] ?? '';
$filtre_priorite = $_GET['priorite'] ?? '';

$taches = $db->prepare("
  SELECT t.*, p.titre AS projet_titre, c.nom AS client_nom
  FROM taches t
  JOIN projets p ON p.id = t.projet_id
  JOIN clients c ON c.id = p.client_id
  WHERE (? = '' OR t.projet_id = ?)
    AND (? = '' OR t.statut = ?)
    AND (? = '' OR t.priorite = ?)
  ORDER BY FIELD(t.priorite,'critique','haute','normale','basse'), t.date_echeance
");
$taches->execute([$filtre_projet, $filtre_projet, $filtre_statut, $filtre_statut, $filtre_priorite, $filtre_priorite]);
$taches = $taches->fetchAll();

$allProjets = $db->query("SELECT p.id, p.titre, c.nom AS client_nom FROM projets p JOIN clients c ON c.id=p.client_id ORDER BY c.nom, p.titre")->fetchAll();

$pageTitle = 'Tâches — Nextmux ERP';
require_once 'header.php';
?>

<?php if ($action === 'list'): ?>
  <div class="flex gap-3 mb-4" style="justify-content:space-between;align-items:center;">
    <form method="GET" class="flex gap-2" style="flex-wrap:wrap;">
      <select name="projet_id" style="width:200px;">
        <option value="">Tous les projets</option>
        <?php foreach ($allProjets as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $filtre_projet == $p['id'] ? 'selected' : '' ?>><?= h($p['titre']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="statut" style="width:130px;">
        <option value="">Tous statuts</option>
        <option value="a_faire" <?= $filtre_statut === 'a_faire'  ? 'selected' : '' ?>>À faire</option>
        <option value="en_cours" <?= $filtre_statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
        <option value="termine" <?= $filtre_statut === 'termine'  ? 'selected' : '' ?>>Terminée</option>
      </select>
      <select name="priorite" style="width:130px;">
        <option value="">Toutes priorités</option>
        <option value="critique" <?= $filtre_priorite === 'critique' ? 'selected' : '' ?>>Critique</option>
        <option value="haute" <?= $filtre_priorite === 'haute'    ? 'selected' : '' ?>>Haute</option>
        <option value="normale" <?= $filtre_priorite === 'normale'  ? 'selected' : '' ?>>Normale</option>
        <option value="basse" <?= $filtre_priorite === 'basse'    ? 'selected' : '' ?>>Basse</option>
      </select>
      <button type="submit" class="btn btn-ghost" title="Rechercher"><i class="fa-solid fa-search"></i></button>
      <a href="taches.php" class="btn btn-ghost"><i class="fa-solid fa-redo"></i> Réinitialiser</a>
    </form>
    <a href="taches.php?action=create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle tâche</a>
  </div>

  <!-- Compteurs rapides -->
  <?php
  $stats = ['a_faire' => 0, 'en_cours' => 0, 'termine' => 0];
  foreach ($taches as $t) $stats[$t['statut']]++;
  ?>
  <div class="stat-grid mb-4" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
      <div class="stat-label">À faire</div>
      <div class="stat-value"><?= $stats['a_faire'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">En cours</div>
      <div class="stat-value blue"><?= $stats['en_cours'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Terminées</div>
      <div class="stat-value green"><?= $stats['termine'] ?></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-check-circle"></i> Tâches (<?= count($taches) ?>)</span></div>
    <?php if ($taches): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Tâche</th>
              <th>Projet</th>
              <th>Statut</th>
              <th>Échéance</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($taches as $t):
              $retard = $t['date_echeance'] && $t['date_echeance'] < date('Y-m-d') && $t['statut'] !== 'termine';
            ?>
              <tr>
                <td>
                  <?= h($t['titre']) ?>
                  <?php if ($retard): ?><span class="badge badge-danger" style="margin-left:6px">En retard</span><?php endif; ?>
                </td>
                <td>
                  <a href="projets.php?action=view&id=<?= $t['projet_id'] ?>" style="color:var(--accent);text-decoration:none;">
                    <?= h($t['projet_titre']) ?>
                  </a>
                  <div class="text-muted" style="font-size:0.72rem"><?= h($t['client_nom']) ?></div>
                </td>
                <td><?= statutBadgeTache($t['statut']) ?></td>
                <td class="text-muted <?= $retard ? 'red' : '' ?>"><?= formatDate($t['date_echeance']) ?></td>
                <td>
                  <div class="flex gap-2">
                    <!-- Changement rapide de statut -->
                    <?php if ($t['statut'] !== 'termine'): ?>
                      <a href="taches.php?action=status&id=<?= $t['id'] ?>&s=termine" class="btn btn-success btn-sm" title="Marquer terminée"><i class="fa-solid fa-check"></i></a>
                    <?php endif; ?>
                    <?php if ($t['statut'] === 'a_faire'): ?>
                      <a href="taches.php?action=status&id=<?= $t['id'] ?>&s=en_cours" class="btn btn-ghost btn-sm" title="Démarrer"><i class="fa-solid fa-play"></i></a>
                    <?php endif; ?>
                    <a href="taches.php?action=edit&id=<?= $t['id'] ?>" class="btn btn-ghost btn-sm" title="Modifier"><i class="fa-solid fa-edit"></i></a>
                    <a href="taches.php?action=delete&id=<?= $t['id'] ?>" class="btn btn-ghost btn-sm" title="Supprimer"
                      onclick="return confirm('Supprimer cette tâche ?')"><i class="fa-solid fa-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="icon"><i class="fa-solid fa-check-circle"></i></div>
        <p>Aucune tâche trouvée.</p>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- FORM -->
  <a href="taches.php" class="btn btn-ghost btn-sm mb-4"><i class="fa-solid fa-arrow-left"></i> Retour</a>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('h', $errors)) ?></div>
  <?php endif; ?>

  <div class="card" style="max-width:600px;">
    <div class="card-header"><span class="card-title"><?= $id ? 'Modifier la tâche' : 'Nouvelle tâche' ?></span></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label>Projet *</label>
          <select name="projet_id" required>
            <option value="">-- Choisir un projet --</option>
            <?php foreach ($allProjets as $p): ?>
              <option value="<?= $p['id'] ?>" <?= (($tache['projet_id'] ?? $_GET['projet_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                <?= h($p['client_nom']) ?> — <?= h($p['titre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Titre *</label>
          <input type="text" name="titre" value="<?= h($tache['titre'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description"><?= h($tache['description'] ?? '') ?></textarea>
        </div>
        <div class="grid-3">
          <div class="form-group">
            <label>Statut</label>
            <select name="statut">
              <?php foreach (['a_faire', 'en_cours', 'termine'] as $s): ?>
                <option value="<?= $s ?>" <?= ($tache['statut'] ?? 'a_faire') === $s ? 'selected' : '' ?>>
                  <?= str_replace('_', ' ', $s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Priorité</label>
            <select name="priorite">
              <?php foreach (['basse', 'normale', 'haute', 'critique'] as $s): ?>
                <option value="<?= $s ?>" <?= ($tache['priorite'] ?? 'normale') === $s ? 'selected' : '' ?>>
                  <?= ucfirst($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Échéance</label>
            <input type="date" name="date_echeance" value="<?= h($tache['date_echeance'] ?? '') ?>">
          </div>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
          <a href="taches.php" class="btn btn-ghost">Annuler</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>