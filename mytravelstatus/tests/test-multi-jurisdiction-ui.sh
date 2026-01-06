#!/bin/bash
#
# Multi-Jurisdiction UI Test Script
#
# This script helps you set up test data and verify the multi-jurisdiction
# features are working correctly in MyTravelStatus.
#
# Usage:
#   1. Update the SITE_URL and AUTH variables below
#   2. Run: chmod +x test-multi-jurisdiction-ui.sh && ./test-multi-jurisdiction-ui.sh
#
# Authentication Options:
#   - Cookie auth: Use browser dev tools to copy your WordPress cookies
#   - Application password: Create one in WordPress > Users > Profile

set -e

# =============================================================================
# CONFIGURATION - Update these values for your site
# =============================================================================

SITE_URL="https://your-site.com"
API_BASE="${SITE_URL}/wp-json/mts/v1"

# Authentication (choose one method):
# Option 1: Cookie-based auth (copy from browser)
# COOKIE="wordpress_logged_in_xxx=value; wordpress_sec_xxx=value"

# Option 2: Application password (WordPress 5.6+)
# Create at: WordPress Admin > Users > Profile > Application Passwords
WP_USER="your-username"
WP_APP_PASSWORD="xxxx xxxx xxxx xxxx xxxx xxxx"

# Build auth header
if [ -n "$WP_APP_PASSWORD" ]; then
    AUTH_HEADER="Authorization: Basic $(echo -n "${WP_USER}:${WP_APP_PASSWORD}" | base64)"
else
    AUTH_HEADER="Cookie: ${COOKIE}"
fi

# =============================================================================
# HELPER FUNCTIONS
# =============================================================================

print_header() {
    echo ""
    echo "========================================"
    echo "$1"
    echo "========================================"
}

api_get() {
    local endpoint="$1"
    curl -s -X GET "${API_BASE}${endpoint}" \
        -H "${AUTH_HEADER}" \
        -H "Content-Type: application/json"
}

api_post() {
    local endpoint="$1"
    local data="$2"
    curl -s -X POST "${API_BASE}${endpoint}" \
        -H "${AUTH_HEADER}" \
        -H "Content-Type: application/json" \
        -d "${data}"
}

api_put() {
    local endpoint="$1"
    local data="$2"
    curl -s -X PUT "${API_BASE}${endpoint}" \
        -H "${AUTH_HEADER}" \
        -H "Content-Type: application/json" \
        -d "${data}"
}

# =============================================================================
# TEST 1: Get Available Jurisdiction Rules
# =============================================================================

print_header "TEST 1: Get Available Jurisdiction Rules"

echo "Fetching all jurisdiction rules..."
RULES=$(api_get "/jurisdictions/rules")
echo "$RULES" | python3 -m json.tool 2>/dev/null || echo "$RULES"

