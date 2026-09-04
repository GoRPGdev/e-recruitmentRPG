#!/usr/bin/env bash
# tools/rbac-probe.sh -- cek status HTTP tiap endpoint kunci untuk tiap peran.
# Read-only (GET). Untuk checklist uji per peran di docs/MATRIKS_HAK_AKSES.md.
#
# Prasyarat:
#   1) server dev jalan:  cd web && php -S 127.0.0.1:8899 -t .
#   2) PHP di PATH (atau set $PHP)
#
# Jalan:  bash tools/rbac-probe.sh
# Bikin user uji_<role> (password Uji12345) kalau belum ada, lalu probe.

set -u
B=${BASE_URL:-http://127.0.0.1:8899}
PHP=${PHP:-php}
PW=Uji12345
ROLES=(IT_ADMIN HR_ADMIN HR_SPV USER_DEPT BOD VIEWER)
EP=(dashboard documents requisitions pipeline master flowbuilder import postings \
    "export/candidates" "documents/flow_docs" "requisitions/approve/999" "requisitions/create")

command -v curl >/dev/null || { echo "curl tidak ada"; exit 1; }
curl -s -o /dev/null "$B/" || { echo "server $B tidak menjawab -- jalankan: cd web && php -S 127.0.0.1:8899 -t ."; exit 1; }

for r in "${ROLES[@]}"; do
  u="uji_$(echo "$r" | tr 'A-Z' 'a-z')"
  "$PHP" "$(dirname "$0")/mkuser.php" "$u" "$PW" "$r" >/dev/null 2>&1
  J="/tmp/rbacprobe_$u.txt"; rm -f "$J"
  CSRF=$(curl -s -c "$J" "$B/auth/login" | grep -oE 'name="csrf_test_name" value="[^"]*"' | grep -oE 'value="[^"]*"' | sed 's/value="//;s/"//')
  curl -s -b "$J" -c "$J" -o /dev/null \
    --data-urlencode "csrf_test_name=$CSRF" --data-urlencode "username=$u" --data-urlencode "password=$PW" \
    "$B/auth/login"
done

printf '%-26s' "endpoint \\ role"
for r in "${ROLES[@]}"; do printf '%-11s' "$r"; done
printf '\n'
printf '%.0s-' {1..92}; printf '\n'

for e in "${EP[@]}"; do
  printf '%-26s' "$e"
  for r in "${ROLES[@]}"; do
    u="uji_$(echo "$r" | tr 'A-Z' 'a-z')"
    code=$(curl -s -b "/tmp/rbacprobe_$u.txt" -o /dev/null -w '%{http_code}' "$B/$e")
    printf '%-11s' "$code"
  done
  printf '\n'
done

echo
echo "200=boleh  403=ditolak  404=lolos-guard tapi id tak ada  302=belum login"
