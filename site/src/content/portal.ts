/**
 * What the portal adds.
 *
 * The public site's job is to be found and to show the value; the portal's job
 * is to do the work. Every item here is a feature that exists today - claiming
 * anything the portal cannot actually do would be the fastest way to lose the
 * trust the guides are built to earn.
 */
export interface PortalFeature {
  name: string;
  what: string;
  /**
   * Set only for features that do not exist yet. Everything without it is
   * live today - see the note above about not claiming what the portal cannot do.
   */
  status?: 'soon';
  /** What the same subject looks like on the public site, for contrast. */
  publicVersion: string;
}

export const portalFeatures: PortalFeature[] = [
  {
    name: 'Ask about your own situation',
    what: 'The assistant knows your visa route, your dates, who is applying and what you have already gathered — so answers are about you, not about visas in general.',
    publicVersion: 'The guides answer the general question.',
  },
  {
    name: 'Your dossier, tracked',
    what: 'Every document the consulate will want, with what is done, what is outstanding and what needs an apostille first.',
    publicVersion: 'The guides list what is required.',
  },
  {
    name: 'Dated from your move',
    what: 'Tasks and deadlines counted back from your actual date, so you find out an apostille takes weeks while there is still time.',
    publicVersion: 'The guides say roughly when to start.',
  },
  {
    name: 'Documents drafted for you',
    what: 'Cover letters, financial statements and the declaration of no professional activity, filled in from your profile.',
    publicVersion: 'The guides describe what each one has to say.',
  },
  {
    name: 'Schengen day counting',
    status: 'soon',
    what: 'Trips logged against the 90-in-180 rule, so you know where you stand before you book. Built as a standalone tool first, then included in membership at no extra cost.',
    publicVersion: 'The guides explain the rule.',
  },
  {
    name: 'Research on demand',
    what: 'Reports on a commune, a region or a specific requirement, written against current official sources and saved to your file.',
    publicVersion: 'The guides cover the common cases.',
  },
];
