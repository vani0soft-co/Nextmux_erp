<?php
require_once 'db.php';
requireLogin();
$currentPage = 'depenses';
$db = getDB();

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'delete' && $id) {
  $db->prepare("DELETE FROM depenses WHERE id=?")->execute([$id]);
  flash('success', 'Dépense supprimée.');
  redirect('depenses.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'projet_id'    => (int)($_POST['projet_id'] ?? 0) ?: null,
    'libelle'      => trim($_POST['libelle'] ?? ''),
    'montant'      => (float)str_replace(',', '.', $_POST['montant'] ?? '0'),
    'categorie'    => $_POST['categorie'] ?? 'autre',
    'date'         => $_POST['date'] ?: date('Y-m-d'),
    'justificatif' => trim($_POST['justificatif'] ?? ''),
  ];
  $errors = [];
  if (!$data['libelle'])      $errors[] = 'Libellé obligatoire.';
  if ($data['montant'] <= 0)  $errors[] = 'Montant invalide.';

  if (!$errors) {
    if ($id) {
      $db->prepare("UPDATE depenses SET projet_id=?,libelle=?,montant=?,categorie=?,date=?,justificatif=? WHERE id=?")
        ->execute([...array_values($data), $id]);
      flash('success', 'Dépense mise à jour.');
    } else {
      $db->prepare("INSERT INTO depenses (projet_id,libelle,montant,categorie,date,justificatif) VALUES(?,?,?,?,?,?)")
        ->execute(array_values($data));
      flash('success', 'Dépense enregistrée.');
    }
    redirect('depenses.php');
  }
}

$depense = [];
if ($action === 'edit' && $id) {
  $stmt = $db->prepare("SELECT * FROM depenses WHERE id=?");
  $stmt->execute([$id]);
  $depense = $stmt->fetch();
}

$filtre_cat = $_GET['cat'] ?? '';
$depenses = $db->prepare("
  SELECT d.*, p.titre AS projet_titre
  FROM depenses d
  LEFT JOIN projets p ON p.id=d.projet_id
  WHERE (? = '' OR d.categorie = ?)
  ORDER BY d.date DESC
");
$depenses->execute([$filtre_cat, $filtre_cat]);
$depenses = $depenses->fetchAll();

// Stats par catégorie
$byCategorie = $db->query("
  SELECT categorie, COUNT(*) AS nb, SUM(montant) AS total
  FROM depenses GROUP BY categorie ORDER BY total DESC
")->fetchAll();

$allProjets = $db->query("SELECT id, titre FROM projets ORDER BY titre")->fetchAll();

$pageTitle = 'Dépenses — Nextmux ERP';
require_once 'header.php';
?>

<?php if ($action === 'list'): ?>
  <div class="flex gap-3 mb-4" style="justify-content:space-between;align-items:center;">
    <form method="GET" class="flex gap-2">
      <select name="cat" style="width:160px;">
        <option value="">Toutes catégories</option>
        <?php foreach (['materiel', 'logiciel', 'prestataire', 'transport', 'autre'] as $c): ?>
          <option value="<?= $c ?>" <?= $filtre_cat === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-ghost" title="Rechercher"><i class="fa-solid fa-search"></i></button>
      <?php if ($filtre_cat): ?><a href="depenses.php" class="btn btn-ghost">✕</a><?php endif; ?>
    </form>
    <a href="depenses.php?action=create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle dépense</a>
  </div>

  <?php
  $totalDepenses = array_sum(array_column($depenses, 'montant'));
  ?>
  <div class="stat-grid mb-4" style="grid-template-columns:repeat(<?= min(count($byCategorie) + 1, 5) ?>,1fr);">
    <div class="stat-card">
      <div class="stat-label">Total dépenses</div>
      <div class="stat-value red"><?= money($totalDepenses) ?></div>
    </div>
    <?php foreach ($byCategorie as $bc): ?>
      <div class="stat-card">
        <div class="stat-label"><?= ucfirst($bc['categorie']) ?></div>
        <div class="stat-value" style="font-size:1.1rem"><?= money($bc['total']) ?></div>
        <div class="stat-sub"><?= $bc['nb'] ?> dépense<?= $bc['nb'] > 1 ? 's' : '' ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-money-bill"></i> Dépenses (<?= count($depenses) ?>)</span></div>
    <?php if ($depenses): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Libellé</th>
              <th>Projet</th>
              <th>Catégorie</th>
              <th>Montant</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($depenses as $d): ?>
              <tr>
                <td class="text-muted"><?= formatDate($d['date']) ?></td>
                <td><?= h($d['libelle']) ?></td>
                <td class="text-muted"><?= h($d['projet_titre'] ?? '— Général') ?></td>
                <td><span class="badge badge-secondary"><?= ucfirst($d['categorie']) ?></span></td>
                <td class="mono red"><?= money($d['montant']) ?></td>
                <td>
                  <div class="flex gap-2">
                    <a href="depenses.php?action=edit&id=<?= $d['id'] ?>" class="btn btn-ghost btn-sm" title="Modifier"><i class="fa-solid fa-edit"></i></a>
                    <a href="depenses.php?action=delete&id=<?= $d['id'] ?>" class="btn btn-ghost btn-sm"
                      onclick="return confirm('Supprimer cette dépense ?')"><i class="fa-solid fa-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="icon"><i class="fa-solid fa-money-bill"></i></div>
        <p>Aucune dépense.</p>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- FORM -->
  <a href="depenses.php" class="btn btn-ghost btn-sm mb-4">← Retour</a>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('h', $errors)) ?></div>
  <?php endif; ?>

  <div class="card" style="max-width:560px;">
    <div class="card-header"><span class="card-title"><?= $id ? 'Modifier la dépense' : 'Nouvelle dépense' ?></span></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label>Libellé *</label>
          <input type="text" name="libelle" value="<?= h($depense['libelle'] ?? '') ?>" required>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Montant (FCFA) *</label>
            <input type="number" step="0.01" name="montant" value="<?= h($depense['montant'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Date *</label>
            <input type="date" name="date" value="<?= h($depense['date'] ?? date('Y-m-d')) ?>">
          </div>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Catégorie</label>
            <select name="categorie">
              <?php foreach (['materiel', 'logiciel', 'prestataire', 'transport', 'autre'] as $c): ?>
                <option value="<?= $c ?>" <?= ($depense['categorie'] ?? 'autre') === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Projet (optionnel)</label>
            <select name="projet_id">
              <option value="">— Dépense générale —</option>
              <?php foreach ($allProjets as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($depense['projet_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= h($p['titre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Justificatif (référence ou chemin fichier)</label>
          <input type="text" name="justificatif" value="<?= h($depense['justificatif'] ?? '') ?>" placeholder="Ex: reçu-amazon-jan2025.pdf">
        </div>
        <div class="flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
          <a href="depenses.php" class="btn btn-ghost">Annuler</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>