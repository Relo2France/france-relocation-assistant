/**
 * The terms, privacy notice and refund policy.
 *
 * Written to how the service actually works today: WordPress.com hosting,
 * Cloudflare in front, MemberPress with Stripe for payment, member files stored
 * privately, the assistant and document tools running on Anthropic's Claude
 * API, email controls and account deletion in the portal's Settings. If any of
 * that changes, this file changes with it - a policy that describes a
 * different service is worse than none.
 *
 * Plain English on purpose. Approved by the owner on 18 September 2026;
 * not reviewed by a lawyer. Change it here when the service changes.
 */
import { familyCopy, PRICE, REFUND_DAYS, SUPPORT_EMAIL } from './links';

export const LEGAL_UPDATED = '18 September 2026';

export interface LegalSection {
  heading: string;
  paragraphs: string[];
  list?: string[];
}

export interface LegalPage {
  path: string;
  /** Page heading and <title> stem. */
  title: string;
  /** Short text for links in the footer and on the pricing page. */
  linkText: string;
  description: string;
  intro: string;
  sections: LegalSection[];
}

const CONTACT = `Email ${SUPPORT_EMAIL}, or, if you are a member, write to us from Support in the portal.`;

const terms: LegalPage = {
  path: '/terms/',
  title: 'Terms of Service',
  linkText: 'Terms',
  description:
    'The terms for using Relo2France: the free guides, the lifetime membership, the AI assistant, and what the service is not.',
  intro:
    'These terms cover your use of relo2france.com: the free guides, and the member portal you get with a membership. By creating an account or buying a membership you agree to them. “We” means Relo2France.',
  sections: [
    {
      heading: 'What Relo2France is',
      paragraphs: [
        'Relo2France organises the official requirements for Americans moving to France, in the order you will meet them. The guides are free to read. Membership adds a member portal: a plan dated from your move, a dossier per person, documents drafted from your details, an assistant that answers about your situation, research reports, and a Schengen day tracker.',
      ],
    },
    {
      heading: 'It is information, not advice',
      paragraphs: [
        'Everything on the site and in the portal is general information. It is not legal, immigration, tax or financial advice, and using it does not create a lawyer–client or any other professional relationship.',
        'Relo2France is not affiliated with the French government, any French consulate, France-Visas, TLScontact or any other authority. Decisions on visas, residence permits, tax and healthcare are made by those authorities alone, and we cannot promise any outcome.',
        'French requirements change, and they vary by consulate and by prefecture. We re-check our sources regularly, but you should confirm anything you rely on with the official source or your consulate, and for your specific circumstances consult an immigration lawyer, a tax professional or a notaire.',
      ],
    },
    {
      heading: 'AI features',
      paragraphs: [
        'The assistant, document checks, drafted letters and research reports are produced with AI (Anthropic’s Claude). AI can be wrong or out of date. Read everything it produces before you use it, and check facts that matter against the official source. You are responsible for any document you sign or submit.',
      ],
    },
    {
      heading: 'Your membership',
      paragraphs: [
        `Membership costs ${PRICE}, paid once. It gives lifetime access to the member portal: access for as long as Relo2France operates the service, with no subscription and no renewal fee. Payment is taken by Stripe through our MemberPress checkout; we never see or store your full card number.`,
        familyCopy().cardNote,
        `The ${REFUND_DAYS}-day money-back guarantee is set out in our refund policy.`,
      ],
    },
    {
      heading: 'Your account',
      paragraphs: [
        'Give accurate information, keep your sign-in details to yourself, and tell us if you think someone else has used your account. A membership is for one household: you, and a partner you invite to share the file. Do not share access beyond that.',
        'You keep ownership of everything you enter or upload. You allow us to store and process it only to run the service for you, as the privacy notice describes.',
      ],
    },
    {
      heading: 'Fair use',
      paragraphs: ['Please do not:'],
      list: [
        'copy, scrape or resell the guides or the knowledge base',
        'try to get into another member’s file or interfere with the service',
        'upload anything unlawful, or anything you have no right to share',
        'use the assistant to produce false documents or mislead any authority',
      ],
    },
    {
      heading: 'Ending your account',
      paragraphs: [
        'You can delete your account at any time from Settings in the portal; this permanently removes your file. We may suspend or close an account that breaks these terms, and will tell you why.',
      ],
    },
    {
      heading: 'Our responsibility',
      paragraphs: [
        'We work to keep the service accurate and available, but it is provided as it is, without guarantees that it will be error-free or uninterrupted. To the extent the law allows, we are not liable for losses arising from reliance on the information or tools, and our total liability to you is limited to the amount you paid us. Nothing in these terms limits rights you have under the law that cannot be limited.',
      ],
    },
    {
      heading: 'Changes',
      paragraphs: [
        'If we change these terms we will update this page and its date. For a significant change we will also tell members by email or in the portal.',
      ],
    },
    { heading: 'Contact', paragraphs: [CONTACT] },
  ],
};

