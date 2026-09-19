/**
 * Destinations that still live on WordPress.
 *
 * Sign-in, checkout and the portal itself have not been ported yet, so linking
 * to them means leaving this stack. Naming them in one place means the port is
 * a single edit here rather than a hunt through components - and the link test
 * can tell a deliberate external link from a route someone forgot to build.
 */
export const WP_ORIGIN = 'https://relo2france.com';

export const external = {
  signIn: `${WP_ORIGIN}/portal/`,
  /** MemberPress checkout. One product: lifetime access. */
  join: `${WP_ORIGIN}/register/lifetime-membership/`,
  /** MemberPress checkout for the Family add-on (product 560). */
  familyAddon: `${WP_ORIGIN}/register/family-add-on/`,
  portal: `${WP_ORIGIN}/portal/`,
  account: `${WP_ORIGIN}/account/`,
} as const;

/**
 * The price, in one place, because it appears on three pages and a wrong one
 * is worse than a missing one. Sourced from the live MemberPress product.
 */
export const PRICE = '$99';
export const PRICE_NOTE = 'once, for life. No subscription, no renewal.';

/**
 * The guarantee, in one place for the same reason. Thirty days, full refund,
 * no form to fill in: an email is enough.
 */
export const REFUND_DAYS = 30;
export const GUARANTEE = `${REFUND_DAYS}-day money-back guarantee. Email us within ${REFUND_DAYS} days and you get every cent back.`;

/**
 * The Family add-on: one partner and up to four children, each with their own
 * file and the partner with their own sign-in.
 *
 * FAMILY_ADDON_ON_SALE is the one switch. The MemberPress product does not
 * exist yet, and until its ID is set in Portal Settings the portal opens the
 * family features to every member. While that is true the site must not sell
 * an add-on nobody can buy: it says plainly that family is included during
 * launch. Flip this to true only once the product is live and gated, and the
 * priced copy returns everywhere at once.
 */
export const FAMILY_ADDON_ON_SALE = true;
export const FAMILY_ADDON_PRICE = '$35';
/** No trailing full stop: it is quoted inside parentheses as well as on its own. */
export const FAMILY_ADDON_NOTE = 'once, on top of membership, for one partner and up to four children';
/** What the site says while the add-on is not on sale. */
export const FAMILY_INCLUDED_NOTE =
  'During launch, a partner and up to four children are included in membership at no extra cost, each with a file of their own.';

/**
 * The contact address for anyone, member or not. Members can also write from
 * Support inside the portal. Kept out of `external`, which holds only
 * WordPress URLs.
 */
export const SUPPORT_EMAIL = 'support@relo2france.com';

/**
 * Every sentence the pricing page says about family, decided by one flag, so
 * the page can never sell the add-on in one paragraph and give it away in the
 * next.
 */
export function familyCopy(onSale: boolean = FAMILY_ADDON_ON_SALE) {
  if (!onSale) {
    return {
      cardTitle: 'Family included',
      cardNote: FAMILY_INCLUDED_NOTE,
      answer:
        'Yes. Membership tracks you and dates every step for the people moving with you. During launch, a partner and up to four children are included at no extra cost: each gets a file of their own, and your partner their own sign-in, so you can split the work or do it all yourself.',
      included:
        'Every applicant in your household on one account — during launch, a file each for a partner and up to four children, and a sign-in for your partner',
      extras: 'There is no renewal, no paid tier and no per-document charge.',
    };
  }
  return {
    cardTitle: `Family add-on · ${FAMILY_ADDON_PRICE}`,
    cardNote: `${FAMILY_ADDON_NOTE.charAt(0).toUpperCase()}${FAMILY_ADDON_NOTE.slice(1)}.`,
    answer: `Membership tracks you and dates every step for the people moving with you. The Family add-on (${FAMILY_ADDON_PRICE}, ${FAMILY_ADDON_NOTE}) gives each of them a file of their own, and your partner their own sign-in, so you can split the work or do it all yourself.`,
    included:
      'Every applicant in your household on one account — and with the Family add-on, a file each and a sign-in for your partner',
    extras: `There is no renewal and no per-document charge. The only optional extra is the Family add-on, ${FAMILY_ADDON_PRICE} once.`,
  };
}
