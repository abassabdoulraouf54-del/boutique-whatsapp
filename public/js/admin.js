(() => {
  // Les messages de succès disparaissent seuls (les erreurs restent)
  document.querySelectorAll('.admin-main .flash:not(.error):not(.danger)').forEach((f) => setTimeout(() => {
    f.classList.add('hide'); setTimeout(() => f.remove(), 450);
  }, 5000));

  // Recherche instantanée dans les longues listes
  document.querySelectorAll('.pg-table-wrap').forEach((wrap) => {
    const lignes = wrap.querySelectorAll('tbody tr');
    if (lignes.length < 6) return;
    const champ = document.createElement('input');
    champ.type = 'search'; champ.className = 'pg-recherche';
    champ.placeholder = 'Rechercher dans la liste…'; champ.setAttribute('aria-label', 'Rechercher');
    wrap.parentNode.insertBefore(champ, wrap);
    champ.addEventListener('input', () => {
      const q = champ.value.trim().toLowerCase();
      lignes.forEach((tr) => { tr.hidden = q !== '' && !tr.textContent.toLowerCase().includes(q); });
    });
  });

  // Aperçu immédiat de la photo choisie
  document.querySelectorAll('input[type=file]').forEach((champ) => {
    const img = document.createElement('img');
    img.className = 'pg-apercu-live'; img.alt = ''; img.hidden = true;
    champ.insertAdjacentElement('afterend', img);
    champ.addEventListener('change', () => {
      const f = champ.files && champ.files[0];
      if (f && f.type.startsWith('image/')) { img.src = URL.createObjectURL(f); img.hidden = false; }
      else { img.hidden = true; }
    });
  });

  // Évite les doubles envois
  document.addEventListener('submit', (e) => {
    if (e.defaultPrevented) return;
    const b = e.target.querySelector && e.target.querySelector('button[type=submit]:not(.danger)');
    if (b) setTimeout(() => { b.disabled = true; }, 0);
  });
  window.addEventListener('pageshow', () => {
    document.querySelectorAll('button[type=submit]:disabled').forEach((b) => { b.disabled = false; });
  });
})();
