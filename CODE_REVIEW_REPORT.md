# Code Review Report: class-framt-portal-api.php

**File:** `/home/user/france-relocation-assistant/france-relocation-member-tools/includes/class-framt-portal-api.php`
**Review Date:** 2025-12-18
**Focus:** Lines 2148+ (new methods) with full file context

---

## 🔴 CRITICAL ISSUES (Must Fix Immediately)

### 1. SQL Injection Vulnerability - Table Name in SHOW TABLES
**Lines:** 2872, 2938, 2999
**Severity:** CRITICAL 🔴

```php
// VULNERABLE CODE
$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;
```

**Issue:** Table name is interpolated directly into SQL without proper escaping. While `$table` comes from `FRAMT_Portal_Schema::get_table()`, this is still dangerous.

**Fix:**
```php
// Use $wpdb->prepare() with %s placeholder
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
```

**Impact:** Potential SQL injection if `get_table()` method is ever compromised or modified.

---

### 2. Unsafe WHERE Clause Construction
**Lines:** 1509-1511
**Severity:** CRITICAL 🔴

```php
// VULNERABLE CODE
$sql = $wpdb->prepare(
    "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . " ORDER BY created_at DESC",
    $values
);
```

**Issue:** Table name inserted directly without escaping. While unlikely to be exploited, WordPress coding standards require `$wpdb->prepare()` for ALL dynamic SQL.

**Fix:**
```php
// Better approach - validate table name comes from trusted source
$table = FRAMT_Portal_Schema::get_table( 'files' );
// Table name is now from a controlled source, but still add validation
if ( ! preg_match( '/^[a-z0-9_]+$/i', $table ) ) {
    return new WP_Error( 'invalid_table', 'Invalid table name' );
}
```

---

### 3. Missing Array Sanitization in Profile Update
**Lines:** 2299-2304
**Severity:** HIGH 🔴

```php
// VULNERABLE CODE
if ( isset( $params['dependents'] ) && is_array( $params['dependents'] ) ) {
    update_user_meta( $user_id, 'fra_dependents', $params['dependents'] );
}
if ( isset( $params['previous_visas'] ) && is_array( $params['previous_visas'] ) ) {
    update_user_meta( $user_id, 'fra_previous_visas', $params['previous_visas'] );
}
```

**Issue:** Arrays stored directly without sanitizing nested values. Could store malicious scripts.

**Fix:**
```php
// Sanitize array deeply
if ( isset( $params['dependents'] ) && is_array( $params['dependents'] ) ) {
    $sanitized_dependents = array_map( function( $dependent ) {
        return is_array( $dependent )
            ? array_map( 'sanitize_text_field', $dependent )
            : sanitize_text_field( $dependent );
    }, $params['dependents'] );
    update_user_meta( $user_id, 'fra_dependents', $sanitized_dependents );
}
```

---

### 4. Loose Type Comparisons in Permission Checks
**Lines:** 3004, 3017, 3831, 3861, 3891, 1875, 1476
**Severity:** MEDIUM-HIGH 🟡

```php
// PROBLEMATIC CODE
if ( ! $doc || $doc->user_id != get_current_user_id() ) // Line 3004
if ( $subscription->user_id != $user_id && ! current_user_can( 'manage_options' ) ) // Line 3831
```

**Issue:** Using `!=` instead of `!==` can lead to type juggling vulnerabilities.

**Fix:**
```php
// Use strict comparison
if ( ! $doc || (int) $doc->user_id !== get_current_user_id() )
if ( (int) $subscription->user_id !== $user_id && ! current_user_can( 'manage_options' ) )
```

---

## ⚠️ WARNINGS (Should Fix)

### 5. Non-Yoda Conditions Throughout File
**Lines:** Multiple occurrences throughout
**Severity:** MEDIUM 🟡

**Examples:**
```php
// Line 2268 - NOT Yoda
if ( count( $user_data ) > 1 ) {

// Line 2381 - NOT Yoda
'percentage' => $cat_total > 0 ? round( ( $cat_filled / $cat_total ) * 100 ) : 0,

// Line 2470 - NOT Yoda
$checklist['percentage'] = count( $items ) > 0 ? round( ( $completed / count( $items ) ) * 100 ) : 0;
```

**WordPress Standard (Yoda):**
```php
if ( 1 < count( $user_data ) ) {
'percentage' => 0 < $cat_total ? round( ( $cat_filled / $cat_total ) * 100 ) : 0,
```

**Impact:** Not WPCS compliant. wpcs would flag these.

---

### 6. Insufficient Sanitization for Document Content
**Lines:** 2293, 2536
**Severity:** MEDIUM 🟡

