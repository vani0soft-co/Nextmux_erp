<?php
require_once 'db.php';
requireLogin();
$currentPage = 'factures';
$db = getDB();

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// DELETE
if ($action === 'delete' && $id) {
  $db->prepare("DELETE FROM factures WHERE id=?")->execute([$id]);
  flash('success', 'Facture supprimée.');
  redirect('factures.php');
}

// Générer numéro auto
function nextNumeroFacture(PDO $db): string
{
  $year = date('Y');
  $stmt = $db->prepare("SELECT COUNT(*) FROM factures WHERE YEAR(date_emission)=?");
  $stmt->execute([$year]);
  $num = $stmt->fetchColumn() + 1;
  return sprintf('FAC-%s-%03d', $year, $num);
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ht  = (float)str_replace(',', '.', $_POST['montant_ht'] ?? '0');
  $tva = (float)str_replace(',', '.', $_POST['tva'] ?? '20');
  $ttc = round($ht * (1 + $tva / 100), 2);

  $data = [
    'projet_id'     => (int)($_POST['projet_id'] ?? 0),
    'numero'        => trim($_POST['numero'] ?? ''),
    'montant_ht'    => $ht,
    'tva'           => $tva,
    'montant_ttc'   => $ttc,
    'statut'        => $_POST['statut'] ?? 'brouillon',
    'date_emission' => $_POST['date_emission'] ?: date('Y-m-d'),
    'date_echeance' => $_POST['date_echeance'] ?: null,
    'notes'         => trim($_POST['notes'] ?? ''),
  ];
  $errors = [];
  if (!$data['projet_id']) $errors[] = 'Projet obligatoire.';
  if (!$data['numero'])    $errors[] = 'Numéro obligatoire.';

  if (!$errors) {
    if ($id) {
      $db->prepare("UPDATE factures SET projet_id=?,numero=?,montant_ht=?,tva=?,montant_ttc=?,statut=?,date_emission=?,date_echeance=?,notes=? WHERE id=?")
        ->execute([...array_values($data), $id]);
      flash('success', 'Facture mise à jour.');
    } else {
      $db->prepare("INSERT INTO factures (projet_id,numero,montant_ht,tva,montant_ttc,statut,date_emission,date_echeance,notes) VALUES(?,?,?,?,?,?,?,?,?)")
        ->execute(array_values($data));
      flash('success', 'Facture créée.');
    }
    redirect('factures.php');
  }
}

// LOAD
$facture = [];
if (in_array($action, ['edit', 'view']) && $id) {
  $stmt = $db->prepare("SELECT f.*, p.titre AS projet_titre, c.nom AS client_nom, c.adresse, c.ville, c.email AS client_email FROM factures f JOIN projets p ON p.id=f.projet_id JOIN clients c ON c.id=p.client_id WHERE f.id=?");
  $stmt->execute([$id]);
  $facture = $stmt->fetch();
}

// Paiements de cette facture
$paiements = [];
if ($action === 'view' && $id) {
  $stmt = $db->prepare("SELECT * FROM paiements WHERE facture_id=? ORDER BY date_paiement");
  $stmt->execute([$id]);
  $paiements = $stmt->fetchAll();
}

