(() => {
  const racine = document.getElementById('dashboard');
  if (!racine || typeof Chart === 'undefined') return;

  const urlApi = racine.dataset.api;
  const urlCommande = racine.dataset.commande;
  let jours = parseInt(racine.dataset.jours, 10) || 30;
  let donnees = JSON.parse(racine.dataset.initial);
  let derniereId = donnees.dernieres.length ? donnees.dernieres[0].id : 0;

  const LABELS = {
    en_attente: 'En attente', confirmee: 'Confirmée', payee: 'Payée',
    livree: 'Livrée', annulee: 'Annulée'
  };
  const COULEURS = {
    en_attente: '#D98A00', confirmee: '#2F5A94', payee: '#1F8A5B',
    livree: '#0B1F3A', annulee: '#C0392B'
  };
  const AUTRES = ['#5B6B82', '#8FA3BF', '#B8C4D6', '#E2DDD0'];

  const nombre = (n) => (Number(n) || 0).toLocaleString('fr-FR');
  const fcfa = (n) => nombre(n) + ' FCFA';
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const libelle = (s) => LABELS[s] || (s ? s.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase()) : '—');

  const ctxCourbe = document.getElementById('db-courbe');
  const ctxAnneau = document.getElementById('db-anneau');

  const courbe = new Chart(ctxCourbe, {
    type: 'line',
    data: { labels: [], datasets: [{
      label: 'CA (FCFA)', data: [], borderColor: '#1D3B66', backgroundColor: 'rgba(47,90,148,.15)',
      fill: true, tension: .3, pointRadius: 2
    }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false },
        tooltip: { callbacks: { label: (c) => fcfa(c.parsed.y) } } },
      scales: { y: { beginAtZero: true, ticks: { callback: (v) => nombre(v) } },
                x: { ticks: { maxTicksLimit: 10 } } }
    }
  });

  const anneau = new Chart(ctxAnneau, {
    type: 'doughnut',
    data: { labels: [], datasets: [{ data: [], backgroundColor: [], borderWidth: 0 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
               plugins: { legend: { position: 'bottom' } } }
  });

  function rendreKpis(k) {
    document.querySelectorAll('[data-kpi]').forEach((el) => {
      const v = k[el.dataset.kpi];
      if (v === undefined) return;
      el.textContent = el.dataset.fmt === 'fcfa' ? fcfa(v) : nombre(v);
    });
  }

  function rendreGraphes(d) {
    courbe.data.labels = d.serie.labels;
    courbe.data.datasets[0].data = d.serie.ca;
    courbe.update();
    document.getElementById('db-total-periode').textContent =
      `${fcfa(d.serie.total)} sur ${d.jours} jours`;

    let i = 0;
    anneau.data.labels = d.statuts.map((s) => libelle(s.statut));
    anneau.data.datasets[0].data = d.statuts.map((s) => s.nb);
    anneau.data.datasets[0].backgroundColor = d.statuts.map((s) => COULEURS[s.statut] || AUTRES[i++ % AUTRES.length]);
    anneau.update();
  }

  function rendreTable(d) {
    const corps = document.getElementById('db-dernieres');
    if (!d.dernieres.length) {
      corps.innerHTML = '<tr><td colspan="5" class="db-vide">Aucune commande pour le moment.</td></tr>';
      return;
    }
    corps.innerHTML = d.dernieres.map((c) => `
      <tr>
        <td><a href="${esc(urlCommande.replace(/0$/, c.id))}">#${esc(c.id)}</a></td>
        <td>${esc(c.client)}</td>
        <td>${fcfa(c.total)}</td>
        <td><span class="db-badge db-badge--${esc(c.statut)}">${esc(libelle(c.statut))}</span></td>
        <td>${esc(c.date)}</td>
      </tr>`).join('');
  }

  function rendreTop(d) {
    const liste = document.getElementById('db-top');
    if (!d.top.length) {
      liste.innerHTML = '<li class="db-vide">Pas encore de ventes sur la période.</li>';
      return;
    }
    const max = Math.max(...d.top.map((p) => p.qte), 1);
    liste.innerHTML = d.top.map((p) => `
      <li>
        <strong>${esc(p.nom)}</strong>
        <small>${nombre(p.qte)} vendu(s) · ${fcfa(p.ca)}</small>
        <div class="db-barre"><i style="width:${Math.round((p.qte / max) * 100)}%"></i></div>
      </li>`).join('');
  }

  function afficher(d, notifier) {
    donnees = d;
    rendreKpis(d.kpis);
    rendreGraphes(d);
    rendreTable(d);
    rendreTop(d);
    document.getElementById('db-heure').textContent = d.genere_a;

    const nouvelle = d.dernieres.length ? d.dernieres[0] : null;
    if (notifier && nouvelle && nouvelle.id > derniereId) {
      const alerte = document.getElementById('db-alerte');
      alerte.textContent = `🔔 Nouvelle commande de ${nouvelle.client} : ${fcfa(nouvelle.total)}`;
      alerte.hidden = false;
      setTimeout(() => { alerte.hidden = true; }, 12000);
    }
    if (nouvelle) derniereId = Math.max(derniereId, nouvelle.id);
  }

  async function charger(notifier = true) {
    try {
      const rep = await fetch(`${urlApi}?jours=${jours}`, {
        headers: { Accept: 'application/json' }, credentials: 'same-origin'
      });
      if (!rep.ok) throw new Error(rep.status);
      afficher(await rep.json(), notifier);
    } catch (e) {
      document.getElementById('db-heure').textContent = 'hors ligne';
    }
  }

  document.getElementById('db-periode').addEventListener('change', (e) => {
    jours = parseInt(e.target.value, 10);
    charger(false);
  });
  document.getElementById('db-actualiser').addEventListener('click', () => charger(true));
  setInterval(() => { if (!document.hidden) charger(true); }, 30000);

  afficher(donnees, false);
})();
