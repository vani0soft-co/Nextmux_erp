<?php
require_once 'db.php';
requireLogin();
$currentPage = 'paiements';
$db = getDB();

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// DELETE
if ($action === 'delete' && $id) {
  $stmt = $db->prepare("SELECT facture_id FROM paiements WHERE id=?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  $db->prepare("DELETE FROM paiements WHERE id=?")->execute([$id]);
  // Auto-recalcul statut facture
  if ($row) updateStatutFacture($db, $row['facture_id']);
  flash('success', 'Paiement supprimé.');
  redirect('paiements.php');
}

function updateStatutFacture(PDO $db, int $factureId): void
{
  $stmt = $db->prepare("SELECT montant_ttc FROM factures WHERE id=?");
  $stmt->execute([$factureId]);
  $facture = $stmt->fetch();
  if (!$facture) return;

  $stmt = $db->prepare("SELECT COALESCE(SUM(montant),0) AS total FROM paiements WHERE facture_id=?");
  $stmt->execute([$factureId]);
  $totalPaye = (float)$stmt->fetchColumn();
  $ttc = (float)$facture['montant_ttc'];

  $statut = 'envoyee';
  if ($totalPaye <= 0)            $statut = 'envoyee';
  elseif ($totalPaye >= $ttc)     $statut = 'payee';
  else                            $statut = 'partiellement_payee';

  $db->prepare("UPDATE factures SET statut=? WHERE id=?")->execute([$statut, $factureId]);
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'facture_id'    => (int)($_POST['facture_id'] ?? 0),
    'montant'       => (float)str_replace(',', '.', $_POST['montant'] ?? '0'),
    'date_paiement' => $_POST['date_paiement'] ?: date('Y-m-d'),
    'mode_paiement' => $_POST['mode_paiement'] ?? 'virement',
    'reference'     => trim($_POST['reference'] ?? ''),
  ];
  $errors = [];
  if (!$data['facture_id']) $errors[] = 'Facture obligatoire.';
  if ($data['montant'] <= 0) $errors[] = 'Montant invalide.';

  if (!$errors) {
    $db->prepare("INSERT INTO paiements (facture_id,montant,date_paiement,mode_paiement,reference) VALUES(?,?,?,?,?)")
      ->execute(array_values($data));
    updateStatutFacture($db, $data['facture_id']);
    flash('success', 'Paiement enregistré.');
    redirect('paiements.php');
  }
}

// LIST
$paiements = $db->query("
  SELECT pa.*, f.numero, f.montant_ttc, p.titre AS projet_titre, c.nom AS client_nom
  FROM paiements pa
  JOIN factures f ON f.id=pa.facture_id
  JOIN projets p ON p.id=f.projet_id
  JOIN clients c ON c.id=p.client_id
  ORDER BY pa.date_paiement DESC
")->fetchAll();

// Factures ouvertes pour form
$facturesOuvertes = $db->query("
  SELECT f.id, f.numero, f.montant_ttc, c.nom AS client_nom,
    COALESCE(SUM(pa.montant),0) AS paye
  FROM factures f
  JOIN projets p ON p.id=f.projet_id
  JOIN clients c ON c.id=p.client_id
  LEFT JOIN paiements pa ON pa.facture_id=f.id
  WHERE f.statut IN ('envoyee','partiellement_payee')
  GROUP BY f.id ORDER BY f.date_echeance
")->fetchAll();

$pageTitle = 'Paiements — Nextmux ERP';
require_once 'header.php';
?>

<?php if ($action === 'list'): ?>
  <div class="flex gap-3 mb-4" style="justify-content:space-between;align-items:center;">
    <div></div>
    <a href="paiements.php?action=create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Enregistrer un paiement</a>
  </div>

  <?php
  $totalEncaisse = array_sum(array_column($paiements, 'montant'));
  ?>
  <div class="stat-grid mb-4" style="grid-template-columns:repeat(2,1fr);">
    <div class="stat-card">
      <div class="stat-label">Total encaissé</div>
      <div class="stat-value green"><?= money($totalEncaisse) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Nombre de paiements</div>
      <div class="stat-value"><?= count($paiements) ?></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-credit-card"></i> Historique des paiements</span></div>
    <?php if ($paiements): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Client</th>
              <th>Facture</th>
              <th>Projet</th>
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
                <td><?= h($p['client_nom']) ?></td>
                <td class="mono"><a href="factures.php?action=view&id=<?= $p['facture_id'] ?>" style="color:var(--accent);text-decoration:none"><?= h($p['numero']) ?></a></td>
                <td class="text-muted"><?= h($p['projet_titre']) ?></td>
                <td class="mono green"><?= money($p['montant']) ?></td>
                <td><?= ucfirst($p['mode_paiement']) ?></td>
                <td class="mono text-muted"><?= h($p['reference'] ?? '—') ?></td>
                <td><a href="paiements.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" onclick="return confirm('Supprimer ce paiement ?')" title="Supprimer"><i class="fa-solid fa-trash"></i></a></td>
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
  <a href="paiements.php" class="btn btn-ghost btn-sm mb-4">← Retour</a>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('h', $errors)) ?></div>
  <?php endif; ?>

  <div class="card" style="max-width:560px;">
    <div class="card-header"><span class="card-title">Enregistrer un paiement</span></div>
    <div class="card-body">
      <?php if (!$facturesOuvertes): ?>
        <div class="alert alert-info">Aucune facture en attente de paiement.</div>
      <?php else: ?>
        <form method="POST">
          <div class="form-group">
            <label>Facture *</label>
            <select name="facture_id" required onchange="updateReste(this)">
              <option value="">-- Choisir une facture --</option>
              <?php foreach ($facturesOuvertes as $f): ?>
                <option value="<?= $f['id'] ?>"
                  data-reste="<?= $f['montant_ttc'] - $f['paye'] ?>"
                  <?= ($_GET['facture_id'] ?? '') == $f['id'] ? 'selected' : '' ?>>
                  <?= h($f['numero']) ?> — <?= h($f['client_nom']) ?> (Reste : <?= money($f['montant_ttc'] - $f['paye']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label>Montant (FCFA) *</label>
              <input type="number" step="0.01" name="montant" id="montant" value="" required>
              <small class="text-muted" id="reste_info"></small>
            </div>
            <div class="form-group">
              <label>Date du paiement</label>
              <input type="date" name="date_paiement" value="<?= date('Y-m-d') ?>">
            </div>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label>Mode de paiement</label>
              <select name="mode_paiement">
                <option value="virement">Virement bancaire</option>
                <option value="cheque">Chèque</option>
                <option value="especes">Espèces</option>
                <option value="carte">Carte bancaire</option>
                <option value="autre">Autre</option>
              </select>
            </div>
            <div class="form-group">
              <label>Référence bancaire</label>
              <input type="text" name="reference" placeholder="Ex: VIR-2025-001">
            </div>
          </div>
          <div class="flex gap-2">
            <button type="submit" class="btn btn-success"><i class="fa-solid fa-save"></i> Enregistrer le paiement</button>
            <a href="paiements.php" class="btn btn-ghost">Annuler</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function updateReste(sel) {
      const opt = sel.options[sel.selectedIndex];
      const reste = parseFloat(opt.dataset.reste) || 0;
      document.getElementById('montant').value = reste > 0 ? reste.toFixed(2) : '';
      document.getElementById('reste_info').textContent = reste > 0 ? 'Reste à percevoir : ' + Math.round(reste) + ' FCFA' : '';
    }
    // Trigger if pre-selected
    const sel = document.querySelector('select[name="facture_id"]');
    if (sel && sel.value) updateReste(sel);
  </script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
