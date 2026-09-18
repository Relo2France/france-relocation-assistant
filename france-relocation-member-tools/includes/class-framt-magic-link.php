<?php
/**
 * Sign in with a link sent by email, alongside the password.
 *
 * The member types their email; if it belongs to an account, a link valid
 * for fifteen minutes and good for one use is emailed to it. Opening the
 * link shows a "Continue" button rather than signing in on the spot: mail
 * scanners open every link in a message, and a link that signed in on GET
 * would be spent before the member clicked it. The button posts the token
 * back, which scanners do not do.
 *
 * - The reply never says whether an account exists.
 * - Only an HMAC of the token is stored. A new request adds a token and
 *   leaves earlier unexpired ones working (up to three), so a member who
 *   asks twice can use either email. Signing in spends them all.
 * - Administrators never get links: they sign in with their password.
 * - Requests are rate-limited per email and per IP address.
 * - The email is transactional and always sent, like a password reset.
 *
 * @package     FRA_Member_Tools
 * @subpackage  Auth
 * @since       2.9.26
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FRAMT_Magic_Link {

    const META      = 'framt_magic_login';
    const TTL       = 15 * MINUTE_IN_SECONDS;
    const QUERY_VAR = 'r2f_login';
    const AJAX      = 'framt_magic_link';

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_nopriv_' . self::AJAX, array( $this, 'ajax_request' ) );
        add_action( 'wp_ajax_' . self::AJAX, array( $this, 'ajax_request' ) );
        add_action( 'template_redirect', array( $this, 'handle_link' ), 0 );
        // Signing out ends on the portal's card, worded for it. MemberPress
        // applies its own after-logout URL late on this filter; ours runs after.
        add_filter( 'logout_redirect', array( $this, 'logout_redirect' ), PHP_INT_MAX, 3 );
        // MemberPress's after-logout setting redirects from inside wp_logout
        // and exits, before logout_redirect is ever applied. Go first.
        add_action( 'wp_logout', array( $this, 'redirect_on_logout' ), -1000 );
        // One sign-in screen: /login/, /logged-out/ and a signed-out
        // /account/ all forward to the portal's card.
        add_action( 'template_redirect', array( $this, 'account_needs_sign_in' ), 1 );
    }

    /**
     * At sign-out, if the link asked for the portal card, go there now. The
     * session and cookies are already cleared when wp_logout fires.
     */
    public function redirect_on_logout() {
        // Never redirect inside an AJAX or REST request: the caller expects
        // JSON, and a 302 with an empty body loses the real response.
        if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }
        $requested = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( '' === $requested ) {
            return;
        }
        $target = $this->logout_redirect( '', $requested );
        if ( '' !== $target ) {
            wp_safe_redirect( $target );
            exit;
        }
    }

    /**
     * One sign-in screen: the portal's. The /login/ page forwards there,
     * except for MemberPress's password-reset steps, which live on it
     * (?action=forgot_password, ?action=reset_password). A signed-out visitor
     * to /account/ goes there too, instead of MemberPress's bare form.
     */
    public function account_needs_sign_in() {
        if ( ! empty( $_REQUEST[ self::QUERY_VAR ] ) ) {
            return; // an old sign-in link: handle_link deals with it.
        }
        if ( is_page( 'login' ) && empty( $_GET['action'] ) && 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $to = home_url( '/portal/' );
            if ( isset( $_GET['link'] ) && 'expired' === $_GET['link'] ) { // phpcs:ignore WordPress.Security.NonceVerification
                $to = add_query_arg( 'link', 'expired', $to );
            }
            // Keep where the member was going, so signing in lands them there.
            $back = self::safe_redirect_to( $_GET['redirect_to'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
            if ( '' !== $back ) {
                $to = add_query_arg( 'redirect_to', rawurlencode( $back ), $to );
            }
            wp_safe_redirect( $to );
            exit;
        }
        if ( ! is_user_logged_in() && is_page( 'logged-out' ) ) {
            wp_safe_redirect( add_query_arg( 'signed_out', '1', home_url( '/portal/' ) ) );
            exit;
        }
        // The Schengen tracker page is drawn by an older plugin whose own
        // sign-in box points at wp-login.php; send signed-out visitors to the
        // portal card, and back to the tracker once signed in.
        if ( ! is_user_logged_in() && is_page( 'my-travel-status' ) ) {
            wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( home_url( '/my-travel-status/' ) ), home_url( '/portal/' ) ) );
            exit;
        }
        if ( ! is_user_logged_in() && is_page( 'account' ) ) {
            wp_safe_redirect( home_url( '/portal/' ) );
            exit;
        }
    }

    /**
     * Signing out ends on the portal's sign-in card, worded for it
     * (?signed_out=1), whatever a later setting such as MemberPress's
     * after-logout URL would put in its place.
     */
    public function logout_redirect( $redirect_to, $requested = '', $user = null ) {
        $signed_out = add_query_arg( 'signed_out', '1', home_url( '/portal/' ) );
        if ( is_string( $requested ) && ( 0 === strpos( $requested, home_url( '/portal/' ) ) || 0 === strpos( $requested, home_url( '/logged-out/' ) ) ) ) {
            return $signed_out;
        }
        return $redirect_to;
    }

    /** The one reply, whatever happened, so nobody can test which emails have accounts. */
    const REPLY = 'If that email belongs to an account, a sign-in link is on its way. It works once, for fifteen minutes. Check spam if it has not arrived in a couple of minutes.';

    /**
     * A same-site URL to go to after signing in, or '' when the value is
     * empty or points somewhere else.
     *
     * @param mixed $raw Raw request value.
     * @return string
     */
    public static function safe_redirect_to( $raw ) {
        if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
            return '';
        }
        $url = esc_url_raw( wp_unslash( $raw ) );
        if ( '' === $url ) {
            return '';
        }
        $valid = wp_validate_redirect( $url, '' );
        if ( '' === $valid ) {
            return '';
        }
        // Only pages on this site; never back into sign-in or sign-out.
        if ( 0 !== strpos( $valid, home_url( '/' ) ) || false !== strpos( $valid, 'wp-login.php' ) ) {
            return '';
        }
        return $valid;
    }

    /** Administrators sign in with their password, never by emailed link. */
    private static function refused( $user ) {
        if ( ! $user instanceof WP_User ) {
            return true;
        }
        if ( user_can( $user, 'manage_options' ) ) {
            return true;
        }
        if ( function_exists( 'is_user_spammy' ) && is_user_spammy( $user ) ) {
            return true;
        }
        return false;
    }

    /** Active (unexpired) stored token entries for a user, oldest first. */
    private static function active_entries( $user_id ) {
        $saved = get_user_meta( $user_id, self::META, true );
        if ( ! is_array( $saved ) ) {
            return array();
        }
        // Earlier versions stored one entry as array( hash, expires ).
        if ( isset( $saved['hash'] ) ) {
            $saved = array( $saved );
        }
        $now    = time();
        $active = array();
        foreach ( $saved as $entry ) {
            if ( is_array( $entry ) && ! empty( $entry['hash'] ) && (int) ( $entry['expires'] ?? 0 ) >= $now ) {
                $active[] = array(
                    'hash'    => (string) $entry['hash'],
                    'expires' => (int) $entry['expires'],
                );
            }
        }
        return $active;
    }

    private static function email_limit_key( $email ) {
        return 'framt_ml_' . md5( 'email:' . strtolower( $email ) );
    }

    private static function ip() {
        return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    }

    /** True when this key has been used more than $max times in $window seconds. */
    private static function limited( $key, $max, $window ) {
        return self::limited_name( 'framt_ml_' . md5( $key ), $max, $window );
    }

    private static function limited_name( $name, $max, $window ) {
        $count = (int) get_transient( $name );
        if ( $count >= $max ) {
            return true;
        }
        set_transient( $name, $count + 1, $window );
        return false;
    }

    private static function digest( $token ) {
        return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
    }

    /**
     * admin-ajax: email a sign-in link.
     */
    public function ajax_request() {
        // A field people never see; a bot that fills it gets the same reply.
        if ( ! empty( $_POST['website'] ) ) {
            wp_send_json_success( self::REPLY );
        }
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( 'Enter the email address you signed up with.' );
        }
        if ( self::limited( 'ip:' . self::ip(), 10, HOUR_IN_SECONDS ) ) {
            wp_send_json_error( 'Too many sign-in links requested from here. Try again in an hour, or sign in with your password.' );
        }
        if ( self::limited_name( self::email_limit_key( $email ), 3, 15 * MINUTE_IN_SECONDS ) ) {
            // Same wording as success: the limit must not reveal the account either.
            wp_send_json_success( self::REPLY );
        }

        $user = get_user_by( 'email', $email );
        if ( $user && ! self::refused( $user ) ) {
            $this->send_link( $user, self::safe_redirect_to( $_POST['redirect_to'] ?? '' ) );
        }
        wp_send_json_success( self::REPLY );
    }

    private function send_link( $user, $redirect_to = '' ) {
        $token   = bin2hex( random_bytes( 32 ) );
        $entries = self::active_entries( $user->ID );
        $entries[] = array(
            'hash'    => self::digest( $token ),
            'expires' => time() + self::TTL,
        );
        // Earlier links stay valid until they expire; keep the newest three.
        update_user_meta( $user->ID, self::META, array_slice( $entries, -3 ) );
        $args = array( self::QUERY_VAR => $token, 'u' => (int) $user->ID );
        if ( '' !== $redirect_to ) {
            $args['redirect_to'] = rawurlencode( $redirect_to );
        }
        $url   = add_query_arg( $args, home_url( '/portal/' ) );
        $first = $user->first_name ?: strtok( $user->display_name, ' ' );
        $body  = '<p style="margin:0 0 12px;">Use the button below to sign in to your Relo2France portal. It works once, for the next fifteen minutes.</p>'
            . '<p style="margin:0;color:#5f6e66;font-size:14px;">If you did not ask for this, ignore the email; nothing happens unless the link is used.</p>';
        if ( class_exists( 'FRAMT_Messages' ) ) {
            $html = FRAMT_Messages::render_email( 'Your sign-in link', 'Hello ' . ( $first ?: 'there' ) . ',', $body, 'Sign in to Relo2France', $url );
            FRAMT_Messages::send_html( $user->user_email, 'Your Relo2France sign-in link', $html );
        } else {
            wp_mail( $user->user_email, 'Your Relo2France sign-in link', "Sign in to Relo2France (works once, for fifteen minutes):\n\n" . $url );
        }
    }

    /**
     * Check a token without spending it.
     *
     * @return WP_User|null
     */
    private static function check( $user_id, $token ) {
        $user = get_userdata( (int) $user_id );
        if ( ! $user || ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
            return null;
        }
        if ( self::refused( $user ) ) {
            return null;
        }
        $digest = self::digest( $token );
        foreach ( self::active_entries( $user->ID ) as $entry ) {
            if ( hash_equals( $entry['hash'], $digest ) ) {
                return $user;
            }
        }
        return null;
    }

    /**
     * The link: show a Continue button on GET, sign in on POST.
     */
    public function handle_link() {
        if ( empty( $_REQUEST[ self::QUERY_VAR ] ) ) {
            return;
        }
        $token   = sanitize_text_field( wp_unslash( $_REQUEST[ self::QUERY_VAR ] ) );
        $user_id = absint( $_REQUEST['u'] ?? 0 );
        $user    = self::check( $user_id, $token );
        $expired = add_query_arg( 'link', 'expired', home_url( '/portal/' ) );

        if ( ! $user ) {
            wp_safe_redirect( $expired );
            exit;
        }

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
            // One use: spend it (and any other outstanding link) before signing in.
            delete_user_meta( $user->ID, self::META );
            // A used link resets the per-email request limit.
            delete_transient( self::email_limit_key( $user->user_email ) );
            wp_clear_auth_cookie();
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true, is_ssl() );
            do_action( 'wp_login', $user->user_login, $user );
            $back = self::safe_redirect_to( $_POST['redirect_to'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification -- the token is the credential.
            wp_safe_redirect( '' !== $back ? $back : home_url( '/portal/' ) );
            exit;
        }

        nocache_headers();
        header( 'Referrer-Policy: no-referrer' );
        $this->render_continue( $user, $token, self::safe_redirect_to( $_GET['redirect_to'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
        exit;
    }

    private function render_continue( $user, $token, $redirect_to = '' ) {
        $parts  = explode( '@', $user->user_email );
        $masked = substr( $parts[0], 0, 1 ) . str_repeat( '•', max( 2, strlen( $parts[0] ) - 1 ) ) . '@' . ( $parts[1] ?? '' );
        ?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Sign in · Relo2France</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Karla:wght@400;600&display=swap" rel="stylesheet">
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#23332c;font-family:Karla,'Helvetica Neue',Arial,sans-serif;padding:20px;box-sizing:border-box}
.card{background:#fff;border:1px solid #dde3de;border-radius:12px;padding:40px 32px;max-width:400px;width:100%;text-align:center}
.mark{font-family:Fraunces,Georgia,serif;font-size:26px;font-weight:700;letter-spacing:-.02em;color:#1c2420;margin:0 0 20px}.mark span{color:#2c5346}
h1{font-family:Fraunces,Georgia,serif;font-size:24px;font-weight:600;color:#111827;margin:0 0 8px}p{color:#5f6e66;font-size:15px;line-height:1.5;margin:0 0 24px}
button{width:100%;background:#2c5346;color:#fff;border:0;border-radius:999px;padding:13px 18px;font:600 15px Karla,'Helvetica Neue',Arial,sans-serif;cursor:pointer}
button:hover{background:#23443a}button:focus-visible{outline:2px solid #2c5346;outline-offset:3px}
</style></head><body>
<main class="card">
<div class="mark">Relo<span>2</span>France</div>
<h1>Sign in</h1>
<p>Continue as <?php echo esc_html( $masked ); ?>. This link works once.</p>
<form method="post" action="<?php echo esc_url( home_url( '/portal/' ) ); ?>">
<input type="hidden" name="<?php echo esc_attr( self::QUERY_VAR ); ?>" value="<?php echo esc_attr( $token ); ?>">
<input type="hidden" name="u" value="<?php echo (int) $user->ID; ?>">
<?php if ( '' !== $redirect_to ) : ?><input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>"><?php endif; ?>
<button type="submit">Continue to your portal</button>
</form>
</main></body></html><?php
    }

    /**
     * The request form: an email field and a button, used by the portal's
     * sign-in card and the /login/ page. Self-contained HTML and script.
     *
     * @return string
     */
    public static function form_html() {
        $ajax = admin_url( 'admin-ajax.php' );
        ob_start();
        ?>
<div class="r2f-magic" data-ajax="<?php echo esc_url( $ajax ); ?>">
    <form class="r2f-magic-form" novalidate>
        <label for="r2f-magic-email" class="r2f-magic-label">Email</label>
        <input type="email" id="r2f-magic-email" name="email" required autocomplete="email" placeholder="you@example.com" class="r2f-magic-input">
        <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="r2f-magic-hp">
        <button type="submit" class="r2f-magic-button">Email me a sign-in link</button>
        <p class="r2f-magic-status" role="status" aria-live="polite"></p>
    </form>
</div>
<style>
.r2f-magic-form{display:flex;flex-direction:column;gap:10px;text-align:left}
.r2f-magic-label{font-size:14px;font-weight:600;color:var(--ink,#374151)}
.r2f-magic-input{padding:12px 14px;border:1px solid var(--rule,#d1d5db);border-radius:8px;font-size:15px;width:100%;box-sizing:border-box;background:var(--card,#fff);color:var(--ink,#1c2420)}
.r2f-magic-input:focus{outline:2px solid var(--vine,#2c5346);outline-offset:1px}
.r2f-magic-hp{position:absolute;left:-9999px;width:1px;height:1px;opacity:0}
.r2f-magic-button{background:transparent;color:var(--ink,#2c5346);border:1.5px solid var(--vine,#2c5346);border-radius:var(--radius-pill,8px);padding:12px 16px;font-weight:600;font-size:15px;cursor:pointer}
.r2f-magic-button:hover{background:var(--card-2,#f4f6f4)}.r2f-magic-button[disabled]{opacity:.6;cursor:wait}
.r2f-magic-status{margin:4px 0 0;font-size:14px;line-height:1.45;color:var(--ink,#2c5346)}
.r2f-magic-status.is-error{color:#d4574b}
</style>
<script>
(function(){
  document.querySelectorAll('.r2f-magic').forEach(function(box){
    if (box.dataset.ready) return; box.dataset.ready = '1';
    var form = box.querySelector('form'), btn = box.querySelector('button'), status = box.querySelector('.r2f-magic-status');
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var fd = new FormData(form); fd.append('action', '<?php echo esc_js( self::AJAX ); ?>');
      var back = new URLSearchParams(window.location.search).get('redirect_to'); if (back) fd.append('redirect_to', back);
      btn.disabled = true; status.className = 'r2f-magic-status'; status.textContent = 'Sending…';
      fetch(box.dataset.ajax, {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(d){ status.textContent = d.data || 'Something went wrong. Try again.'; if (!d.success) status.className = 'r2f-magic-status is-error'; })
        .catch(function(){ status.textContent = 'Something went wrong. Try again, or sign in with your password.'; status.className = 'r2f-magic-status is-error'; })
        .then(function(){ btn.disabled = false; });
    });
  });
})();
</script>
        <?php
        return ob_get_clean();
    }
}
