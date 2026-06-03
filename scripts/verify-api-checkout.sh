#!/usr/bin/env bash
# DietWatchers — Pre-deploy API verification (curl)
set -uo pipefail

API="${API:-https://dietdev-ledsvd8q.on-forge.com/api}"
TOKEN="${TOKEN:-}"
PHONE="${PHONE:-563742968}"

PROGRAM_ID="${PROGRAM_ID:-14}"
PLAN_ID="${PLAN_ID:-36}"
PLAN_DURATION_ID="${PLAN_DURATION_ID:-369}"
PLAN_CALORY_ID="${PLAN_CALORY_ID:-1}"

YASMIN_LAT="${YASMIN_LAT:-24.8247}"
YASMIN_LNG="${YASMIN_LNG:-46.6300}"
DISTRICT_ID="${DISTRICT_ID:-}"

if [ -z "$TOKEN" ]; then
  echo "Set TOKEN env var (customer Sanctum token)."; exit 1
fi

AUTH=(-H "Authorization: Bearer ${TOKEN}" -H "Accept: application/json" -H "Content-Type: application/json")
PASS="\033[32m✔\033[0m"; FAIL="\033[31m✗\033[0m"; INFO="\033[36mℹ\033[0m"

hr(){ printf '%.0s—' {1..70}; echo; }
step(){ hr; echo -e "${INFO} STEP $1: $2"; hr; }

step 0 "GET /profile"
code=$(curl -s -o /tmp/dw_profile.json -w "%{http_code}" "${AUTH[@]}" "${API}/profile")
echo "HTTP ${code}"; head -c 600 /tmp/dw_profile.json; echo
if [ "$code" != "200" ]; then echo -e "${FAIL} Invalid token or API domain."; exit 1; fi
echo -e "${PASS} Token valid."

step 1 "GET /districts — find الياسمين"
curl -s "${AUTH[@]}" "${API}/districts" -o /tmp/dw_districts.json
echo "District count: $(grep -o '"id"' /tmp/dw_districts.json | wc -l)"
grep -i 'ياسمين' /tmp/dw_districts.json | head -3 || echo -e "${FAIL} الياسمين not found in active districts"
if [ -z "$DISTRICT_ID" ]; then
  DISTRICT_ID=$(grep -B5 -i 'ياسمين' /tmp/dw_districts.json | grep -o '"id"[[:space:]]*:[[:space:]]*[0-9]*' | head -1 | grep -o '[0-9]*' || true)
  echo "Auto DISTRICT_ID=${DISTRICT_ID}"
fi

step 2 "GET /addresses"
curl -s "${AUTH[@]}" "${API}/addresses" -o /tmp/dw_addr.json
echo "Address count: $(grep -o '"id"' /tmp/dw_addr.json | wc -l)"
head -c 500 /tmp/dw_addr.json; echo

step 3 "POST /addresses (Yasmin test)"
if [ -z "$DISTRICT_ID" ]; then
  echo -e "${FAIL} No DISTRICT_ID — set manually from step 1"; exit 1
fi
ADDR_PAYLOAD=$(cat <<JSON
{"title":"اختبار الياسمين","type":"home","latitude":${YASMIN_LAT},"longitude":${YASMIN_LNG},"description":"اختبار تغطية - الياسمين الرياض","district_id":${DISTRICT_ID}}
JSON
)
code=$(curl -s -o /tmp/dw_addr_new.json -w "%{http_code}" "${AUTH[@]}" -X POST "${API}/addresses" -d "${ADDR_PAYLOAD}")
echo "HTTP ${code}"; head -c 700 /tmp/dw_addr_new.json; echo
if [ "$code" = "200" ] || [ "$code" = "201" ]; then
  NEW_ADDR_ID=$(grep -o '"id"[[:space:]]*:[[:space:]]*[0-9]*' /tmp/dw_addr_new.json | head -1 | grep -o '[0-9]*' || true)
  echo -e "${PASS} API accepted Yasmin. address_id=${NEW_ADDR_ID}"
else
  echo -e "${FAIL} API rejected address save."
fi

step 4 "POST /subscriptions/calculate"
CALC_PAYLOAD=$(cat <<JSON
{"program_id":${PROGRAM_ID},"plan_id":${PLAN_ID},"plan_duration_id":${PLAN_DURATION_ID},"plan_calory_id":${PLAN_CALORY_ID},"with_pickup":false,"with_support":false,"with_weekend":false,"payment_option":"credit_card"}
JSON
)
code=$(curl -s -o /tmp/dw_calc.json -w "%{http_code}" "${AUTH[@]}" -X POST "${API}/subscriptions/calculate" -d "${CALC_PAYLOAD}")
echo "HTTP ${code}"; head -c 900 /tmp/dw_calc.json; echo
if [ "$code" = "200" ]; then
  echo -e "${PASS} calculate OK"
  grep -o '"first_available_date_for_subscription"[[:space:]]*:[[:space:]]*"[^"]*"' /tmp/dw_calc.json || true
else
  echo -e "${FAIL} calculate rejected (HTTP ${code})"
fi

hr
echo -e "${INFO} Summary: STEP3+STEP4 OK => Yasmin served by API; local check was the bug."
hr
