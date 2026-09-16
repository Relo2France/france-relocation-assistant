/**
 * The glossary "?" must never take a page down. The API's shape differs from
 * the type in @/types, and an entry with no name is a real possibility.
 */
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const glossary = vi.hoisted(() => ({ data: undefined as unknown }));
vi.mock('@/hooks/useApi', () => ({ useGlossary: () => ({ data: glossary.data }) }));

import Jargon from './Jargon';

describe('Jargon', () => {
  it('renders plain text while the glossary is missing', () => {
    glossary.data = undefined;
    render(<Jargon text="Order the apostille" />);
    expect(screen.getByText(/Order the apostille/)).toBeInTheDocument();
    expect(screen.queryByRole('button')).toBeNull();
  });

  it('reads the API shape (term/definition) and survives a broken entry', () => {
    glossary.data = [
      { id: 'docs', title: 'Documents', terms: [{ term: 'Apostille', definition: 'A certificate that authenticates a document.' }, { definition: 'no name' }, null] },
    ];
    render(<Jargon text="Order the apostille for your certificate" />);
    expect(screen.getByRole('button', { name: /what is apostille/i })).toBeInTheDocument();
  });

  it('also reads the older title/short shape', () => {
    glossary.data = [{ id: 'x', title: 'X', terms: [{ title: 'VLS-TS', short: 'Long-stay visa that is also a permit.' }] }];
    render(<Jargon text="Validate your VLS-TS" />);
    expect(screen.getByRole('button', { name: /what is vls-ts/i })).toBeInTheDocument();
  });
});
