<?php
require_once 'db.php';
requireLogin();

$pageTitle   = 'Tableau de bord — Nextmux ERP';
$currentPage = 'dashboard';

$db = getDB();

// Statistiques globales
$dash = $db->query("SELECT * FROM v_dashboard")->fetch();

// Projets actifs (5 derniers)
$projets = $db->query("
  SELECT p.*, c.nom AS client_nom,
    (SELECT COUNT(*) FROM taches t WHERE t.projet_id = p.id) AS nb_taches,
    (SELECT COUNT(*) FROM taches t WHERE t.projet_id = p.id AND t.statut = 'termine') AS nb_terminees
  FROM projets p
  JOIN clients c ON c.id = p.client_id
  WHERE p.statut = 'en_cours'
  ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

// Factures en attente
$facturesAttente = $db->query("
  SELECT f.*, p.titre AS projet_titre, c.nom AS client_nom
  FROM factures f
  JOIN projets p ON p.id = f.projet_id
  JOIN clients c ON c.id = p.client_id
  WHERE f.statut IN ('envoyee', 'partiellement_payee')
  ORDER BY f.date_echeance ASC LIMIT 5
")->fetchAll();

// Tâches urgentes
$tachesUrgentes = $db->query("
  SELECT t.*, p.titre AS projet_titre
  FROM taches t
  JOIN projets p ON p.id = t.projet_id
  WHERE t.statut != 'termine' AND t.priorite IN ('haute', 'critique')
  ORDER BY FIELD(t.priorite,'critique','haute'), t.date_echeance ASC
  LIMIT 6
")->fetchAll();

// Finance mensuelle (12 derniers mois)
$finMois = $db->query("
  SELECT
    DATE_FORMAT(date_emission, '%b %Y') AS mois,
    SUM(montant_ttc) AS total
  FROM factures
  WHERE date_emission >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
  GROUP BY DATE_FORMAT(date_emission, '%Y-%m')
  ORDER BY DATE_FORMAT(date_emission, '%Y-%m')
")->fetchAll();

require_once 'header.php';
?>

<!-- STAT CARDS -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-users"></i> Clients</div>
    <div class="stat-value blue"><?= $dash['nb_clients'] ?></div>
    <div class="stat-sub">Clients actifs</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-folder"></i> Projets actifs</div>
    <div class="stat-value blue"><?= $dash['projets_actifs'] ?></div>
    <div class="stat-sub">En cours de réalisation</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-tasks"></i> Tâches ouvertes</div>
    <div class="stat-value orange"><?= $dash['taches_ouvertes'] ?></div>
    <div class="stat-sub">À faire ou en cours</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-receipt"></i> Total facturé</div>
    <div class="stat-value"><?= money($dash['total_facture']) ?></div>
    <div class="stat-sub">TTC toutes factures</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-credit-card"></i> Encaissé</div>
    <div class="stat-value green"><?= money($dash['total_encaisse']) ?></div>
    <div class="stat-sub">Paiements reçus</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-money-bill"></i> Dépenses</div>
    <div class="stat-value red"><?= money($dash['total_depenses']) ?></div>
    <div class="stat-sub">Total engagé</div>
  </div>
</div>

<!-- Résultat net mis en avant -->
<div class="card mb-6">
  <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; padding:20px 24px;">
    <div>
      <div class="stat-label"><i class="fa-solid fa-chart-line"></i> Résultat net (encaissé − dépenses)</div>
      <div class="stat-value <?= $dash['resultat_net'] >= 0 ? 'green' : 'red' ?>" style="font-size:2rem;">
        <?= money($dash['resultat_net']) ?>
      </div>
    </div>
    <a href="finance.php" class="btn btn-ghost">Voir le détail <i class="fa-solid fa-arrow-right"></i></a>
  </div>
</div>

<div class="grid-2 mb-6">
  <!-- Projets en cours -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fa-solid fa-folder-open"></i> Projets en cours</span>
      <a href="projets.php" class="btn btn-ghost btn-sm">Voir tous</a>
    </div>
    <?php if ($projets): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Projet</th>
              <th>Client</th>
              <th>Avancement</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($projets as $p):
              $pct = $p['nb_taches'] > 0 ? round(($p['nb_terminees'] / $p['nb_taches']) * 100) : 0;
            ?>
              <tr>
                <td><a href="projets.php?action=view&id=<?= $p['id'] ?>" style="color:var(--accent); text-decoration:none;"><?= h($p['titre']) ?></a></td>
                <td class="text-muted"><?= h($p['client_nom']) ?></td>
                <td style="min-width:100px;">
                  <div class="flex gap-2">
                    <div class="progress-bar" style="flex:1">
                      <div class="fill" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="text-muted mono" style="font-size:0.7rem; width:32px;"><?= $pct ?>%</span>
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
        <p>Aucun projet en cours</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Tâches urgentes -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fa-solid fa-fire"></i> Tâches urgentes</span>
      <a href="taches.php" class="btn btn-ghost btn-sm">Voir toutes</a>
    </div>
    <?php if ($tachesUrgentes): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Tâche</th>
              <th>Projet</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tachesUrgentes as $t): ?>
              <tr>
                <td><?= h($t['titre']) ?></td>
                <td class="text-muted"><?= h($t['projet_titre']) ?></td>
                <td><?= statutBadgeTache($t['statut']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="icon"><i class="fa-solid fa-check-circle"></i></div>
        <p>Aucune tâche urgente</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Factures en attente -->
<?php if ($facturesAttente): ?>
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fa-solid fa-hourglass-end"></i> Factures en attente de paiement</span>
      <a href="factures.php" class="btn btn-ghost btn-sm">Toutes les factures</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>N°</th>
            <th>Client</th>
            <th>Projet</th>
            <th>Montant TTC</th>
            <th>Échéance</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($facturesAttente as $f): ?>
            <tr>
              <td class="mono"><?= h($f['numero']) ?></td>
              <td><?= h($f['client_nom']) ?></td>
              <td class="text-muted"><?= h($f['projet_titre']) ?></td>
              <td class="text-right mono"><?= money($f['montant_ttc']) ?></td>
              <td class="<?= ($f['date_echeance'] && $f['date_echeance'] < date('Y-m-d')) ? 'text-muted' : '' ?>"><?= formatDate($f['date_echeance']) ?></td>
              <td><?= statutBadgeFacture($f['statut']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>