```php
// Line 2293
$value = is_array( $params[ $field ] ) ? $params[ $field ] : sanitize_text_field( $params[ $field ] );

// Line 2536
$progress[ $item_id ]['notes'] = sanitize_textarea_field( $params['notes'] );
```

**Issue:**
- Arrays are not sanitized at all (line 2293)
- `sanitize_textarea_field()` might not be sufficient for rich text notes

**Fix:**
```php
// For line 2293
$value = is_array( $params[ $field ] )
    ? $this->sanitize_array_recursive( $params[ $field ] )
    : sanitize_text_field( $params[ $field ] );

// For line 2536 (if notes support HTML)
$progress[ $item_id ]['notes'] = wp_kses_post( $params['notes'] );
```

---

### 7. Missing Nonce Verification
**All POST/PUT/DELETE Endpoints**
**Severity:** MEDIUM 🟡

**Issue:** REST API endpoints don't verify nonces. While REST API uses cookies and has built-in CSRF protection, additional nonce verification is recommended for sensitive operations.

**Recommendation:**
```php
// Add to permission callbacks for destructive operations
public function check_member_permission_with_nonce( $request ) {
    $base = $this->check_member_permission();
    if ( is_wp_error( $base ) ) {
        return $base;
    }

    // Verify nonce for state-changing operations
    $nonce = $request->get_header( 'X-WP-Nonce' );
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_Error(
            'rest_cookie_invalid_nonce',
            __( 'Cookie nonce is invalid.' ),
            array( 'status' => 403 )
        );
    }

    return true;
}
```

---

### 8. Unescaped Data in Document Generation
**Lines:** 3087-3091
**Severity:** MEDIUM 🟡

```php
// Line 3087
$data['date'] = date_i18n( 'F j, Y' );

foreach ( $data as $key => $value ) {
    $template = str_replace( '{' . $key . '}', $value, $template );
    $template = str_replace( '[' . $key . ']', $value, $template );
}
```

**Issue:** User-provided data inserted into templates without escaping. Could inject malicious content into generated documents.

**Fix:**
```php
$data['date'] = date_i18n( 'F j, Y' );

foreach ( $data as $key => $value ) {
    // Escape for plain text documents
    $escaped_value = esc_html( $value );
    $template = str_replace( '{' . $key . '}', $escaped_value, $template );
    $template = str_replace( '[' . $key . ']', $escaped_value, $template );
}
```

---

## 🔵 PERFORMANCE ISSUES

### 9. N+1 Query Problem in format_note_response
**Lines:** 2130, 2917 (when used in loops)
**Severity:** HIGH 🟡

```php
// INEFFICIENT CODE - Called for each note
private function format_note_response( $note ) {
    $user = get_userdata( $note->user_id ); // Database query for each note!
    // ...
}
```

**Impact:** If 50 notes are fetched, this creates 50+ separate queries.

**Fix:**
```php
// In get_notes() method - prefetch all users
public function get_notes( $request ) {
    // ... existing code to get $notes ...

    // Prefetch all unique users
    $user_ids = array_unique( array_map( function( $note ) {
        return $note->user_id;
    }, $notes ) );

    // Prime user cache
    if ( ! empty( $user_ids ) ) {
        cache_users( $user_ids );
    }

    $formatted = array_map( array( $this, 'format_note_response' ), $notes );
    return rest_ensure_response( $formatted );
}
```

---

### 10. Repeated get_user_meta Calls in get_checklists
**Lines:** 2458-2473
**Severity:** MEDIUM 🟡

```php
// INEFFICIENT - get_user_meta called 6 times in loop
foreach ( $checklist_types as $type => $checklist ) {
    $progress = get_user_meta( $user_id, 'fra_checklist_' . $type, true ) ?: array();
    $items    = $this->get_checklist_items( $type );
    // ...
}
```

**Fix:**
```php
// Prefetch all checklist progress at once
$all_progress = array();
foreach ( array_keys( $checklist_types ) as $type ) {
    $all_progress[ $type ] = get_user_meta( $user_id, 'fra_checklist_' . $type, true ) ?: array();
}

foreach ( $checklist_types as $type => $checklist ) {
    $progress = $all_progress[ $type ];
    $items    = $this->get_checklist_items( $type );
    // ...
}
```

---

### 11. No Caching for Static/Expensive Data
**Lines:** 3185-3243 (glossary), 2555-2605 (checklists)
**Severity:** LOW 🔵

**Issue:** Large hardcoded arrays rebuilt on every request.

