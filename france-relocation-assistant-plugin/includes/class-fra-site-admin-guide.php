<?php
/**
 * Site Administration: how to get things done.
 *
 * A read-only page under FR Assistant listing, step by step, what an
 * administrator does to produce a given outcome: a refund, an approved
 * knowledge-base update, a message to members, a code update. Each guide
 * names the real screens and buttons and links straight to them, so it
 * must change whenever a screen it describes changes.
 *
 * @package France_Relocation_Assistant
 * @since   3.13.22
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_Site_Admin_Guide {

    const SLUG = 'fra-site-admin';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 20);
    }

    public static function menu() {
        add_submenu_page(
            'france-relocation-assistant',
            'Site Administration',
            'Site Administration',
            'manage_options',
            self::SLUG,
            array(__CLASS__, 'render'),
            1
        );
    }

    /** A link into another admin screen, as a button. */
    private static function go($label, $url) {
        return '<a class="button" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }

    /**
     * The guides: title, when you need it, steps, notes, links.
     *
     * @return array[]
     */
    private static function guides() {
        $ai_review  = admin_url('admin.php?page=france-relocation-assistant-ai-review');
        $kb_gaps    = admin_url('admin.php?page=france-relocation-assistant-kb-gaps');
        $messages   = admin_url('admin.php?page=fra-messages');
        $portal     = admin_url('admin.php?page=framt-portal-settings');
        $sync       = admin_url('tools.php?page=fra-github-sync');
        $api        = admin_url('admin.php?page=france-relocation-assistant-settings');
        $users      = admin_url('users.php');
        $mepr_tx    = admin_url('admin.php?page=memberpress-trans');
        $mepr_prod  = admin_url('edit.php?post_type=memberpressproduct');

        return array(
            array(
                'id'    => 'refund',
                'title' => 'Refund a purchase',
                'when'  => 'A member asks for their money back. Both the $99 membership and the $35 Family add-on carry a 30-day guarantee, each counted from the day it was bought.',
                'steps' => array(
                    'Go to MemberPress → Transactions.',
                    'Search for the member’s email address.',
                    'Check the transaction date is within the last 30 days. After 30 days a payment is not refundable unless the law requires it.',
                    'Hover over the transaction and click Refund (Refund & Cancel is fine too; there is no subscription to cancel).',
                    'Reply to the member that it is done. Stripe returns the money straight away; their bank usually shows it within 5 to 10 business days.',
                ),
                'notes' => array(
                    'Refunding the membership ends their access. Refunding only the Family add-on ends the family side: their partner loses access to the household file; their own file carries on.',
                    'You can also refund from the Stripe dashboard (Payments → the payment → Refund). MemberPress should follow, but refunding from MemberPress is the direct route.',
                ),
                'links' => array(array('MemberPress Transactions', $mepr_tx)),
            ),
            array(
                'id'    => 'kb-review',
                'title' => 'Review and approve knowledge-base updates',
                'when'  => 'Every week. The Cloudflare review service re-checks every topic against official sources on Sunday at 03:00 UTC and leaves suggested updates for you. Nothing reaches the site until you approve it.',
                'steps' => array(
                    'Go to FR Assistant → AI Review and scroll to Pending Updates.',
                    'Open each suggested update. Read what changed and check the sources it lists are official (service-public.gouv.fr, france-visas.gouv.fr, ameli.fr, impots.gouv.fr and similar).',
                    'Read the In Practice section. It appears only when at least two dated, independent sources agree; a note says when one was withheld.',
                    'Click ✓ Apply Update to publish it, or ✗ Reject to discard it. Approve All and Reject All exist, but read before using them.',
                    'After approving, check Member Messages for notices to members (see "Approve notices to members" below).',
                ),
                'notes' => array(
                    'Approving a draft without an In Practice section keeps the topic’s existing In Practice.',
                    'Do not use Start AI Review or Run Now: the review is run by the Cloudflare worker (the Runner setting on the same screen says so), not by WordPress.',
                ),
                'links' => array(array('AI Review', $ai_review)),
            ),
            array(
                'id'    => 'kb-gaps',
                'title' => 'Handle knowledge-base gaps and the overnight email',
                'when'  => 'Members asked something the knowledge base answered poorly, or the overnight email lists drafts that were withheld.',
                'steps' => array(
                    'Each night at 04:00 UTC the review service drafts answers for recorded gaps. Drafts it can back with official sources go to AI Review → Pending Updates, like any other update.',
                    'Drafts it could not verify are not queued; they arrive in the overnight email as a work list and are retried automatically (daily, then weekly).',
                    'To see every recorded gap, go to FR Assistant → KB Gaps. COVERAGE means nothing matched the question; DEPTH means a topic matched but was too thin.',
                    'Dismiss a gap there if it is not worth covering (off-topic, a one-off, or spam).',
                ),
                'notes' => array('The same screen shows how many questions were answered from the knowledge base alone, with no AI call.'),
                'links' => array(array('KB Gaps', $kb_gaps), array('AI Review', $ai_review)),
            ),
            array(
                'id'    => 'notices',
                'title' => 'Approve notices to members after a knowledge-base change',
                'when'  => 'You approved an update that affects some members (their visa route, their situation). A notice to them is drafted automatically and waits for you; you also get a daily email while any wait.',
                'steps' => array(
                    'Go to FR Assistant → Member Messages. Waiting notices appear at the top under "Notices waiting for your approval", with how many members each goes to and why.',
                    'Open a notice and read it.',
                    'Click Approve and send to message those members (they get it in the portal, and by email if they have email updates on), or Discard.',
                ),
                'notes' => array('The approve and discard links in the daily email work while you are signed in to wp-admin.'),
                'links' => array(array('Member Messages', $messages)),
            ),
            array(
                'id'    => 'message',
                'title' => 'Message a member, all members, or answer a support request',
                'when'  => 'You want to tell members something, or a member wrote in through Support.',
                'steps' => array(
                    'Go to FR Assistant → Member Messages.',
                    'To answer a member: filter Open, open their conversation, type your reply and click Send Reply. They see it in the portal under Support and get an email if they have email updates on.',
                    'To start a message: under Send Message to Members, choose the recipient (one member or 📢 All Members), write a subject and the message, and click 📤 Send Message. It appears in their portal under Messages.',
                ),
                'notes' => array('Members control their own emails in the portal under Settings → Notifications. A message always reaches the portal even when email updates are off.'),
                'links' => array(array('Member Messages', $messages)),
            ),
            array(
                'id'    => 'update',
                'title' => 'Put new code live (plugins and theme)',
                'when'  => 'Claude or a developer says a change is pushed and asks you to sync.',
                'steps' => array(
                    'Go to Tools → GitHub Sync.',
                    'Click Check for Updates.',
                    'Click Update All (Plugins + Theme), or update a single plugin from Managed Plugins.',
                    'Wait for the success notice. The version numbers under Managed Plugins and Managed Themes should match the ones you were given.',
                ),
                'notes' => array('A backup is taken before every update. If something goes wrong, restore it from Plugin Backups or Theme Backups on the same screen.'),
                'links' => array(array('GitHub Sync', $sync)),
            ),
            array(
                'id'    => 'family',
                'title' => 'Family add-on: check it is set up, or help a buyer',
                'when'  => 'A member bought the Family add-on but the portal does not show it, or you change the product.',
                'steps' => array(
                    'Go to FR Assistant → Portal Settings → Members tab, section Family add-on. Product ID should be 560 (MemberPress "Family add-on") and the checkout URL /register/family-add-on/. The portal fills these in by itself if they are empty.',
                    'For a buyer who does not see it: in MemberPress → Transactions, find their Family add-on purchase and check its status is Complete. A pending or failed payment does not unlock it.',
                    'The checkout only admits signed-in lifetime members; anyone else is sent to sign in or to Pricing.',
                ),
                'notes' => array('Households that added family during the free launch keep it. Administrators always have it.'),
                'links' => array(array('Portal Settings', $portal), array('MemberPress Products', $mepr_prod), array('MemberPress Transactions', $mepr_tx)),
            ),
            array(
                'id'    => 'signin',
                'title' => 'Help a member who cannot sign in',
                'when'  => 'A member writes that they are locked out.',
                'steps' => array(
                    'Suggest the emailed sign-in link first: on the portal sign-in card, "Email me a sign-in link". It works once, for 15 minutes; they should check spam.',
                    'If they prefer a password: Users → find them → hover → Send password reset.',
                    'If no email arrives at all, check the address on their user profile is right.',
                ),
                'notes' => array('Emailed links are never sent to administrator accounts; administrators sign in with a password.'),
                'links' => array(array('Users', $users)),
            ),
            array(
                'id'    => 'health',
                'title' => 'Check the portal is healthy',
                'when'  => 'A member reports something broken, or once a week as a habit.',
                'steps' => array(
                    'Go to FR Assistant → Portal Settings → Portal tab.',
                    'Status shows the portal version that is live.',
                    'Portal crashes lists what members saw when a page broke, with where it happened; the last twenty are kept. Send any new ones to Claude or a developer.',
                ),
                'links' => array(array('Portal Settings', $portal)),
            ),
            array(
                'id'    => 'portal-content',
                'title' => 'Change what members see in the portal',
                'when'  => 'You want to switch a tool off, change who can use the portal, or change the welcome banner.',
                'steps' => array(
                    'Go to FR Assistant → Portal Settings.',
                    'Tools tab, What members see: switch individual tools on or off.',
                    'Members tab: Who gets in, the Family add-on and the Welcome banner.',
                    'Click Save Changes at the bottom.',
                ),
                'notes' => array('Wording on the public site (relo2france.com pages and guides) is not edited here; ask Claude to change it.'),
                'links' => array(array('Portal Settings', $portal)),
            ),
            array(
                'id'    => 'api',
                'title' => 'Change the AI key or model',
                'when'  => 'The Anthropic key is rotated, or AI answers stop working.',
                'steps' => array(
                    'Go to FR Assistant → API Settings.',
                    'Paste the new Anthropic API key and save. Never send the key to anyone, Claude included.',
                    'Model choice is by tier, not a fixed model; the site picks the current model for each tier automatically.',
                ),
                'notes' => array('The review service on Cloudflare has its own copy of the key; if you rotate it, ask for the worker’s key to be updated too.'),
                'links' => array(array('API Settings', $api)),
            ),
            array(
                'id'    => 'remove',
                'title' => 'Remove a member’s account',
                'when'  => 'A member asks you to delete their account and data.',
                'steps' => array(
                    'Best: ask them to use Settings → Portal Account → Danger Zone → Delete Account in the portal. It removes their file, documents and messages, cancels MemberPress access and deletes their sign-in. Only the account holder can do this, not a partner.',
                    'If they cannot: refund first if they are within 30 days (see Refund a purchase), then delete them under Users.',
                ),
                'notes' => array('Deleting under Users removes the sign-in but may leave portal records behind; the portal’s own Delete Account is the complete route.'),
                'links' => array(array('Users', $users)),
            ),
        );
    }

    public static function render() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $guides = self::guides();
        ?>
        <div class="wrap fra-site-admin">
            <h1>Site Administration</h1>
            <p style="max-width:70ch;font-size:14px;color:#50575e;">How to get things done on Relo2France, step by step. Each guide names the screen and the button, with a link to go straight there.</p>

            <div class="card" style="max-width:none;margin:16px 0 24px;">
                <h2 style="margin-top:0;">Guides</h2>
                <ol style="columns:2;column-gap:40px;margin:0 0 0 18px;">
                    <?php foreach ($guides as $g) : ?>
                        <li style="break-inside:avoid;margin:0 0 6px;"><a href="#<?php echo esc_attr($g['id']); ?>"><?php echo esc_html($g['title']); ?></a></li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <?php foreach ($guides as $g) : ?>
                <div class="card" id="<?php echo esc_attr($g['id']); ?>" style="max-width:none;margin:0 0 20px;scroll-margin-top:48px;">
                    <h2 style="margin-top:0;"><?php echo esc_html($g['title']); ?></h2>
                    <p style="max-width:75ch;color:#50575e;"><strong>When:</strong> <?php echo esc_html($g['when']); ?></p>
                    <ol style="max-width:75ch;margin-left:18px;">
                        <?php foreach ($g['steps'] as $step) : ?>
                            <li style="margin:0 0 6px;"><?php echo esc_html($step); ?></li>
                        <?php endforeach; ?>
                    </ol>
                    <?php if (!empty($g['notes'])) : ?>
                        <?php foreach ($g['notes'] as $note) : ?>
                            <p style="max-width:75ch;margin:8px 0;padding:8px 12px;background:#f6f7f7;border-left:3px solid #2c5346;"><?php echo esc_html($note); ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($g['links'])) : ?>
                        <p style="margin:12px 0 0;display:flex;gap:8px;flex-wrap:wrap;">
                            <?php foreach ($g['links'] as $link) { echo self::go($link[0], $link[1]); } // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in go(). ?>
                        </p>
                    <?php endif; ?>
                    <p style="margin:12px 0 0;"><a href="#wpbody-content" style="font-size:12px;">Back to top</a></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

FRA_Site_Admin_Guide::init();
