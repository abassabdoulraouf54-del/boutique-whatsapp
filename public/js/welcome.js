(function () {
  var greet = document.getElementById('greet');
  var cible = document.getElementById('hello-word');

  // Salutation selon l'heure, au-dessus du mot
  if (greet) {
    var h = new Date().getHours();
    greet.textContent = '👋 ' + (h < 12 ? 'Bonjour' : (h < 18 ? 'Bonne après-midi' : 'Bonsoir'));
  }

  if (!cible) { return; }
  var reduit = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduit) { return; } // le mot reste fixe : "Bienvenue"

  var mots = ['Welcome', 'Bienvenue'];
  var m = 0, n = 0, efface = false;

  function tick() {
    var mot = mots[m];
    if (!efface) {
      n++;
      cible.textContent = mot.slice(0, n);
      if (n === mot.length) { efface = true; return setTimeout(tick, 1600); }
      return setTimeout(tick, 95);
    }
    n--;
    cible.textContent = mot.slice(0, n);
    if (n === 0) { efface = false; m = (m + 1) % mots.length; return setTimeout(tick, 300); }
    setTimeout(tick, 45);
  }

  cible.textContent = '';
  setTimeout(tick, 400);
})();
