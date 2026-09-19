/**
 * One date style for the whole portal, in American order with the month
 * named so it can never be misread: "Sep 12, 2026".
 *
 * Dates arrive as "2026-09-12", as MySQL timestamps ("2026-09-12 14:03:00")
 * or as ISO strings. A calendar date is read from its first ten characters
 * at UTC midnight, so a due date never slips a day in a western time zone.
 */

type DateInput = string | Date | null | undefined;

function calendarDate(value: DateInput): Date | null {
  if (!value) return null;
  if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value;
  const d = new Date(`${value.slice(0, 10)}T00:00:00Z`);
  return Number.isNaN(d.getTime()) ? null : d;
}

/** "Sep 12, 2026" */
export function formatDate(value: DateInput): string {
  const d = calendarDate(value);
  return d ? d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' }) : '';
}

/** "Sep 12", where the year is plain from the context. */
export function formatShortDate(value: DateInput): string {
  const d = calendarDate(value);
  return d ? d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', timeZone: 'UTC' }) : '';
}

/** "SEP 12", the small capitals used beside a step. */
export function formatDue(value: DateInput): string {
  return formatShortDate(value).toUpperCase();
}

/** "September 2026", for month headings and month-level dates. */
export function formatMonth(value: DateInput): string {
  const d = calendarDate(value);
  return d ? d.toLocaleDateString('en-US', { month: 'long', year: 'numeric', timeZone: 'UTC' }) : '';
}

/** "Sep 12, 2026 at 2:03 PM", for an exact moment in the viewer's own time. */
export function formatDateTime(value: DateInput): string {
  if (!value) return '';
  const d = value instanceof Date ? value : new Date(value.includes('T') ? value : value.replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return '';
  const day = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  const time = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
  return `${day} at ${time}`;
}