**Fix:**
```php
private function get_glossary_data() {
    // Cache for 1 hour
    $cache_key = 'fra_glossary_data_v1';
    $glossary = wp_cache_get( $cache_key );

    if ( false === $glossary ) {
        $glossary = array(
            // ... existing array ...
        );
        wp_cache_set( $cache_key, $glossary, '', HOUR_IN_SECONDS );
    }

    return $glossary;
}
```

---

## 📋 CLEAN CODE ISSUES

### 12. Functions Exceeding Recommended Length
**Severity:** MEDIUM 🟡

| Function | Lines | Line Numbers | Recommendation |
|----------|-------|--------------|----------------|
| `upload_file()` | 112 | 1544-1656 | **Refactor required** - Break into smaller methods |
| `generate_document()` | 82 | 2841-2923 | Refactor - Separate concerns |
| `get_profile_completion()` | 76 | 2318-2394 | Refactor - Extract calculation logic |
| `get_checklists()` | 70 | 2406-2476 | Refactor - Extract progress calculation |
| `update_member_profile()` | 61 | 2249-2310 | Refactor - Separate user and meta updates |

**Example Refactoring for upload_file():**
```php
public function upload_file( $request ) {
    $validation = $this->validate_file_upload( $request );
    if ( is_wp_error( $validation ) ) {
        return $validation;
    }

    $file_data = $this->process_file_upload( $request, $validation );
    if ( is_wp_error( $file_data ) ) {
        return $file_data;
    }

    $file_id = $this->save_file_to_database( $file_data );
    $this->log_file_upload_activity( $file_data );

    return rest_ensure_response( $this->format_file_response( $file_data ) );
}

private function validate_file_upload( $request ) { /* ... */ }
private function process_file_upload( $request, $validation ) { /* ... */ }
private function save_file_to_database( $file_data ) { /* ... */ }
```

---

### 13. Code Duplication - Table Existence Checks
**Lines:** 2872, 2938, 2999
**Severity:** MEDIUM 🟡

```php
// DUPLICATED 3 times
$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;
```

**Fix:**
```php
// Add helper method
private function table_exists( $table_name ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;
}

// Usage
if ( $this->table_exists( $table ) ) {
    // ...
}
```

---

### 14. Magic Numbers
**Lines:** 1587, 3611
**Severity:** LOW 🔵

```php
// Line 1587
if ( $uploaded_file['size'] > 10 * 1024 * 1024 ) {

// Line 3611
if ( count( $history ) > 100 ) {
```

**Fix:**
```php
// Add class constants
const MAX_UPLOAD_SIZE = 10485760; // 10MB in bytes
const MAX_CHAT_HISTORY = 100;

// Usage
if ( $uploaded_file['size'] > self::MAX_UPLOAD_SIZE ) {
if ( count( $history ) > self::MAX_CHAT_HISTORY ) {
```

---

### 15. Missing Input Validation
**Lines:** Multiple
**Severity:** MEDIUM 🟡

**Examples:**
```php
// Line 2807 - No validation that 'type' is valid
$type = $params['type'] ?? '';

// Line 2846 - No validation
$type = $params['type'] ?? '';

// Line 3138 - No sanitization before strtolower
$query = strtolower( $request->get_param( 'q' ) ?? '' );
```

**Fix:**
```php
// Validate document type
$type = $params['type'] ?? '';
$valid_types = array( 'cover-letter', 'attestation-hebergement', 'lettre-motivation', 'employment-attestation', 'financial-attestation' );
if ( ! in_array( $type, $valid_types, true ) ) {
    return new WP_Error( 'invalid_type', 'Invalid document type', array( 'status' => 400 ) );
}

// Sanitize search query
$query = sanitize_text_field( $request->get_param( 'q' ) ?? '' );
$query = strtolower( $query );
```

---

## 💡 SUGGESTIONS FOR IMPROVEMENT

### 16. Add Request Validation Schemas
**Severity:** LOW 🔵

REST API endpoints should use `args` parameter to validate and sanitize input automatically:

```php
register_rest_route(
    self::NAMESPACE,
    '/profile',
    array(
        array(
            'methods'             => 'PUT',
            'callback'            => array( $this, 'update_member_profile' ),
            'permission_callback' => array( $this, 'check_member_permission' ),
            'args'                => array(
                'first_name' => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function( $value ) {
                        return strlen( $value ) <= 50;
                    },
                ),
                'email' => array(
                    'type'              => 'string',
                    'format'            => 'email',
                    'sanitize_callback' => 'sanitize_email',
                ),
                // ... etc
            ),
        ),
    )
);
```

---

### 17. Add Type Hints and Return Types
**Severity:** LOW 🔵

