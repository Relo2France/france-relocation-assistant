# Relo2France Handoff Document
## Session: December 14, 2025 - In-Chat Account System & MemberPress Integration

---

## CURRENT VERSIONS

| Component | Version | Status |
|-----------|---------|--------|
| Main Plugin | v2.9.83 | Active |
| Member Tools Plugin | v1.0.80 | Active |
| Theme | v1.2.3 | Active |

---

## WHAT WAS BUILT THIS SESSION

### 1. COMPLETE IN-CHAT ACCOUNT SYSTEM
All account management stays within the chat panel - no page navigation needed.

**Dropdown Structure:**
```
Kevin ▼
├── My Visa Profile          → Member Tools visa/relocation profile
├── My Visa Documents        → Member Tools documents section
├── My Membership Account    → In-chat billing profile form
│   ├── Subscriptions        → In-chat subscriptions table
│   └── Payments             → In-chat payment history
└── Log Out
```

### 2. CUSTOM MEMBERPRESS SHORTCODES
MemberPress doesn't provide shortcodes for displaying just subscriptions or payments tables. Created custom shortcodes that use MemberPress's internal `MeprAccountCtrl` class:

- `[fra_mepr_subscriptions]` - Displays subscriptions table with cancel/suspend/resume/update/upgrade actions
- `[fra_mepr_payments]` - Displays payment history table

**Implementation in `france-relocation-assistant.php`:**
```php
public function render_mepr_subscriptions() {
    $acct_ctrl = new MeprAccountCtrl();
    $acct_ctrl->subscriptions();
}

public function render_mepr_payments() {
    $acct_ctrl = new MeprAccountCtrl();
    $acct_ctrl->payments();
}
```

### 3. NAMING CONSISTENCY
- **My Visa Profile** - Passport info, visa type, move dates (for documents)
- **My Visa Documents** - Generated documents for relocation
- **My Membership Account** - Billing profile, subscriptions, payments

### 4. MEMBER MESSAGING/SUPPORT TICKET SYSTEM (v2.9.77/v1.0.65)

**New Feature:** Complete messaging system for priority member support.

**Frontend (Member Side):**
- "My Messages" link in member dropdown (with unread badge)
- Inbox view showing all conversations
- Compose new message form
- Conversation view with reply thread
- Reply to messages
- Delete conversations

**Backend (Admin Side):**
- Admin page at: WP Admin → Relo2France → Member Messages
- List all tickets with status filters (All/Open/Closed)
- Split-view interface with message list and detail panel
- Reply to tickets
- Close/Reopen tickets
- Unread count badge in admin menu

**Database Tables (created on plugin activation):**
- `wp_framt_messages` - Message/ticket records
- `wp_framt_message_replies` - Reply thread

**Email Notifications:**
- Admin notified on new message and member reply
- Member notified when admin replies

**Files Added/Modified:**
- `class-framt-messages.php` (new) - All messaging logic
- `france-relocation-member-tools.php` - Added class include and table creation
- `shortcode-template.php` - Added "My Messages" to dropdown
- `frontend.css` (main) - Unread badge styling
- `frontend.css` (member tools) - Messages section styling  
- `frontend.js` (member tools) - Messages UI handling

### 5. CODE CLEANUP & OPTIMIZATION
- Consolidated duplicate MemberPress hide rules
- Removed outdated account footer CSS
- Cleaned up responsive media queries

---

## KEY FILES CHANGED

### Main Plugin (france-relocation-assistant) v2.9.45

**france-relocation-assistant.php**
- Added `[fra_mepr_subscriptions]` shortcode registration
- Added `[fra_mepr_payments]` shortcode registration
- Added `render_mepr_subscriptions()` method using `MeprAccountCtrl`
- Added `render_mepr_payments()` method using `MeprAccountCtrl`

**includes/shortcode-template.php**
- Dropdown with nested My Membership Account group
- Subscriptions view using `[fra_mepr_subscriptions]`
- Payments view using `[fra_mepr_payments]`

**assets/js/frontend.js**
- Updated section titles for "My Visa Documents"
- Handler for `[data-account-section]` child links
- Views array includes subscriptions and payments

**assets/css/frontend.css**
- Consolidated MemberPress hide rules
- Dropdown group/children styling
- Table styling for subscriptions/payments

### Member Tools Plugin (france-relocation-member-tools) v1.0.62

**includes/class-framt-documents.php**
- Page header: "My Visa Documents"

**assets/js/frontend.js**
- Document ready note: "saved to My Visa Documents"

---

## ARCHITECTURE

