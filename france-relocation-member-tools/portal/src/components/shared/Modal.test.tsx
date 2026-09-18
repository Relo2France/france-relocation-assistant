import { useState } from 'react';
import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import Modal, { Drawer } from './Modal';

function Harness({ drawer = false }: { drawer?: boolean }) {
  const [open, setOpen] = useState(false);
  const Dialog = drawer ? Drawer : Modal;
  return (
    <>
      <button type="button" onClick={() => setOpen(true)}>Open it</button>
      <Dialog isOpen={open} onClose={() => setOpen(false)} title="Edit letter">
        <input aria-label="Body" />
      </Dialog>
    </>
  );
}

describe.each([false, true])('dialog behaviour (drawer: %s)', (drawer) => {
  it('is a labelled modal dialog that takes focus and gives it back on Escape', () => {
    render(<Harness drawer={drawer} />);
    const opener = screen.getByRole('button', { name: 'Open it' });
    opener.focus();
    fireEvent.click(opener);

    const dialog = screen.getByRole('dialog', { name: 'Edit letter' });
    expect(dialog).toHaveAttribute('aria-modal', 'true');
    expect(dialog.contains(document.activeElement)).toBe(true);

    fireEvent.keyDown(document, { key: 'Escape' });
    expect(screen.queryByRole('dialog')).toBeNull();
    expect(document.activeElement).toBe(opener);
  });
});