echo ""
echo "Available jurisdiction codes:"
echo "$RULES" | python3 -c "
import json, sys
rules = json.load(sys.stdin)
for r in rules:
    print(f\"  - {r['code']}: {r['name']} ({r['category']})\")
" 2>/dev/null || echo "(Install python3 for formatted output)"

# =============================================================================
# TEST 2: Get Current Tracked Jurisdictions
# =============================================================================

print_header "TEST 2: Get Currently Tracked Jurisdictions"

TRACKED=$(api_get "/jurisdictions/tracked")
echo "$TRACKED" | python3 -m json.tool 2>/dev/null || echo "$TRACKED"

# =============================================================================
# TEST 3: Set Up Multi-Jurisdiction Tracking
# =============================================================================

print_header "TEST 3: Setting Up Multi-Jurisdiction Tracking"

echo "Adding 3 jurisdictions: schengen_visa, uk_srt, ireland_183..."
RESULT=$(api_put "/jurisdictions/tracked" '{"jurisdictions": ["schengen_visa", "uk_srt", "ireland_183"]}')
echo "$RESULT" | python3 -m json.tool 2>/dev/null || echo "$RESULT"

# =============================================================================
# TEST 4: Create Test Trips
# =============================================================================

print_header "TEST 4: Creating Test Trips"

# Calculate dates relative to today
TODAY=$(date +%Y-%m-%d)
YEAR=$(date +%Y)

# Trip 1: Schengen trip (France, 15 days ago for 10 days)
START1=$(date -d "15 days ago" +%Y-%m-%d 2>/dev/null || date -v-15d +%Y-%m-%d)
END1=$(date -d "5 days ago" +%Y-%m-%d 2>/dev/null || date -v-5d +%Y-%m-%d)

echo "Creating Schengen trip: France ${START1} to ${END1}..."
TRIP1=$(api_post "/trips" "{
    \"start_date\": \"${START1}\",
    \"end_date\": \"${END1}\",
    \"country\": \"France\",
    \"category\": \"personal\",
    \"notes\": \"Test trip - Paris vacation\"
}")
echo "$TRIP1" | python3 -m json.tool 2>/dev/null || echo "$TRIP1"

# Trip 2: UK trip (30 days ago for 20 days)
START2=$(date -d "50 days ago" +%Y-%m-%d 2>/dev/null || date -v-50d +%Y-%m-%d)
END2=$(date -d "30 days ago" +%Y-%m-%d 2>/dev/null || date -v-30d +%Y-%m-%d)

echo ""
echo "Creating UK trip: ${START2} to ${END2}..."
TRIP2=$(api_post "/trips" "{
    \"start_date\": \"${START2}\",
    \"end_date\": \"${END2}\",
    \"country\": \"United Kingdom\",
    \"category\": \"business\",
    \"notes\": \"Test trip - London business\"
}")
echo "$TRIP2" | python3 -m json.tool 2>/dev/null || echo "$TRIP2"

# Trip 3: Ireland trip (60 days ago for 7 days)
START3=$(date -d "67 days ago" +%Y-%m-%d 2>/dev/null || date -v-67d +%Y-%m-%d)
END3=$(date -d "60 days ago" +%Y-%m-%d 2>/dev/null || date -v-60d +%Y-%m-%d)

echo ""
echo "Creating Ireland trip: ${START3} to ${END3}..."
TRIP3=$(api_post "/trips" "{
    \"start_date\": \"${START3}\",
    \"end_date\": \"${END3}\",
    \"country\": \"Ireland\",
    \"category\": \"personal\",
    \"notes\": \"Test trip - Dublin vacation\"
}")
echo "$TRIP3" | python3 -m json.tool 2>/dev/null || echo "$TRIP3"

# Trip 4: Another Schengen trip (Spain, 90 days ago for 14 days)
START4=$(date -d "104 days ago" +%Y-%m-%d 2>/dev/null || date -v-104d +%Y-%m-%d)
END4=$(date -d "90 days ago" +%Y-%m-%d 2>/dev/null || date -v-90d +%Y-%m-%d)

echo ""
echo "Creating Schengen trip: Spain ${START4} to ${END4}..."
TRIP4=$(api_post "/trips" "{
    \"start_date\": \"${START4}\",
    \"end_date\": \"${END4}\",
    \"country\": \"Spain\",
    \"category\": \"personal\",
    \"notes\": \"Test trip - Barcelona\"
}")
echo "$TRIP4" | python3 -m json.tool 2>/dev/null || echo "$TRIP4"

# =============================================================================
# TEST 5: Get Multi-Jurisdiction Summary
# =============================================================================

print_header "TEST 5: Multi-Jurisdiction Summary"

echo "Fetching summary for all tracked jurisdictions..."
SUMMARY=$(api_get "/jurisdictions/summary")
echo "$SUMMARY" | python3 -m json.tool 2>/dev/null || echo "$SUMMARY"

# =============================================================================
# TEST 6: Get Compliance Overview
# =============================================================================

print_header "TEST 6: Compliance Overview"

echo "Fetching compliance overview..."
OVERVIEW=$(api_get "/jurisdictions/overview")
echo "$OVERVIEW" | python3 -m json.tool 2>/dev/null || echo "$OVERVIEW"

# =============================================================================
# TEST 7: Get Individual Jurisdiction Summary
# =============================================================================

print_header "TEST 7: Individual Jurisdiction Summaries"

echo "UK SRT Summary:"
UK_SUMMARY=$(api_get "/jurisdictions/uk_srt/summary")
echo "$UK_SUMMARY" | python3 -m json.tool 2>/dev/null || echo "$UK_SUMMARY"

echo ""
echo "Schengen Summary:"
SCHENGEN_SUMMARY=$(api_get "/jurisdictions/schengen_visa/summary")
echo "$SCHENGEN_SUMMARY" | python3 -m json.tool 2>/dev/null || echo "$SCHENGEN_SUMMARY"

# =============================================================================
# TEST 8: Passport Control Mode
# =============================================================================

print_header "TEST 8: Passport Control Mode"

echo "Fetching passport control data..."
PASSPORT=$(api_get "/passport-control")
echo "$PASSPORT" | python3 -m json.tool 2>/dev/null || echo "$PASSPORT"

# =============================================================================
# SUMMARY
# =============================================================================

print_header "TEST COMPLETE"

echo "
Multi-jurisdiction test data has been set up!

To verify the UI:
1. Visit your site's MyTravelStatus dashboard
2. You should see the 'Multi-Jurisdiction Compliance' section
3. Three jurisdiction cards should appear:
   - Schengen 90/180 (with France & Spain trips)
   - UK Statutory Residence Test (with UK trip)
   - Ireland 183-Day Rule (with Ireland trip)

4. Each card shows:
   - Days used / allowed
   - Progress bar with status color
   - Status badge (ok/warning/critical/exceeded)

5. Click 'Manage Tracked Rules' to open the jurisdiction picker

6. The old single Schengen section is hidden when 2+ jurisdictions are tracked

If something isn't working:
- Check browser console for JavaScript errors
- Verify the plugin is activated and updated
- Check that trips were created successfully above
- Clear any caches (WordPress, browser, CDN)
"

# =============================================================================
# CLEANUP OPTION
# =============================================================================

echo ""
echo "To remove test trips, you can delete them via:"
echo "  - WordPress Admin > MyTravelStatus > Trips"
echo "  - Or run: api_delete \"/trips/{trip_id}\""
echo ""
