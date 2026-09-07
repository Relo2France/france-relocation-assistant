# Relo2France Site

The public site's design system and pages. Built from **The Settled Direction**.

Lives outside the GitHub Sync plugin allowlist, so nothing here deploys to
WordPress.

## The rule

One principle governs the whole system. Break it and the design stops meaning
anything:

- **Chrome is warm.** Navigation, timeline, buttons, the portal shell. Colour,
  roundness, Fraunces. Reassurance belongs here.
- **Content is exact.** Inside an article: serif at reading size, hairline
  rules, monospaced figures, no decoration. This is the part people act on.
- **Honey means "not law".** One accent, one meaning: lived experience, or a
  deadline. Never an official requirement.

`src/components/components.test.tsx` enforces the third point. If a practice
note ever renders like a requirement, or a community source in the brand
colour, those tests fail.

## Tokens

`src/styles/tokens.css` is the single source of truth. Tailwind reads the
custom properties rather than duplicating hex values, so dark mode needs no
`dark:` variants - the variables swap underneath.

Every colour is declared on bare `:root` and only *redefined* in the dark
blocks. A colour defined only inside a dark block silently fails in the
un-stamped "system" state most visitors are in; `src/styles/tokens.test.ts`
makes that impossible to ship.

## Components

| Component | Job |
|---|---|
| `Button` | Actions. Uses `--on-brand` so it stays legible in both themes |
| `Label` | Uppercase eyebrow. Tone carries meaning |
| `SourceChip` | Official vs community, coloured by how far it can be trusted |
| `Timeline` | The move as a countdown, with a marked current step |
| `Dossier` | The folder being assembled, with a ready count |
| `Requirements` / `Requirement` / `Figure` / `Caveat` | Official content, deliberately plain |
| `PracticeNote` | Lived experience, visibly not law |
| `GuideCard` | A guide in the index, with its lead time |
| `SiteNav` | Warm chrome |

## Commands

    npm install
    npm run dev         # local
    npm test            # tokens + components
    npm run typecheck
    npm run build

## Prerendering

`npm run build` compiles, then runs `scripts/prerender.mjs`, which renders each
route's real React component to HTML and writes a static shell carrying the
full article plus its `<head>` meta and JSON-LD. The app script stays, so
browsers hydrate and behave as an SPA from there.

This is not optional polish. `cgp-site` shipped client-rendered content pages
and Google flagged ~136 URLs as Soft 404 - the crawler saw a title and an empty
div. `src/content/prerender.test.ts` reads the built output and fails if a page
ever ships as a shell again.

The prerender step refuses to write a page whose route does not resolve, rather
than emitting an empty one.

### URLs are pinned

Guide slugs match the URLs already indexed on relo2france.com, so the cutover
needs no redirects for these pages. A test pins the list: changing a slug costs
a ranking, so it should be deliberate rather than a rename that slipped through.

## Also generated at build

- `404.html` - prerendered like any other page, but `noindex` and absent from
  the sitemap. It offers the guides rather than dead-ending, because most
  people arrive here from a stale link.
- `sitemap.xml` - every route, with `lastmod` on guides taken from when their
  content was last verified. That is the only honest freshness signal we have.
- `robots.txt` - allows everything and points at the sitemap.

Your host needs telling to serve `404.html` for unmatched paths. On Cloudflare
that is `not_found_handling` in the assets config.

## Not done yet

- The remaining guide bodies are stubs. Structure and metadata are real; the
  prose needs writing or porting. A prerendered guide is currently ~1,500
  characters against ~58,000 on the live WordPress page.
- No deployment. The site is not wired to Cloudflare yet.
- Four typefaces is a lot. Dropping Karla and letting Fraunces label is the
  first cut if load time matters.
