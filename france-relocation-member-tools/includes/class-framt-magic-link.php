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
 * - Only an HMAC of the token is stored; a new request replaces the old.
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
    }

    /** The one reply, whatever happened, so nobody can test which emails have accounts. */
    const REPLY = 'If that email belongs to an account, a sign-in link is on its way. It works once, for fifteen minutes. Check spam if it has not arrived in a couple of minutes.';

    private static function ip() {
        return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    }

    /** True when this key has been used more than $max times in $window seconds. */
    private static function limited( $key, $max, $window ) {
        $name  = 'framt_ml_' . md5( $key );
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
        if ( self::limited( 'email:' . strtolower( $email ), 3, 15 * MINUTE_IN_SECONDS ) ) {
            // Same wording as success: the limit must not reveal the account either.
            wp_send_json_success( self::REPLY );
        }

        $user = get_user_by( 'email', $email );
        if ( $user ) {
            $this->send_link( $user );
        }
        wp_send_json_success( self::REPLY );
    }

    private function send_link( $user ) {
        $token = bin2hex( random_bytes( 32 ) );
        update_user_meta( $user->ID, self::META, array(
            'hash'    => self::digest( $token ),
            'expires' => time() + self::TTL,
        ) );
        $url   = add_query_arg( array( self::QUERY_VAR => $token, 'u' => (int) $user->ID ), home_url( '/login/' ) );
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
        $saved = get_user_meta( $user->ID, self::META, true );
        if ( ! is_array( $saved ) || empty( $saved['hash'] ) || (int) ( $saved['expires'] ?? 0 ) < time() ) {
            return null;
        }
        return hash_equals( (string) $saved['hash'], self::digest( $token ) ) ? $user : null;
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
        $expired = add_query_arg( 'link', 'expired', home_url( '/login/' ) );

        if ( ! $user ) {
            wp_safe_redirect( $expired );
            exit;
        }

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
            // One use: spend it before signing in.
            delete_user_meta( $user->ID, self::META );
            wp_clear_auth_cookie();
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true, is_ssl() );
            do_action( 'wp_login', $user->user_login, $user );
            wp_safe_redirect( home_url( '/portal/' ) );
            exit;
        }

        nocache_headers();
        header( 'Referrer-Policy: no-referrer' );
        $this->render_continue( $user, $token );
        exit;
    }

    private function render_continue( $user, $token ) {
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
<form method="post" action="<?php echo esc_url( home_url( '/login/' ) ); ?>">
<input type="hidden" name="<?php echo esc_attr( self::QUERY_VAR ); ?>" value="<?php echo esc_attr( $token ); ?>">
<input type="hidden" name="u" value="<?php echo (int) $user->ID; ?>">
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