```php
// Current
public function get_member_profile( $request ) {

// Better
public function get_member_profile( WP_REST_Request $request ): WP_REST_Response {
    // ...
    return rest_ensure_response( $response );
}
```

---

### 18. Extract Hardcoded Arrays to Configuration
**Lines:** 2555-2605, 3185-3243
**Severity:** LOW 🔵

Move large data arrays to separate configuration files:

```php
// config/checklist-templates.php
return array(
    'visa-application' => array( /* ... */ ),
    'pre-departure' => array( /* ... */ ),
    // ...
);

// In class
private function get_checklist_items( $type ) {
    static $checklists = null;

    if ( null === $checklists ) {
        $checklists = include FRAMT_PLUGIN_DIR . '/config/checklist-templates.php';
    }

    return $checklists[ $type ] ?? array();
}
```

---

### 19. Add Logging for Security Events
**Severity:** LOW 🔵

```php
// Log failed permission checks
public function check_project_permission( $request ) {
    // ... existing code ...

    if ( $project->user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
        // Log unauthorized access attempt
        error_log( sprintf(
            'Unauthorized project access attempt: User %d tried to access project %d (owner: %d)',
            get_current_user_id(),
            $project_id,
            $project->user_id
        ) );

        return new WP_Error(
            'rest_forbidden',
            'You do not have permission to access this project.',
            array( 'status' => 403 )
        );
    }
}
```

---

### 20. Add Rate Limiting for Chat/AI Endpoints
**Lines:** 3580-3621
**Severity:** LOW 🔵

```php
public function send_chat_message( $request ) {
    $user_id = get_current_user_id();

    // Rate limiting
    $rate_limit_key = 'fra_chat_rate_limit_' . $user_id;
    $request_count = get_transient( $rate_limit_key );

    if ( false === $request_count ) {
        set_transient( $rate_limit_key, 1, MINUTE_IN_SECONDS );
    } elseif ( $request_count >= 10 ) {
        return new WP_Error(
            'rate_limit_exceeded',
            'Too many requests. Please wait a minute.',
            array( 'status' => 429 )
        );
    } else {
        set_transient( $rate_limit_key, $request_count + 1, MINUTE_IN_SECONDS );
    }

    // ... rest of method
}
```

---

## 📊 SUMMARY

### Priority Matrix

| Severity | Count | Must Fix Before Deploy |
|----------|-------|------------------------|
| 🔴 Critical | 4 | **YES** |
| 🟡 High | 11 | Strongly Recommended |
| 🔵 Medium/Low | 5 | Nice to Have |

### Critical Action Items

1. ✅ **Fix SQL injection in SHOW TABLES** (Lines 2872, 2938, 2999)
2. ✅ **Sanitize array inputs** (Lines 2299-2304)
3. ✅ **Use strict comparisons** (Lines 3004, 3017, 3831, etc.)
4. ✅ **Validate document generation inputs** (Lines 3087-3091)
5. ✅ **Fix N+1 query in notes** (Line 2130)
6. ✅ **Refactor long functions** (Especially upload_file)

### WordPress Coding Standards Compliance

- ❌ **Yoda Conditions**: Not used consistently
- ⚠️ **Spacing/Indentation**: Appears correct (tabs)
- ⚠️ **Nonce Verification**: Missing for state-changing operations
- ⚠️ **Sanitization**: Inconsistent, especially for arrays
- ⚠️ **Escaping**: Not applicable for API responses (JSON)
- ✅ **Naming**: Follows WP conventions

### Security Score: 6/10
- Major vulnerabilities in SQL and input handling
- Good permission checking structure but needs stricter comparisons
- Missing CSRF protection beyond REST API defaults

### Performance Score: 7/10
- N+1 queries in some methods
- No caching for expensive operations
- Generally good database query structure

### Code Quality Score: 7/10
- Some functions too long
- Code duplication exists
- Good overall structure and organization
- Well-documented with PHPDoc

---

## 🎯 RECOMMENDED IMMEDIATE ACTIONS

### Before Production Deployment:

1. **Fix all CRITICAL issues** (SQL injection, array sanitization)
2. **Add strict type comparisons** throughout
3. **Implement proper array sanitization helper**
4. **Add request validation schemas** to REST endpoints
5. **Refactor long functions** (at least `upload_file`)
6. **Add rate limiting** to chat/AI endpoints
7. **Run PHPCS** with WordPress standards
8. **Add unit tests** for permission checks

### Nice to Have:

- Implement caching strategy
- Extract configuration to separate files
- Add comprehensive logging
- Improve error messages
- Add type hints (PHP 7.0+)

---

**Review Complete** ✅
