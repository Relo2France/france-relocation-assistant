import type { ReactElement } from 'react';
import { Home } from './pages/Home';
import { GuideIndex } from './pages/GuideIndex';
import { Guide } from './pages/Guide';
import { NotFound } from './pages/NotFound';
import { HowItWorks } from './pages/HowItWorks';
import { Pricing } from './pages/Pricing';
import { guideBySlug } from './content/guides';

export interface PageMeta {
  title: string;
  description: string;
  /** Absolute canonical URL. */
  canonical: string;
  /** schema.org JSON-LD, or null where none is warranted. */
  jsonLd: object | null;
  /** Keep this page out of the index. Only the 404 should set it. */
  noindex?: boolean;
}

export const SITE = 'https://relo2france.com';
const SITE_NAME = 'Relo2France';

/**
 * One place that maps a path to both its component and its <head>.
 *
 * Keeping them together is deliberate: the prerender script and the browser
 * must agree, and the commonest way that breaks is meta drifting from content.
 */
export function resolveRoute(path: string): { element: ReactElement; meta: PageMeta } | null {
  const clean = path.endsWith('/') ? path : `${path}/`;

  if (clean === '/') {
    return {
      element: <Home />,
      meta: {
        title: `${SITE_NAME} — Moving to France, in the order you need it`,
        description:
          'Every requirement for an American moving to France, sequenced by when you need it and re-checked against official French sources every week.',
        canonical: `${SITE}/`,
        jsonLd: {
          '@context': 'https://schema.org',
          '@type': 'WebSite',
          name: SITE_NAME,
          url: `${SITE}/`,
        },
      },
    };
  }

  if (clean === '/guides/') {
    return {
      element: <GuideIndex />,
      meta: {
        title: `Guides — ${SITE_NAME}`,
        description:
          'Guides for Americans relocating to France: visas, property, healthcare, tax and banking, in the order you will need them.',
        canonical: `${SITE}/guides/`,
        jsonLd: null,
      },
    };
  }

  if (clean === '/how-it-works/') {
    return {
      element: <HowItWorks />,
      meta: {
        title: `How it works — ${SITE_NAME}`,
        description:
          'How Relo2France works: free guides drawn from official French sources, then a dated task list and an assistant that answers against your own file.',
        canonical: `${SITE}/how-it-works/`,
        jsonLd: null,
      },
    };
  }

  if (clean === '/pricing/') {
    return {
      element: <Pricing />,
      meta: {
        title: `Pricing — ${SITE_NAME}`,
        description:
          'Relo2France costs $35 once, for lifetime access. The guides are free; membership adds your own dated file, documents and the assistant.',
        canonical: `${SITE}/pricing/`,
        jsonLd: {
          '@context': 'https://schema.org',
          '@type': 'Product',
          name: `${SITE_NAME} Lifetime Membership`,
          description:
            'Lifetime access to the Relo2France member portal: a dated relocation plan, document tools and an assistant answering against your own file.',
          offers: {
            '@type': 'Offer',
            price: '35',
            priceCurrency: 'USD',
            url: `${SITE}/pricing/`,
            availability: 'https://schema.org/InStock',
          },
        },
      },
    };
  }

  const match = clean.match(/^\/guides\/([a-z0-9-]+)\/$/);
  const guide = match ? guideBySlug(match[1]!) : undefined;

  if (guide) {
    return {
      element: <Guide guide={guide} />,
      meta: {
        title: `${guide.title} — ${SITE_NAME}`,
        description: guide.description,
        canonical: `${SITE}/guides/${guide.slug}/`,
        jsonLd: {
          '@context': 'https://schema.org',
          '@type': 'Article',
          headline: guide.title,
          description: guide.description,
          dateModified: `${guide.verified}-01`,
          publisher: { '@type': 'Organization', name: SITE_NAME },
          mainEntityOfPage: `${SITE}/guides/${guide.slug}/`,
        },
      },
    };
  }

  return null;
}

/** The 404 page. Not in `routes`, so it never reaches the sitemap. */
export function notFoundRoute(): { element: ReactElement; meta: PageMeta } {
  return {
    element: <NotFound />,
    meta: {
      title: `Page not found — ${SITE_NAME}`,
      description: 'That page is not here. Browse the guides for Americans relocating to France.',
      canonical: `${SITE}/404`,
      jsonLd: null,
      noindex: true,
    },
  };
}
