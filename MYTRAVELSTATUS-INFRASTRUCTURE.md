# MyTravelStatus.com - Infrastructure Blueprint

**Product**: MyTravelStatus - Track your visa, tax, and residency days worldwide
**Stack**: WordPress + React SPA + iOS/Android Native Apps
**Last Updated**: December 29, 2025

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Hosting Setup (Cloudways)](#2-hosting-setup-cloudways)
3. [Domain & DNS Configuration](#3-domain--dns-configuration)
4. [WordPress Configuration](#4-wordpress-configuration)
5. [Performance Optimization](#5-performance-optimization)
6. [Security Hardening](#6-security-hardening)
7. [Email & Notifications](#7-email--notifications)
8. [Monitoring & Alerts](#8-monitoring--alerts)
9. [Backup Strategy](#9-backup-strategy)
10. [Development Workflow](#10-development-workflow)
11. [Scaling Checklist](#11-scaling-checklist)
12. [Cost Breakdown](#12-cost-breakdown)

---

## 1. Architecture Overview

```
                                    ┌─────────────────┐
                                    │   Cloudflare    │
                                    │   (CDN + WAF)   │
                                    └────────┬────────┘
                                             │
                    ┌────────────────────────┼────────────────────────┐
                    │                        │                        │
                    ▼                        ▼                        ▼
            ┌───────────────┐      ┌─────────────────┐      ┌─────────────────┐
            │  iOS App      │      │  Web Browser    │      │  Android App    │
            │  (SwiftUI)    │      │  (React SPA)    │      │  (Compose)      │
            └───────┬───────┘      └────────┬────────┘      └────────┬────────┘
                    │                       │                        │
                    └───────────────────────┼────────────────────────┘
                                            │
                                            ▼
                              ┌──────────────────────────┐
                              │      Cloudways VPS       │
                              │   (DigitalOcean 2-4GB)   │
                              │                          │
                              │  ┌────────────────────┐  │
                              │  │     WordPress      │  │
                              │  │  ┌──────────────┐  │  │
                              │  │  │ REST API     │  │  │
                              │  │  │ /r2f-schengen│  │  │
                              │  │  │ /framt/v1    │  │  │
                              │  │  └──────────────┘  │  │
                              │  │  ┌──────────────┐  │  │
                              │  │  │ MemberPress  │  │  │
                              │  │  │ (Billing)    │  │  │
                              │  │  └──────────────┘  │  │
                              │  │  ┌──────────────┐  │  │
                              │  │  │ React Portal │  │  │
                              │  │  │ (SPA)        │  │  │
                              │  │  └──────────────┘  │  │
                              │  └────────────────────┘  │
                              │                          │
                              │  ┌────────────────────┐  │
                              │  │   Redis Cache      │  │
                              │  └────────────────────┘  │
                              │                          │
                              │  ┌────────────────────┐  │
                              │  │   MySQL 8.0        │  │
                              │  └────────────────────┘  │
                              └──────────────────────────┘
                                            │
                              ┌─────────────┴─────────────┐
                              │                           │
                              ▼                           ▼
                    ┌─────────────────┐         ┌─────────────────┐
                    │    SendGrid     │         │    OneSignal    │
                    │  (Trans Email)  │         │  (Push Notify)  │
                    └─────────────────┘         └─────────────────┘
```

---

## 2. Hosting Setup (Cloudways)

### 2.1 Account & Server Creation

**Step 1: Sign up at Cloudways**
- URL: https://www.cloudways.com
- Use promo code for first month discount (search current codes)

**Step 2: Launch Server**
```
Provider:        DigitalOcean
Server Size:     2GB RAM ($28/mo) - Start here
                 4GB RAM ($54/mo) - Upgrade when needed
Server Location: Choose based on primary user base:
                 - NYC or SFO for US users
                 - London or Frankfurt for EU users
                 - Singapore for APAC users
PHP Version:     8.1 or 8.2
MySQL Version:   8.0
```

**Step 3: Create Application**
```
Application:     WordPress (Clean Install)
App Name:        mytravelstatus-production
Project:         MyTravelStatus
```

### 2.2 Server Settings (Cloudways Panel)

Navigate to **Server → Settings & Packages**

**PHP Settings:**
```
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
max_input_vars = 5000
post_max_size = 64M
upload_max_filesize = 64M
```

**MySQL Settings:**
```
max_connections = 500
wait_timeout = 60
innodb_buffer_pool_size = 512M  (for 2GB server)
                          1G    (for 4GB server)
```

### 2.3 Enable Services

**In Cloudways Panel → Server → Manage Services:**

| Service | Status | Notes |
|---------|--------|-------|
| Varnish | ON | HTTP cache (configure bypass rules) |
| Redis | ON | Object cache |
| Memcached | OFF | Not needed with Redis |
| Elasticsearch | OFF | Not needed initially |

---

## 3. Domain & DNS Configuration

### 3.1 Domain Setup

**Registrar**: Cloudflare Registrar (recommended) or Namecheap

**Primary Domain**: `mytravelstatus.com`

### 3.2 Cloudflare Configuration

**Step 1: Add Site to Cloudflare**
- Sign up at cloudflare.com (free plan works)
- Add mytravelstatus.com
- Update nameservers at registrar

**Step 2: DNS Records**
```
Type    Name              Content                    Proxy
─────────────────────────────────────────────────────────────
A       mytravelstatus.com   [Cloudways Server IP]    Proxied (orange)
A       www                  [Cloudways Server IP]    Proxied (orange)
A       api                  [Cloudways Server IP]    Proxied (orange)
CNAME   mail                 sendgrid.net             DNS only (gray)
TXT     @                    v=spf1 include:sendgrid.net ~all
TXT     _dmarc               v=DMARC1; p=quarantine; ...
```

**Step 3: SSL/TLS Settings**
```
Encryption Mode:     Full (strict)
Always Use HTTPS:    ON
Minimum TLS:         TLS 1.2
Automatic HTTPS:     ON
```

**Step 4: Caching Rules**
```
# Page Rules (3 free):

1. *mytravelstatus.com/wp-admin/*
   Cache Level: Bypass
   Disable Performance

2. *mytravelstatus.com/wp-json/*
   Cache Level: Bypass
   (API endpoints must not be cached)

3. *mytravelstatus.com/wp-content/uploads/*
   Cache Level: Cache Everything
   Edge Cache TTL: 1 month
```

**Step 5: Firewall Rules (Security → WAF)**
```
# Block common attacks (free tier)
Managed Rules: ON
OWASP Core Ruleset: ON (if available)

# Rate limiting for API
Rule: (http.request.uri.path contains "/wp-json/r2f-schengen/")
Action: Rate limit to 100 requests/minute per IP
```

### 3.3 Cloudways Domain Setup

In Cloudways Panel → Application → Domain Management:
1. Add `mytravelstatus.com` as primary domain
2. Add `www.mytravelstatus.com`
3. Enable Let's Encrypt SSL (auto-renews)

---

## 4. WordPress Configuration

### 4.1 Initial Setup

**wp-config.php additions** (via Cloudways Application Settings):

```php
// Performance
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// Security
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', true);

// Caching
define('WP_CACHE', true);

// API Performance
define('REST_REQUEST', true);

// Debug (only in development)
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
```

### 4.2 Required Plugins

**Install via WordPress Admin:**

| Plugin | Purpose | Settings |
|--------|---------|----------|
| **MemberPress** | Subscriptions & billing | Connect Stripe |
| **Redis Object Cache** | Object caching | Enable in settings |
| **WP Mail SMTP** | Transactional email | Connect SendGrid |
| **Wordfence Security** | Security & firewall | Enable WAF |
| **UpdraftPlus** | Backups | Configure S3/Dropbox |

**Your Custom Plugins (upload via SFTP):**
- `relo2france-schengen-tracker/` - Main tracking plugin
- `france-relocation-member-tools/` - React portal plugin

### 4.3 Permalink Structure

Settings → Permalinks:
```
Custom Structure: /%postname%/
```

### 4.4 REST API Configuration

**Verify endpoints work:**
```bash
# Test public endpoint
curl https://mytravelstatus.com/wp-json/r2f-schengen/v1/app/status

# Expected response:
{
  "min_app_version": "1.0.0",
  "latest_app_version": "1.0.0",
  "force_update": false,
  "maintenance_mode": false,
  "server_time": "2025-12-29T12:00:00Z"
}
```

---

## 5. Performance Optimization

### 5.1 Varnish Cache Configuration

**Cloudways → Application → Varnish Settings**

Add exclusions (URLs that should NOT be cached):
```
/wp-admin/.*
/wp-login.php
/wp-json/.*
/cart/.*
/checkout/.*
/my-account/.*
/portal/.*
/api/.*
```

### 5.2 Redis Object Cache

**Step 1: Install "Redis Object Cache" plugin**

**Step 2: Enable in Settings → Redis**

**Step 3: Verify connection:**
```bash
# SSH into server
redis-cli ping
# Should return: PONG

redis-cli info stats | grep hits
# Should show increasing hit rate
```

### 5.3 Database Optimization

**Add to wp-config.php:**
```php
// Limit post revisions
define('WP_POST_REVISIONS', 5);

// Auto-empty trash
define('EMPTY_TRASH_DAYS', 7);
```

**Weekly maintenance (via WP-CLI cron):**
```bash
# Add to Cloudways Cron Jobs
0 3 * * 0 cd /home/master/applications/[app]/public_html && wp db optimize --quiet
0 4 * * 0 cd /home/master/applications/[app]/public_html && wp transient delete --expired --quiet
```

### 5.4 Image Optimization

**For user uploads (passport photos, etc.):**
- Install "ShortPixel Image Optimizer" or "Imagify"
- Set to lossy compression, 80% quality
- Enable WebP conversion

### 5.5 API Response Optimization

**In your plugin code, implement transient caching:**
```php
// Example: Cache Schengen summary for 5 minutes
public function get_schengen_summary( $user_id ) {
    $cache_key = 'schengen_summary_' . $user_id;
    $cached = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    $summary = $this->calculate_summary( $user_id );
    set_transient( $cache_key, $summary, 5 * MINUTE_IN_SECONDS );

    return $summary;
}
```

---

## 6. Security Hardening

### 6.1 Wordfence Configuration

**Firewall → All Firewall Options:**
```
Web Application Firewall Status: Enabled and Protecting
Protection Level: Extended Protection
Real-Time IP Blocklist: Enabled
```

**Brute Force Protection:**
```
Lock out after: 5 failed attempts
Lock out duration: 1 hour
Immediately block: Invalid usernames
```

**Rate Limiting:**
```
Throttle: Users making more than 240 requests/minute
Block: Users making more than 360 requests/minute
```

### 6.2 Additional Security Headers

**Add to .htaccess:**
```apache
# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(self), camera=(), microphone=()"
</IfModule>

# Block access to sensitive files
<FilesMatch "^(wp-config\.php|readme\.html|license\.txt|xmlrpc\.php)$">
    Require all denied
</FilesMatch>
```

### 6.3 API Authentication Security

**Ensure your API enforces:**
```php
// In your API permission callbacks
public function check_permission() {
    // Require authentication
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_forbidden',
            __( 'Authentication required.', 'mytravelstatus' ),
            array( 'status' => 401 )
        );
    }

    // Rate limiting per user
    $user_id = get_current_user_id();
    $rate_key = 'api_requests_' . $user_id;
    $requests = get_transient( $rate_key ) ?: 0;

    if ( $requests > 100 ) { // 100 requests per minute
        return new WP_Error(
            'rate_limit_exceeded',
            __( 'Too many requests. Please wait.', 'mytravelstatus' ),
            array( 'status' => 429 )
        );
    }

    set_transient( $rate_key, $requests + 1, MINUTE_IN_SECONDS );

    return true;
}
```

### 6.4 Secure User Data

**Encryption for sensitive fields:**
```php
// Store location data encrypted
$encrypted = openssl_encrypt(
    json_encode( $location_data ),
    'AES-256-CBC',
    SECURE_AUTH_KEY,
    0,
    substr( SECURE_AUTH_SALT, 0, 16 )
);
update_user_meta( $user_id, 'fra_encrypted_locations', $encrypted );
```

---

## 7. Email & Notifications

### 7.1 SendGrid Setup (Transactional Email)

**Step 1: Create SendGrid Account**
- URL: https://sendgrid.com
- Free tier: 100 emails/day (enough to start)

**Step 2: API Key**
- Settings → API Keys → Create API Key
- Permissions: Mail Send only
- Save key securely

**Step 3: Configure WP Mail SMTP Plugin**
```
Mailer:         SendGrid
API Key:        [your-api-key]
From Email:     noreply@mytravelstatus.com
From Name:      MyTravelStatus
```

**Step 4: DNS Authentication (in Cloudflare)**
Add records provided by SendGrid for:
- Domain authentication (CNAME records)
- Link branding (CNAME records)

### 7.2 Email Types

| Email Type | Trigger | Template |
|------------|---------|----------|
| Welcome | User registration | welcome.html |
| Threshold Warning | 60+ days used | threshold-warning.html |
| Threshold Danger | 80+ days used | threshold-danger.html |
| Trip Confirmation | Manual trip added | trip-confirmed.html |
| Subscription Receipt | MemberPress payment | (built-in) |

### 7.3 OneSignal Push Notifications

**Step 1: Create OneSignal Account**
- URL: https://onesignal.com
- Free tier: Unlimited push

**Step 2: Create Apps**
- iOS App (needs Apple Push Certificate)
- Android App (needs Firebase Server Key)
- Web Push (for PWA)

**Step 3: Configure WordPress Plugin**
Install "OneSignal Push Notifications" and configure with App ID.

**Step 4: Trigger notifications from PHP:**
```php
// Example: Send threshold warning
function send_threshold_notification( $user_id, $days_used ) {
    $player_id = get_user_meta( $user_id, 'onesignal_player_id', true );

    if ( ! $player_id ) {
        return;
    }

    $response = wp_remote_post( 'https://onesignal.com/api/v1/notifications', array(
        'headers' => array(
            'Authorization' => 'Basic ' . ONESIGNAL_REST_API_KEY,
            'Content-Type'  => 'application/json',
        ),
        'body' => json_encode( array(
            'app_id'             => ONESIGNAL_APP_ID,
            'include_player_ids' => array( $player_id ),
            'headings'           => array( 'en' => 'Schengen Alert' ),
            'contents'           => array( 'en' => "You've used {$days_used} of 90 days" ),
            'data'               => array( 'type' => 'threshold_warning' ),
        ) ),
    ) );
}
```

---

## 8. Monitoring & Alerts

### 8.1 Uptime Monitoring

**Use UptimeRobot (free tier):**
- URL: https://uptimerobot.com
- Monitors: 50 free

**Create Monitors:**
```
1. Website
   Type: HTTP(s)
   URL: https://mytravelstatus.com
   Interval: 5 minutes

2. API Status
   Type: HTTP(s)
   URL: https://mytravelstatus.com/wp-json/r2f-schengen/v1/app/status
   Interval: 5 minutes
   Keyword: "min_app_version"

3. Login Page
   Type: HTTP(s)
   URL: https://mytravelstatus.com/wp-login.php
   Interval: 5 minutes
```

### 8.2 Error Logging

**Cloudways → Application → Logs:**
- Access Logs: For traffic analysis
- Error Logs: For debugging

**Download logs periodically or set up log forwarding.**

### 8.3 Performance Monitoring

**New Relic (optional, has free tier):**
- Install New Relic PHP agent via Cloudways
- Monitor: Response times, throughput, errors

**Query Monitor Plugin (for development):**
- Shows slow queries, API calls, hooks
- Disable in production

### 8.4 Alert Configuration

**UptimeRobot Alerts:**
- Email: your-email@example.com
- Slack webhook (optional)
- SMS (paid feature)

**Wordfence Alerts:**
- Enable email for: Blocked attacks, Login lockouts, Plugin updates needed

---

## 9. Backup Strategy

### 9.1 Cloudways Built-in Backups

**Server → Backup and Restore:**
```
Backup Frequency:   Daily
Backup Retention:   7 days (default)
Backup Time:        3:00 AM server time
```

### 9.2 UpdraftPlus Off-site Backups

**Settings → UpdraftPlus:**
```
Files backup schedule:    Daily, retain 7
Database backup schedule: Daily, retain 14
Remote storage:           Amazon S3 or Dropbox

Include in files:
  ✓ Plugins
  ✓ Themes
  ✓ Uploads
  ✓ Other (wp-content)

Exclude from backups:
  - /wp-content/cache/*
  - /wp-content/updraft/*
```

### 9.3 Database Export (Additional Safety)

**Weekly full export via WP-CLI cron:**
```bash
# Add to Cloudways Cron
0 2 * * 0 cd /home/master/applications/[app]/public_html && \
  wp db export - | gzip > ~/backups/db-$(date +\%Y\%m\%d).sql.gz
```

### 9.4 Disaster Recovery Procedure

```
1. Restore from Cloudways backup (fastest)
   - Server → Backup and Restore → Restore

2. If Cloudways unavailable:
   - Spin up new server
   - Restore from UpdraftPlus (S3/Dropbox)

3. DNS failover:
   - Update Cloudflare A record to new server IP
   - TTL is 5 minutes, so propagation is fast
```

---

## 10. Development Workflow

### 10.1 Environment Setup

```
Production:  mytravelstatus.com (Cloudways)
Staging:     staging.mytravelstatus.com (Cloudways clone)
Local:       mytravelstatus.local (LocalWP or DDEV)
```

**Cloudways Staging:**
1. Application → Clone Application
2. Name: mytravelstatus-staging
3. Creates separate database and files

### 10.2 Deployment Process

**For Plugin Updates:**
```bash
# 1. Test locally
cd france-relocation-assistant
npm run build  # if React changes

# 2. Deploy to staging via SFTP
sftp user@staging-server
put -r relo2france-schengen-tracker /public_html/wp-content/plugins/

# 3. Test on staging
# 4. Deploy to production via SFTP or Git
```

**For React Portal Updates:**
```bash
# 1. Build production bundle
cd france-relocation-member-tools/portal
npm run build

# 2. Upload to server
# Built files are in assets/portal/
```

### 10.3 Git Workflow

```
main           → Production (protected)
staging        → Staging environment
feature/*      → New features
fix/*          → Bug fixes
```

### 10.4 Database Migrations

**When adding new tables:**
```php
// In your plugin activation hook
register_activation_hook( __FILE__, 'mytravelstatus_activate' );

function mytravelstatus_activate() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mytravelstatus_trips (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        start_date date NOT NULL,
        end_date date NOT NULL,
        country varchar(2) NOT NULL,
        jurisdiction varchar(50) DEFAULT 'schengen',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY jurisdiction (jurisdiction)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'mytravelstatus_db_version', '1.0.0' );
}
```

---

## 11. Scaling Checklist

### When to Upgrade

| Metric | Threshold | Action |
|--------|-----------|--------|
| CPU Usage | Consistently > 70% | Upgrade server |
| Memory Usage | Consistently > 80% | Upgrade server |
| Response Time | > 500ms average | Optimize queries, add cache |
| Users | > 5,000 | Consider 4GB server |
| Users | > 20,000 | Consider dedicated DB |
| API Calls | > 100K/day | Add Redis queue for async |

### Scaling Steps

**Level 1: Optimize (0-5K users)**
```
✓ Redis object cache
✓ Cloudflare CDN
✓ Varnish page cache (non-API)
✓ Database query optimization
✓ Image compression
```

**Level 2: Upgrade (5K-20K users)**
```
□ Upgrade to 4GB Cloudways server
□ Upgrade to 8GB if needed
□ Add database indexing for custom tables
□ Implement API response caching
```

**Level 3: Separate (20K+ users)**
```
□ Move to Cloudways Managed Database (separate)
□ Add second application server
□ Consider load balancer
□ Implement job queue for heavy operations
```

**Level 4: Full Scale (50K+ users)**
```
□ Migrate to AWS/GCP with auto-scaling
□ Separate API service (Node/Go)
□ Implement microservices architecture
□ Add read replicas for database
```

---

## 12. Cost Breakdown

### Monthly Costs (Starting)

| Service | Plan | Cost |
|---------|------|------|
| Cloudways (DO 2GB) | Production | $28 |
| Cloudways (DO 1GB) | Staging | $14 |
| Cloudflare | Free | $0 |
| SendGrid | Free (100/day) | $0 |
| OneSignal | Free | $0 |
| UptimeRobot | Free | $0 |
| MemberPress | License | $15/mo amortized |
| Domain | .com | $1/mo amortized |
| **Total** | | **~$58/mo** |

### Monthly Costs (Growth - 10K users)

| Service | Plan | Cost |
|---------|------|------|
| Cloudways (DO 4GB) | Production | $54 |
| Cloudways (DO 2GB) | Staging | $28 |
| Cloudflare | Pro | $20 |
| SendGrid | Essentials | $20 |
| OneSignal | Free | $0 |
| UptimeRobot | Pro | $7 |
| UpdraftPlus | Premium | $6/mo |
| **Total** | | **~$135/mo** |

### Revenue Target for Sustainability

```
Break-even at $58/mo:
- 2 users at $39/yr ($6.50/mo) = $13/mo
- Need ~5 paying users minimum

Growth costs at $135/mo:
- Need ~21 users at $79/yr tier
- Or ~42 users at $39/yr tier

Target: 500 users @ average $59/yr = $2,458/mo revenue
                                     - $135/mo costs
                                     = $2,323/mo profit
```

---

## Quick Reference Commands

### WP-CLI Useful Commands
```bash
# SSH into server first
cd /home/master/applications/[app]/public_html

# Clear all caches
wp cache flush
wp transient delete --all

# Check database
wp db check
wp db optimize

# User management
wp user list --role=subscriber
wp user meta get [user_id] fra_profile_first_name

# Plugin management
wp plugin list
wp plugin update --all

# Search/replace (for migrations)
wp search-replace 'old-domain.com' 'mytravelstatus.com' --dry-run
```

### Server Access
```bash
# SFTP
sftp master@[server-ip] -p [port]

# SSH (Cloudways)
ssh master@[server-ip] -p [port]

# MySQL
mysql -u [db_user] -p [db_name]
```

---

## Checklist: Go-Live

**Pre-Launch:**
- [ ] Domain configured in Cloudways
- [ ] SSL certificate active
- [ ] Cloudflare proxying enabled
- [ ] WordPress permalinks set
- [ ] MemberPress configured with Stripe
- [ ] SendGrid verified and sending
- [ ] Redis enabled and connected
- [ ] Varnish exclusions set for API
- [ ] Security plugins configured
- [ ] Backups running and verified
- [ ] Uptime monitors active
- [ ] Test user registration flow
- [ ] Test subscription purchase
- [ ] Test mobile app login
- [ ] Test API endpoints

**Post-Launch (First Week):**
- [ ] Monitor error logs daily
- [ ] Check email deliverability
- [ ] Verify backup completion
- [ ] Monitor response times
- [ ] Check Cloudflare analytics
- [ ] Review Wordfence security scans

---

*This document should be updated as infrastructure evolves. Keep credentials in a secure password manager, not in this document.*
