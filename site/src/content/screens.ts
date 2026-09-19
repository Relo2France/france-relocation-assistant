/**
 * Screenshots of the member portal, for the public pages.
 *
 * Every one shows the invented Ellis household (Jordan and Sam, retiring to
 * Bordeaux on the visitor visa). They are produced from the real portal code
 * with demo data by france-relocation-member-tools/portal/demo; no member's
 * information is ever used. Regenerate them there when the portal changes,
 * then convert to WebP into site/public/screens/.
 */
export interface Screen {
  src: string;
  alt: string;
  title: string;
  caption: string;
  width: number;
  height: number;
}

const desktop = (name: string) => ({ src: `/screens/${name}.webp`, width: 1600, height: 1000 });

export const SCREENS = {
  dashboard: {
    ...desktop('dashboard'),
    title: 'Where you are',
    caption: 'Your stage, the months to go, the one step to do next, and your dossier at a glance.',
    alt: 'The member portal home: stage 2 of 6, Prepare, five months to the move, the next step to do, and the dossier progress.',
  },
  stage: {
    ...desktop('stage'),
    title: 'Every step, dated from your move',
    caption: 'Each stage lists its steps in order, for you and for your partner, with the guides for that stage and when to bring in a professional.',
    alt: 'The Prepare stage page listing steps with due dates, a filter for each person, and a panel on when to bring in a tax professional.',
  },
  step: {
    ...desktop('step'),
    title: 'How to do each step',
    caption: 'Where to go, what to bring, how long it takes and what it costs, written for your state.',
    alt: 'A step opened in a side panel, explaining how to get apostilles in North Carolina, with links to the official directories.',
  },
  documents: {
    ...desktop('documents'),
    title: 'Letters drafted for you',
    caption: 'The cover letter, the declaration not to work and the statement of resources, drafted from your profile as PDFs you can edit and sign.',
    alt: 'The Documents page with the letters for a visitor-visa application: a drafted cover letter with Open, PDF and Edit buttons, and the other letters ready to draft.',
  },
  chat: {
    ...desktop('chat'),
    title: 'Answers about your own case',
    caption: 'Ask in plain English. The answer uses your file and the official figures, not a generic article.',
    alt: 'The case assistant answering how much a retired couple must show for the visitor visa, against the 2026 minimum-wage benchmark.',
  },
  family: {
    ...desktop('family'),
    title: 'One file per person',
    caption: 'Your partner gets their own sign-in to the same household file, and you can hand them their steps.',
    alt: 'The Family plans page showing the account holder and their partner, the partner’s documents and the steps assigned to them.',
  },
  deadlines: {
    ...desktop('deadlines'),
    title: 'Deadlines, month by month',
    caption: 'Every dated step across all six stages, overdue first, so nothing slips.',
    alt: 'The Deadlines page listing steps by month from September to November 2026, with finished steps struck through.',
  },
  phone: {
    src: '/screens/phone-dashboard.webp',
    width: 780,
    height: 1688,
    title: 'On your phone',
    caption: 'The same file, laid out for a phone.',
    alt: 'The portal home on a phone: the stage, the road to France and the next step.',
  },
} satisfies Record<string, Screen>;

export const SAMPLE_NOTE = 'Screens show a sample household. No member’s information is ever shown.';