```
User Dropdown
    │
    ├── My Visa Profile ──────────→ navigateToSection('profile')
    │                                    ↓
    │                               Member Tools AJAX
    │
    ├── My Visa Documents ────────→ navigateToSection('my-documents')
    │                                    ↓
    │                               Member Tools AJAX
    │
    ├── My Membership Account ────→ FRAInChatAuth.showView('account')
    │       │                            ↓
    │       │                       [mepr-account-form]
    │       │
    │       ├── Subscriptions ────→ FRAInChatAuth.showView('subscriptions')
    │       │                            ↓
    │       │                       [fra_mepr_subscriptions]
    │       │                            ↓
    │       │                       MeprAccountCtrl->subscriptions()
    │       │
    │       └── Payments ─────────→ FRAInChatAuth.showView('payments')
    │                                    ↓
    │                               [fra_mepr_payments]
    │                                    ↓
    │                               MeprAccountCtrl->payments()
    │
    └── Log Out ──────────────────→ wp_logout_url redirect
```

---

## MEMBERPRESS INTEGRATION DETAILS

### Why Custom Shortcodes Were Needed
MemberPress provides `[mepr-account-form]` which shows the full account page with tabs, but doesn't provide shortcodes to display individual sections like subscriptions or payments.

The solution uses MemberPress's internal `MeprAccountCtrl` controller class which has methods for each section:
- `->subscriptions()` - Renders subscriptions table
- `->payments()` - Renders payments table
- Also handles actions: cancel, suspend, resume, update, upgrade

### Shortcode Output
The shortcodes output the exact same HTML that MemberPress generates for the account page tabs, ensuring full compatibility with MemberPress's styling and functionality.

---

## TESTING CHECKLIST

- [ ] Click "Kevin ▼" dropdown - all items visible
- [ ] "My Visa Profile" - loads visa profile in chat
- [ ] "My Visa Documents" - loads documents in chat
- [ ] "My Membership Account" - loads billing form in chat
- [ ] "Subscriptions" - shows subscriptions table with data
- [ ] "Payments" - shows payment history with data
- [ ] Subscription actions (Update/Cancel) work
- [ ] Back buttons work on all views

---

## GITHUB REPOSITORY

**Main Plugin:** Relo2France/france-relocation-assistant  
**Member Tools:** Relo2France/france-relocation-member-tools

---

## REMOVED CODE - ARCHIVED FOR REFERENCE

### Logout Cookie Redirect System (Removed in v2.9.76)

**Why it was removed:** The thank-you page now handles all auth redirects via JavaScript added directly to the page. This is simpler and more reliable than the plugin-based approach.

**What was removed from `france-relocation-assistant.php`:**

1. **`logout_redirect()` method** - WordPress filter to redirect after logout
2. **`force_logout_redirect()` method** - Set cookie on logout as backup
3. **`clear_logout_cookie_on_login()` method** - Clear stale cookies on login
4. **`check_logout_cookie_redirect()` method** - Intercept thank-you page and redirect

**If you need to restore this functionality:**

Add these hooks to the constructor:
```php
add_filter('logout_redirect', array($this, 'logout_redirect'), 999, 3);
add_action('wp_logout', array($this, 'force_logout_redirect'), 999);
add_action('wp_login', array($this, 'clear_logout_cookie_on_login'), 10);
add_action('template_redirect', array($this, 'check_logout_cookie_redirect'), 1);
```

And restore these methods:
```php
public function logout_redirect($redirect_to, $requested_redirect_to, $user) {
    return home_url('/?logged_out=1');
}

public function force_logout_redirect() {
    setcookie('fra_just_logged_out', '1', time() + 60, COOKIEPATH, COOKIE_DOMAIN);
}

public function clear_logout_cookie_on_login() {
    if (isset($_COOKIE['fra_just_logged_out'])) {
        setcookie('fra_just_logged_out', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
}

public function check_logout_cookie_redirect() {
    if (!is_user_logged_in()) {
        if (isset($_COOKIE['fra_just_logged_out']) && $_COOKIE['fra_just_logged_out'] === '1') {
            setcookie('fra_just_logged_out', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
            wp_safe_redirect(home_url('/?logged_out=1'));
            exit;
        }
        if (is_page('thank-you') && !isset($_GET['membership']) && !isset($_GET['trans_num'])) {
            wp_safe_redirect(home_url('/?logged_out=1'));
            exit;
        }
    } else {
        if (isset($_COOKIE['fra_just_logged_out'])) {
            setcookie('fra_just_logged_out', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }
}
```

**Current approach (thank-you page JavaScript):**
```html
<script>
(function() {
    var isLoggedIn = document.body.classList.contains('logged-in');
    var urlParams = new URLSearchParams(window.location.search);
    var isNewSignup = urlParams.has('membership') && urlParams.has('trans_num');
    
    if (isNewSignup && isLoggedIn) {
        window.location.replace('/?new_signup=1');
    } else if (!isLoggedIn) {
        window.location.replace('/?logged_out=1');
    } else {
        window.location.replace('/');
    }
})();
</script>
<style>
.entry-content, .page-content, article { visibility: hidden; }
</style>
```

---

## FILES INCLUDED IN HANDOFF

1. `france-relocation-assistant-v2.9.83.zip` - Main plugin
2. `france-relocation-member-tools-v1.0.80.zip` - Member tools plugin
3. `relo2france-theme-v1.2.3.zip` - Theme with scroll-snap behavior
4. `handoff-document.md` - This document

---

*Last Updated: December 14, 2025*
