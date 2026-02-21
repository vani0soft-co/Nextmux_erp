<?php
require_once 'db.php';
requireLogin();
$currentPage = 'finance';
$db = getDB();

// Période filtre
$annee  = (int)($_GET['annee'] ?? date('Y'));
$annees = $db->query("
  SELECT DISTINCT YEAR(date_emission) AS a FROM factures
  UNION SELECT DISTINCT YEAR(date) FROM depenses
  UNION SELECT DISTINCT YEAR(date_paiement) FROM paiements
  ORDER BY a DESC
")->fetchAll(PDO::FETCH_COLUMN);

// Tableau de bord global
$dash = $db->query("SELECT * FROM v_dashboard")->fetch();

// Évolution mensuelle sur l'année
$evol = $db->prepare("
  SELECT
    m.mois,
    COALESCE(f.facture, 0)  AS facture,
    COALESCE(p.encaisse, 0) AS encaisse,
    COALESCE(d.depense, 0)  AS depense
  FROM (
    SELECT 1 AS mois UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8
    UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
  ) m
  LEFT JOIN (
    SELECT MONTH(date_emission) AS mois, SUM(montant_ttc) AS facture
    FROM factures WHERE YEAR(date_emission)=? GROUP BY MONTH(date_emission)
  ) f ON f.mois = m.mois
  LEFT JOIN (
    SELECT MONTH(date_paiement) AS mois, SUM(montant) AS encaisse
    FROM paiements WHERE YEAR(date_paiement)=? GROUP BY MONTH(date_paiement)
  ) p ON p.mois = m.mois
  LEFT JOIN (
    SELECT MONTH(date) AS mois, SUM(montant) AS depense
    FROM depenses WHERE YEAR(date)=? GROUP BY MONTH(date)
  ) d ON d.mois = m.mois
  ORDER BY m.mois
");
$evol->execute([$annee, $annee, $annee]);
$evol = $evol->fetchAll();

// Finance par projet
$parProjet = $db->query("SELECT * FROM v_finance_projets ORDER BY total_facture DESC")->fetchAll();

// Répartition dépenses par catégorie
$depCat = $db->query("
  SELECT categorie, SUM(montant) AS total, COUNT(*) AS nb
  FROM depenses GROUP BY categorie ORDER BY total DESC
")->fetchAll();

// Top clients (par CA facturé)
$topClients = $db->query("
  SELECT c.nom, SUM(f.montant_ttc) AS ca, COUNT(DISTINCT p.id) AS nb_projets
  FROM clients c
  JOIN projets p ON p.client_id=c.id
  JOIN factures f ON f.projet_id=p.id
  GROUP BY c.id ORDER BY ca DESC LIMIT 5
")->fetchAll();

// Mois labels
$moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

$pageTitle = 'Suivi financier — Nextmux ERP';
require_once 'header.php';
?>

<!-- Filtre année -->
<div class="flex gap-2 mb-6" style="align-items:center;">
  <form method="GET" class="flex gap-2">
    <select name="annee" onchange="this.form.submit()" style="width:120px;">
      <?php foreach ($annees as $a): ?>
        <option value="<?= $a ?>" <?= $a == $annee ? 'selected' : '' ?>><?= $a ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <span class="text-muted" style="font-size:0.8rem;">Affichage des données pour l'année sélectionnée</span>
</div>

<!-- KPIs globaux -->
<div class="stat-grid mb-6">
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-receipt"></i> Total facturé (TTC)</div>
    <div class="stat-value"><?= money($dash['total_facture']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-credit-card"></i> Total encaissé</div>
    <div class="stat-value green"><?= money($dash['total_encaisse']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-money-bill"></i> Total dépenses</div>
    <div class="stat-value red"><?= money($dash['total_depenses']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-wallet"></i> Résultat net</div>
    <div class="stat-value <?= $dash['resultat_net'] >= 0 ? 'green' : 'red' ?>"><?= money($dash['resultat_net']) ?></div>
    <div class="stat-sub"><?= $dash['resultat_net'] >= 0 ? '▲ Bénéficiaire' : '▼ Déficitaire' ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-chart-bar"></i> Reste à facturer</div>
    <div class="stat-value orange"><?= money($dash['total_facture'] - $dash['total_encaisse']) ?></div>
    <div class="stat-sub">Non encore encaissé</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-bullseye"></i> Taux recouvrement</div>
    <?php $taux = $dash['total_facture'] > 0 ? round(($dash['total_encaisse'] / $dash['total_facture']) * 100, 1) : 0; ?>
    <div class="stat-value <?= $taux >= 80 ? 'green' : 'orange' ?>"><?= $taux ?>%</div>
    <div class="stat-sub">Encaissé / Facturé</div>
  </div>
</div>


<div class="grid-2 mb-6">
  <!-- Finance par projet -->
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-folder"></i> Finance par projet</span></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Projet</th>
            <th>Client</th>
            <th class="text-right">Facturé</th>
            <th class="text-right">Encaissé</th>
            <th class="text-right">Marge</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($parProjet as $p): ?>
            <tr>
              <td class="text-muted" style="font-size:0.78rem"><?= h($p['projet_titre']) ?></td>
              <td class="text-muted" style="font-size:0.78rem"><?= h($p['client_nom']) ?></td>
              <td class="mono text-right" style="font-size:0.8rem"><?= money($p['total_facture']) ?></td>
              <td class="mono text-right green" style="font-size:0.8rem"><?= money($p['total_encaisse']) ?></td>
              <td class="mono text-right <?= $p['marge_nette'] >= 0 ? 'green' : 'red' ?>" style="font-size:0.8rem"><?= money($p['marge_nette']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top clients -->
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-crown"></i> Top clients (CA)</span></div>
    <div class="card-body">
      <?php
      $maxCA = $topClients ? max(array_column($topClients, 'ca')) : 1;
      foreach ($topClients as $i => $cl): ?>
        <div style="margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <span style="font-size:0.82rem;font-weight:500"><?= h($cl['nom']) ?></span>
            <span class="mono" style="font-size:0.8rem;color:var(--green)"><?= money($cl['ca']) ?></span>
          </div>
          <div class="progress-bar" style="height:6px">
            <div class="fill" style="width:<?= round(($cl['ca'] / $maxCA) * 100) ?>%;background:<?= ['#3b82f6', '#3fb950', '#a371f7', '#f59e0b', '#ef4444'][$i] ?>"></div>
          </div>
          <span class="text-muted" style="font-size:0.7rem"><?= $cl['nb_projets'] ?> projet<?= $cl['nb_projets'] > 1 ? 's' : '' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Répartition dépenses -->
<div class="card">
  <div class="card-header"><span class="card-title"><i class="fa-solid fa-money-bill"></i> Répartition des dépenses par catégorie</span></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;">
      <?php
      $totalDep = array_sum(array_column($depCat, 'total'));
      $colors = ['materiel' => '#3b82f6', 'logiciel' => '#a371f7', 'prestataire' => '#f59e0b', 'transport' => '#3fb950', 'autre' => '#8b949e'];
      foreach ($depCat as $dc):
        $pct = $totalDep > 0 ? round(($dc['total'] / $totalDep) * 100, 1) : 0;
        $color = $colors[$dc['categorie']] ?? '#8b949e';
      ?>
        <div style="padding:16px;border:1px solid var(--border);border-radius:8px;border-left:4px solid <?= $color ?>">
          <div class="stat-label"><?= ucfirst($dc['categorie']) ?></div>
          <div style="font-size:1.2rem;font-weight:700;color:<?= $color ?>"><?= money($dc['total']) ?></div>
          <div class="stat-sub"><?= $dc['nb'] ?> dépense<?= $dc['nb'] > 1 ? 's' : '' ?> · <?= $pct ?>%</div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>