// Liste
$search = $_GET['q'] ?? '';
$statut = $_GET['statut'] ?? '';
$factures = $db->prepare("
  SELECT f.*, p.titre AS projet_titre, c.nom AS client_nom,
    COALESCE(SUM(pa.montant),0) AS total_paye
  FROM factures f
  JOIN projets p ON p.id=f.projet_id
  JOIN clients c ON c.id=p.client_id
  LEFT JOIN paiements pa ON pa.facture_id=f.id
  WHERE (f.numero LIKE ? OR c.nom LIKE ?)
    AND (? = '' OR f.statut = ?)
  GROUP BY f.id ORDER BY f.date_emission DESC
");
$factures->execute(["%$search%", "%$search%", $statut, $statut]);
$factures = $factures->fetchAll();

$allProjets = $db->query("SELECT p.id, p.titre, c.nom AS client_nom FROM projets p JOIN clients c ON c.id=p.client_id ORDER BY c.nom")->fetchAll();
$nextNum = nextNumeroFacture($db);

$pageTitle = 'Factures — Nextmux ERP';
require_once 'header.php';
?>

<?php if ($action === 'list'): ?>
  <div class="flex gap-3 mb-4" style="justify-content:space-between;align-items:center;">
    <form method="GET" class="flex gap-2">
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Rechercher…" style="width:200px;">
      <select name="statut" style="width:180px;">
        <option value="">Tous statuts</option>
        <option value="brouillon" <?= $statut === 'brouillon'           ? 'selected' : '' ?>>Brouillon</option>
        <option value="envoyee" <?= $statut === 'envoyee'             ? 'selected' : '' ?>>Envoyée</option>
        <option value="partiellement_payee" <?= $statut === 'partiellement_payee' ? 'selected' : '' ?>>Part. payée</option>
        <option value="payee" <?= $statut === 'payee'               ? 'selected' : '' ?>>Payée</option>
      </select>
      <button type="submit" class="btn btn-ghost" title="Rechercher"><i class="fa-solid fa-search"></i></button>
    </form>
    <a href="factures.php?action=create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle facture</a>
  </div>

  <?php
  $totaux = ['facture' => 0, 'paye' => 0, 'reste' => 0];
  foreach ($factures as $f) {
    $totaux['facture'] += $f['montant_ttc'];
    $totaux['paye']    += $f['total_paye'];
    $totaux['reste']   += $f['montant_ttc'] - $f['total_paye'];
  }
  ?>
  <div class="stat-grid mb-4" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
      <div class="stat-label">Total facturé TTC</div>
      <div class="stat-value"><?= money($totaux['facture']) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total encaissé</div>
      <div class="stat-value green"><?= money($totaux['paye']) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Reste à percevoir</div>
      <div class="stat-value <?= $totaux['reste'] > 0 ? 'orange' : '' ?>"><?= money($totaux['reste']) ?></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-receipt"></i> Factures (<?= count($factures) ?>)</span></div>
    <?php if ($factures): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Numéro</th>
              <th>Client</th>
              <th>Projet</th>
              <th>HT</th>
              <th>TTC</th>
              <th>Payé</th>
              <th>Reste</th>
              <th>Statut</th>
              <th>Émission</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($factures as $f):
              $reste = $f['montant_ttc'] - $f['total_paye'];
            ?>
              <tr>
                <td class="mono"><a href="factures.php?action=view&id=<?= $f['id'] ?>" style="color:var(--accent);text-decoration:none"><?= h($f['numero']) ?></a></td>
                <td><?= h($f['client_nom']) ?></td>
                <td class="text-muted"><?= h($f['projet_titre']) ?></td>
                <td class="mono"><?= money($f['montant_ht']) ?></td>
                <td class="mono"><?= money($f['montant_ttc']) ?></td>
                <td class="mono green"><?= money($f['total_paye']) ?></td>
                <td class="mono <?= $reste > 0 ? 'red' : '' ?>"><?= money($reste) ?></td>
                <td><?= statutBadgeFacture($f['statut']) ?></td>
                <td class="text-muted"><?= formatDate($f['date_emission']) ?></td>
                <td>
                  <a href="factures.php?action=view&id=<?= $f['id'] ?>" class="btn btn-ghost btn-sm" title="Voir"><i class="fa-solid fa-eye"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="icon"><i class="fa-solid fa-receipt"></i></div>
        <p>Aucune facture.</p>
      </div>
    <?php endif; ?>
  </div>

<?php elseif ($action === 'view' && $facture): ?>
  <!-- DETAIL FACTURE -->
  <div class="flex gap-2 mb-4">
    <a href="factures.php" class="btn btn-ghost btn-sm">← Retour</a>
    <a href="factures.php?action=edit&id=<?= $id ?>" class="btn btn-ghost btn-sm" title="Modifier"><i class="fa-solid fa-edit"></i> Modifier</a>
    <a href="paiements.php?action=create&facture_id=<?= $id ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Enregistrer paiement</a>
  </div>

  <div class="grid-2 mb-4">
    <div class="card">
      <div class="card-header"><span class="card-title">Facture <?= h($facture['numero']) ?></span><?= statutBadgeFacture($facture['statut']) ?></div>
      <div class="card-body">
        <table style="width:100%;font-size:0.875rem;">
          <tr>
            <td style="color:var(--muted);padding:5px 0;width:130px">Client</td>
            <td style="font-weight:600"><?= h($facture['client_nom']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:5px 0">Email</td>
            <td><?= h($facture['client_email']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:5px 0">Projet</td>
            <td><?= h($facture['projet_titre']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:5px 0">Date émission</td>
            <td><?= formatDate($facture['date_emission']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:5px 0">Échéance</td>
            <td><?= formatDate($facture['date_echeance']) ?></td>
          </tr>
          <?php if ($facture['notes']): ?>
            <tr>
              <td style="color:var(--muted);padding:5px 0">Notes</td>
              <td class="text-muted"><?= nl2br(h($facture['notes'])) ?></td>
            </tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Montants</span></div>
      <div class="card-body">
        <?php
        $totalPaye = array_sum(array_column($paiements, 'montant'));
        $reste = $facture['montant_ttc'] - $totalPaye;
        $pct = $facture['montant_ttc'] > 0 ? min(100, round(($totalPaye / $facture['montant_ttc']) * 100)) : 0;
        ?>
        <table style="width:100%;font-size:0.875rem;">
          <tr>
            <td style="color:var(--muted);padding:6px 0">Montant HT</td>
            <td class="mono text-right"><?= money($facture['montant_ht']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--muted);padding:6px 0">TVA <?= $facture['tva'] ?>%</td>
            <td class="mono text-right"><?= money($facture['montant_ttc'] - $facture['montant_ht']) ?></td>
          </tr>
          <tr style="font-size:1.05rem;font-weight:600">
            <td style="padding:10px 0">Total TTC</td>
            <td class="mono text-right"><?= money($facture['montant_ttc']) ?></td>
          </tr>
          <tr>
            <td style="color:var(--green);padding:6px 0">Encaissé</td>
            <td class="mono text-right" style="color:var(--green)"><?= money($totalPaye) ?></td>
          </tr>
          <tr>
            <td style="color:var(--red);padding:6px 0">Reste à percevoir</td>
            <td class="mono text-right" style="color:<?= $reste > 0 ? 'var(--red)' : 'var(--green)' ?>"><?= money($reste) ?></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Paiements reçus -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fa-solid fa-credit-card"></i> Paiements reçus (<?= count($paiements) ?>)</span>
      <a href="paiements.php?action=create&facture_id=<?= $id ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Paiement</a>
    </div>
    <?php if ($paiements): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Montant</th>
              <th>Mode</th>
              <th>Référence</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($paiements as $p): ?>
              <tr>
                <td><?= formatDate($p['date_paiement']) ?></td>
                <td class="mono green"><?= money($p['montant']) ?></td>
                <td><?= ucfirst($p['mode_paiement']) ?></td>
                <td class="text-muted mono"><?= h($p['reference'] ?? '—') ?></td>
                <td><a href="paiements.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" onclick="return confirm('Supprimer ?" )" title="Supprimer"><i class="fa-solid fa-trash"></i></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="icon"><i class="fa-solid fa-credit-card"></i></div>
        <p>Aucun paiement enregistré.</p>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- FORM -->
  <a href="factures.php" class="btn btn-ghost btn-sm mb-4">← Retour</a>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('h', $errors)) ?></div>
  <?php endif; ?>

  <div class="card" style="max-width:700px;">
    <div class="card-header"><span class="card-title"><?= $id ? 'Modifier la facture' : 'Nouvelle facture' ?></span></div>
    <div class="card-body">
      <form method="POST">
        <div class="grid-2">
          <div class="form-group">
            <label>Numéro de facture *</label>
            <input type="text" name="numero" value="<?= h($facture['numero'] ?? $nextNum) ?>" required>
          </div>
          <div class="form-group">
            <label>Projet *</label>
            <select name="projet_id" required>
              <option value="">-- Choisir --</option>
              <?php foreach ($allProjets as $p): ?>
                <option value="<?= $p['id'] ?>" <?= (($facture['projet_id'] ?? $_GET['projet_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                  <?= h($p['client_nom']) ?> — <?= h($p['titre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="grid-3">
          <div class="form-group">
            <label>Montant HT (FCFA) *</label>
            <input type="number" step="0.01" name="montant_ht" id="ht" value="<?= h($facture['montant_ht'] ?? '0') ?>" oninput="calcTTC()">
          </div>
          <div class="form-group">
            <label>TVA (%)</label>
            <input type="number" step="0.01" name="tva" id="tva" value="<?= h($facture['tva'] ?? '20') ?>" oninput="calcTTC()">
          </div>
          <div class="form-group">
            <label>Montant TTC (calculé)</label>
            <input type="text" id="ttc_display" readonly style="color:var(--green);font-weight:600" placeholder="0 FCFA">
          </div>
        </div>
        <div class="grid-3">
          <div class="form-group">
            <label>Statut</label>
            <select name="statut">
              <?php foreach (['brouillon', 'envoyee', 'partiellement_payee', 'payee'] as $s): ?>
                <option value="<?= $s ?>" <?= ($facture['statut'] ?? 'brouillon') === $s ? 'selected' : '' ?>>
                  <?= ucfirst(str_replace('_', ' ', $s)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Date d'émission</label>
            <input type="date" name="date_emission" value="<?= h($facture['date_emission'] ?? date('Y-m-d')) ?>">
          </div>
          <div class="form-group">
            <label>Date d'échéance</label>
            <input type="date" name="date_echeance" value="<?= h($facture['date_echeance'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Notes / Mentions</label>
          <textarea name="notes"><?= h($facture['notes'] ?? '') ?></textarea>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
          <a href="factures.php" class="btn btn-ghost">Annuler</a>
        </div>
      </form>
    </div>
  </div>

  <script>
    function calcTTC() {
      const ht = parseFloat(document.getElementById('ht').value) || 0;
      const tva = parseFloat(document.getElementById('tva').value) || 0;
      const ttc = ht * (1 + tva / 100);
      document.getElementById('ttc_display').value = Math.round(ttc) + ' FCFA';
    }
    calcTTC();
  </script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>