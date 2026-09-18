/**
 * Sign out through WordPress's own nonced link. Without it (an older page
 * template), fall back to the login screen's logout action, which asks the
 * member to confirm rather than failing silently.
 */
export function signOutUrl(): string {
  const data = window.fraPortalData;
  if (data?.logoutUrl) return data.logoutUrl;
  const site = (data?.siteUrl || window.location.origin).replace(/\/$/, '');
  return `${site}/wp-login.php?action=logout`;
}