const privacy: LegalPage = {
  path: '/privacy/',
  title: 'Privacy Notice',
  linkText: 'Privacy',
  description:
    'What Relo2France collects, where it is stored, who processes it, and how to control your emails or delete your account.',
  intro:
    'A relocation file holds sensitive details, so this notice says plainly what we collect, why, where it goes and how to remove it.',
  sections: [
    {
      heading: 'What we collect',
      paragraphs: ['Only what you give us, plus what is needed to run the site:'],
      list: [
        'Account details: your name, email address and sign-in details.',
        'Your relocation profile, for you and anyone you add to your household: names, dates and places of birth, passport numbers and expiry dates, addresses in the US and France, your visa route, move dates, and the work, income and family details you choose to enter so tasks and documents fit your case.',
        'Documents you upload, such as passport scans, certificates and statements.',
        'What you write: questions to the assistant, messages to Support, notes and task progress.',
        'Payment records: that you paid, when and how much. Stripe handles the card itself; we never see or store your full card number.',
        'Technical data: server logs (such as IP address and browser type) and the cookies that keep you signed in.',
      ],
    },
    {
      heading: 'Why we use it',
      paragraphs: [
        'To run the service you signed up for: dating your plan, tracking your dossier, drafting your documents, answering your questions and sending the emails you have chosen. We do not sell your information and do not use it for advertising.',
      ],
    },
    {
      heading: 'Where it is stored',
      paragraphs: [
        'The member portal and your file run on WordPress, hosted by WordPress.com. Cloudflare serves the public site and passes portal traffic through to WordPress.com. Uploaded documents are stored in a private folder for your account that is not publicly reachable, and they are served only to you when you are signed in.',
      ],
    },
    {
      heading: 'AI processing',
      paragraphs: [
        'When you use the assistant, a document check, a drafted letter or a research report, the relevant question, profile details and document content are sent to Anthropic’s Claude API to produce the answer. Anthropic processes that content to provide the service under its commercial terms, which do not allow it to train its models on this data by default. If you would rather a document were not processed this way, do not submit it for checking.',
      ],
    },
    {
      heading: 'Who else processes it',
      paragraphs: ['Only the services that run Relo2France:'],
      list: [
        'WordPress.com (Automattic), which hosts the site and your file',
        'Cloudflare, which serves the public site and carries traffic to it',
        'Stripe, which takes payment through our MemberPress checkout',
        'Anthropic, which runs the AI features',
        'Google Fonts, which serves the typefaces on the public site and so sees your IP address when a page loads',
      ],
    },
    {
      heading: 'Emails',
      paragraphs: [
        'Sign-in links, password emails and invitations are always sent, because the account does not work without them. Everything else is yours to control in Settings, under Notifications: email updates when we write to you or finish a report, step reminders as deadlines approach, and a weekly digest. Turn them off and the same messages still appear in the portal.',
      ],
    },
    {
      heading: 'Your household',
      paragraphs: [
        'If you invite a partner, they sign in to the same household file and can see and edit what it holds. Details you enter for your children are part of your file; the service is for adults, and we do not knowingly take accounts from anyone under 18.',
      ],
    },
    {
      heading: 'Keeping and deleting it',
      paragraphs: [
        'We keep your file for as long as you have an account. You can delete your account at any time from Settings in the portal, which permanently removes your profile, documents, tasks and progress; there is also an option to clear your relocation data and keep the account. Payment records are kept as tax and accounting rules require, and copies may remain in our host’s backups for a limited period before they are overwritten.',
      ],
    },
    {
      heading: 'Your rights',
      paragraphs: [
        'You can ask to see, correct, export or delete the information we hold about you. If you live in France or elsewhere in the EU, the GDPR gives you these rights and more, including the right to complain to the CNIL, France’s data protection authority. Residents of some US states have similar rights. Write to us and we will respond within a month.',
      ],
    },
    {
      heading: 'Security',
      paragraphs: [
        'Connections to the site are encrypted, portal data is available only to the signed-in account holder and their invited partner, and uploaded files are kept outside public reach. No system is perfectly secure; if a breach affected your information we would tell you.',
      ],
    },
    {
      heading: 'Changes',
      paragraphs: ['If this notice changes we will update this page and its date, and tell members about significant changes.'],
    },
    { heading: 'Contact', paragraphs: [CONTACT] },
  ],
};

const refund: LegalPage = {
  path: '/refund-policy/',
  title: 'Refund Policy',
  linkText: 'Refund policy',
  description: `Relo2France's ${REFUND_DAYS}-day money-back guarantee on the ${PRICE} lifetime membership: how to ask, and what happens next.`,
  intro: `The ${PRICE} lifetime membership comes with a ${REFUND_DAYS}-day money-back guarantee. If it is not for you, you get every cent back.`,
  sections: [
    {
      heading: 'How it works',
      paragraphs: [],
      list: [
        `Ask within ${REFUND_DAYS} days of buying your membership.`,
        'You do not need a reason, and there is no form: an email or a Support message is enough.',
        'The refund is the full amount you paid. There are no partial refunds and no deductions.',
        'It goes back to the card you paid with, through Stripe. Stripe sends it straight away; your card issuer usually shows it within 5 to 10 business days.',
        'Your membership ends when the refund is made. You can delete your file yourself from Settings in the portal, before or after.',
      ],
    },
    {
      heading: `After ${REFUND_DAYS} days`,
      paragraphs: [
        `After the ${REFUND_DAYS} days the payment is not refundable, except where the law requires otherwise. Because membership is a single payment for lifetime access, there is never a renewal charge to cancel.`,
      ],
    },
    { heading: 'How to ask', paragraphs: [CONTACT] },
  ],
};

export const LEGAL_PAGES: LegalPage[] = [terms, privacy, refund];

export function legalPageByPath(path: string): LegalPage | undefined {
  return LEGAL_PAGES.find((p) => p.path === path);
}
