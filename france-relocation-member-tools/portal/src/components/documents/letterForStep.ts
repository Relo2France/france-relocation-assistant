/**
 * Which drafted letter a plan step is about, by the step's title. The
 * titles come from the plan templates in class-framt-portal-api.php; a
 * step the portal can draft for gets a "Draft it for me" button.
 */
const BY_TITLE: Record<string, string> = {
  'Write the cover letter': 'cover-letter',
  'Professional cover letter for your visa application': 'cover-letter',
  'Sign declaration not to work': 'no-work-declaration',
  'Prepare proof of financial resources': 'resources-statement',
  'Declaration of financial resources': 'resources-statement',
  'Prove financial resources for studies': 'resources-statement',
  'Collect proof the relationship is genuine': 'relationship-statement',
  'Write the business plan and viability file': 'business-plan',
  'Prepare business plan': 'business-plan',
  'Assemble the founder file: the innovative project, its recognition and the funds': 'business-plan',
  'Confirm how remote work for a US employer is treated in France': 'employer-remote-letter',
  'Accommodation certificate from your host': 'host-attestation',
};

export function letterForStep(title: string | undefined | null): string | null {
  return title ? BY_TITLE[title.trim()] ?? null : null;
}
