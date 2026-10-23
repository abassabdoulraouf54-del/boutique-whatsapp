#!/usr/bin/env bash
BASE="${BASE:-http://localhost:8000}"
SLUG="${SLUG:-boutique}"
OK=0; KO=0

verifier() {
  local attendu="$1" url="$2"
  local code
  code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE$url")
  if [ "$code" = "$attendu" ]; then
    echo "  OK    $code  $url"; OK=$((OK+1))
  else
    echo "  ECHEC $code (attendu $attendu)  $url"; KO=$((KO+1))
  fi
}

PID=$(mariadb --skip-ssl -h 127.0.0.1 -P 3306 -u "${DB_USER:-root}" ${DB_PASS:+-p"$DB_PASS"} boutique_whatsapp -N -e "SELECT id FROM produit WHERE boutique_id=2 AND actif=1 AND stock>0 ORDER BY id LIMIT 1" 2>/dev/null)

echo "== Pages publiques =="
verifier 302 "/"
verifier 200 "/b/$SLUG"
verifier 200 "/b/$SLUG?q=casque"
verifier 200 "/b/$SLUG/panier"
[ -n "$PID" ] && verifier 200 "/b/$SLUG/produit/$PID"
verifier 404 "/b/boutique-inconnue"
verifier 404 "/b/$SLUG/produit/999999"
verifier 302 "/b/$SLUG/commander"
verifier 200 "/login"

echo "== Administration (doit rediriger vers /login sans connexion) =="
for p in /admin /admin/produit /admin/produit/new /admin/categorie /admin/commande /admin/parametres /admin/compte; do
  verifier 302 "$p"
done

echo "== Fichiers =="
verifier 200 "/css/app.css"
verifier 200 "/css/shop.css"
verifier 200 "/css/admin.css"
verifier 404 "/.env"
verifier 404 "/.env.local"
verifier 404 "/composer.json"

echo
echo "Résultat : $OK réussis, $KO en échec"
[ "$KO" -eq 0 ]
