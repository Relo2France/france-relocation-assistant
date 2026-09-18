/**
 * Sign out through WordPress's own nonced link. Without it (an older page
 * template), fall back to the login screen's logout action, which asks the
 * member to confirm rather than failing silently.
 */
export function signOutUrl(): string {
  const data = window.fraPortalData;
  // WordPress escapes the separators for HTML; the link needs plain "&".
  if (data?.logoutUrl) return data.logoutUrl.replace(/&amp;/g, '&');
  const site = (data?.siteUrl || window.location.origin).replace(/\/$/, '');
  return `${site}/wp-login.php?action=logout`;
}
