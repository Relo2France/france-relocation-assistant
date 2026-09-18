/**
 * LettersSection
 *
 * The letters a member's visa route calls for, drafted from their profile
 * and a few answers, kept as PDFs in Documents. The consulate and the
 * mailing address come first because every letter uses them; they are
 * profile fields, so what is typed here shows on the Profile page too.
 * Each letter can be opened, downloaded, reworded (the PDF follows the
 * words) and redrafted when the profile moves on.
 */
import { useEffect, useMemo, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { clsx } from 'clsx';
import { AlertTriangle, Download, ExternalLink, FileSignature, Pencil, RefreshCw, Trash2 } from 'lucide-react';
import { CompactErrorFallback } from '@/components/shared/ErrorBoundary';
import Modal from '@/components/shared/Modal';
import { useDeleteFile, useDraftLetter, useEditLetter, useLetters, useSaveLetterAnswers } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type { Letter } from '@/types';
import { FieldInput, IncomeSummary } from './LetterFields';

const ROUTE_NAMES: Record<string, string> = {
  visitor: 'visitor',
  retiree: 'visitor (retiree)',
  student: 'student',
  employee: 'work',
  talent_passport: 'talent passport',
  entrepreneur: 'entrepreneur',
  spouse_french: 'spouse of a French national',
  family: 'family reunification',
  other: 'long-stay',
};

const GROUPS: { id: string; label: string }[] = [
  { id: 'stay', label: 'Where and when' },
  { id: 'money', label: 'Money' },
  { id: 'words', label: 'In your own words' },
  { id: 'details', label: 'Details' },
];

const letterKey = (l: Pick<Letter, 'type' | 'person'>) => (l.person === 'partner' ? `${l.type}|partner` : l.type);

function formatDate(value: string | null | undefined): string {
  if (!value) return '';
  const d = new Date(value.replace(' ', 'T'));
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

export default function LettersSection({ projectId }: { projectId: number }) {
  const { data, isLoading, isError, refetch } = useLetters();
  const saveAnswers = useSaveLetterAnswers();
  const draft = useDraftLetter();
  const edit = useEditLetter();
  const deleteFile = useDeleteFile();
  const queryClient = useQueryClient();
  const { setActiveView, setProfileSection, openLetter, setOpenLetter } = usePortalStore();

  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [open, setOpen] = useState<string | null>(null);
  const [editingFirst, setEditingFirst] = useState(false);
  const [editing, setEditing] = useState<Letter | null>(null);
  const [editText, setEditText] = useState('');
  const [error, setError] = useState<string | null>(null);
  const sectionRef = useRef<HTMLDivElement>(null);

  // What the server holds is the starting point for the form.
  useEffect(() => {
    if (data?.answers) setAnswers({ ...data.answers });
  }, [data?.answers]);

  // Arriving from a step's "Draft it for me": open that letter.
  useEffect(() => {
    if (!openLetter || !data) return;
    const match = data.letters.find((l) => letterKey(l) === openLetter || l.type === openLetter);
    if (match) {
      setOpen(letterKey(match));
      window.setTimeout(() => document.getElementById(`letter-${letterKey(match)}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
    }
    setOpenLetter(null);
  }, [openLetter, data, setOpenLetter]);

  const first = useMemo(() => data?.first ?? ['consulate', 'mailing_address'], [data?.first]);
  const firstMissing = useMemo(() => first.some((k) => !(data?.answers?.[k] ?? '').trim()), [first, data?.answers]);
  const firstDirty = first.some((k) => (answers[k] ?? '') !== (data?.answers?.[k] ?? ''));

  if (isLoading) {
    return <div className="card p-6 mb-6 animate-pulse h-40" aria-hidden="true" />;
  }
  if (isError || !data) {
    return (
      <div className="card mb-6">
        <CompactErrorFallback onRetry={() => void refetch()} message="Your letters could not be loaded." />
      </div>
    );
  }

  if (data.letters.length === 0) {
    return (
      <section className="card p-6 mb-6" aria-labelledby="letters-heading">
        <h2 id="letters-heading" className="font-semibold text-gray-900">Letters for your application</h2>
        <p className="text-sm text-gray-600 mt-1 max-w-[60ch]">
          Choose your visa route in your profile and the letters it calls for appear here, drafted from your details.
        </p>
        <button
          type="button"
          className="btn btn-secondary btn-sm mt-4"
          onClick={() => { setProfileSection('visa'); setActiveView('profile'); }}
        >
          Choose a visa route
        </button>
      </section>
    );
  }

  const setAnswer = (key: string, value: string) => setAnswers((prev) => ({ ...prev, [key]: value }));
  // An unanswered question shows its default (the move date, the stay wording).
  const valueFor = (key: string) => answers[key] || data.form?.defaults?.[key] || '';

  const onlyChanged = (keys: string[]) => {
    const out: Record<string, string> = {};
    for (const k of keys) {
      if ((answers[k] ?? '') !== (data.answers?.[k] ?? '')) out[k] = answers[k] ?? '';
    }
    return out;
  };

  const saveFirst = () => {
    setError(null);
    saveAnswers.mutate(onlyChanged(first), {
      onSuccess: () => setEditingFirst(false),
      onError: (e) => setError(e instanceof Error ? e.message : 'Those details could not be saved.'),
    });
  };

  const runDraft = (letter: Letter) => {
    setError(null);
    if (letter.file?.edited && !window.confirm(`Redraft “${letter.title}” from your profile and answers? This replaces the wording you edited.`)) {
      return;
    }
    draft.mutate(
      { type: letter.type, person: letter.person, answers: onlyChanged([...first, ...letter.fields]) },
      {
        onSuccess: () => setOpen(null),
        onError: (e) => setError(e instanceof Error ? e.message : 'The letter could not be drafted.'),
      }
    );
  };

  const startEdit = (letter: Letter) => {
    setEditing(letter);
    setEditText(letter.file?.text ?? '');
  };

  const saveEdit = () => {
    if (!editing?.file) return;
    setError(null);
    edit.mutate(
      { fileId: editing.file.id, text: editText },
      {
        onSuccess: () => setEditing(null),
        onError: (e) => setError(e instanceof Error ? e.message : 'Your changes could not be saved.'),
      }
    );
  };

  const remove = (letter: Letter) => {
    if (!letter.file) return;
    if (!window.confirm(`Delete your draft of “${letter.title}”? You can draft it again any time.`)) return;
    deleteFile.mutate(
      { id: letter.file.id, projectId },
      { onSuccess: () => queryClient.invalidateQueries({ queryKey: ['letters'] }) }
    );
  };

  const consulate = data.answers?.consulate ?? '';
  const mailing = (data.answers?.mailing_address ?? '').split('\n').filter(Boolean).join(', ');
  const showFirstForm = firstMissing || editingFirst;
  const busyKey = draft.isPending ? (draft.variables ? letterKey(draft.variables) : null) : null;

  return (
    <section ref={sectionRef} className="card mb-6" aria-labelledby="letters-heading">
      <div className="px-4 sm:px-6 py-4 border-b border-rule">
        <h2 id="letters-heading" className="font-semibold text-gray-900 flex items-center gap-2">
          <FileSignature className="w-5 h-5 text-primary-600" aria-hidden="true" />
          Letters for your application
        </h2>
        <p className="text-sm text-gray-600 mt-1 max-w-[65ch]">
          Drafted from your profile for the {ROUTE_NAMES[data.visa_type] ?? 'long-stay'} route. Download, print and sign each one. You can change the wording any time, and the PDF follows your words.
        </p>
      </div>

      {/* Asked first: every letter is addressed from and to these. */}
      <div className={clsx('px-4 sm:px-6 py-4 border-b border-rule', firstMissing && 'bg-primary-50')}>
        {showFirstForm ? (
          <div>
            <p className="eyebrow mb-1">{firstMissing ? 'Start here' : 'Your details for the letters'}</p>
            <p className="text-sm text-gray-600 mb-3 max-w-[65ch]">
              Every letter is addressed to your consulate and carries your mailing address. Both are saved to your profile.
            </p>
            <div className="grid gap-4 md:grid-cols-2">
              {first.map((key) => (
                <FieldInput
                  key={key}
                  id={`letters-first-${key}`}
                  fieldKey={key}
                  field={data.fields[key]}
                  value={answers[key] ?? ''}
                  onChange={(v) => setAnswer(key, v)}
                />
              ))}
            </div>
            <div className="flex flex-wrap items-center gap-3 mt-4">
              <button type="button" className="btn btn-primary btn-sm" onClick={saveFirst} disabled={saveAnswers.isPending || !firstDirty}>
                {saveAnswers.isPending ? 'Saving…' : 'Save to my profile'}
              </button>
              {!firstMissing ? (
                <button type="button" className="btn btn-ghost btn-sm" onClick={() => { setEditingFirst(false); setAnswers({ ...data.answers }); }}>
                  Cancel
                </button>
              ) : null}
            </div>
          </div>
        ) : (
          <div className="flex flex-wrap items-start justify-between gap-3">
            <p className="text-sm text-gray-700 min-w-0">
              To the <span className="font-medium">Consulate General of France in {consulate}</span>, from {mailing}.
            </p>
            <button type="button" className="text-sm font-semibold text-primary-600 hover:text-primary-700" onClick={() => setEditingFirst(true)}>
              Change
            </button>
          </div>
        )}
      </div>

      {error ? (
        <p role="alert" className="mx-4 sm:mx-6 mt-4 text-sm text-red-700 bg-red-50 rounded-lg px-3 py-2">{error}</p>
      ) : null}

      <ul className="divide-y divide-rule">
        {data.letters.map((letter) => {
          const key = letterKey(letter);
          const file = letter.file;
          const isOpen = open === key;
          const own = letter.fields.filter((f) => !first.includes(f));
          const busy = busyKey === key;
          return (
            <li key={key} id={`letter-${key}`} className="px-4 sm:px-6 py-4 scroll-mt-20">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                  <h3 className="font-medium text-gray-900 flex flex-wrap items-center gap-2">
                    {letter.title}
                    {letter.optional ? <span className="badge badge-gray">If it applies</span> : null}
                  </h3>
                  <p className="text-sm text-gray-600 mt-0.5 max-w-[65ch]">{letter.why}</p>
                  {file ? (
                    <p className="text-xs text-gray-500 mt-1.5">
                      {file.edited ? `Edited ${formatDate(file.edited_at)}` : `Drafted ${formatDate(file.generated_at)}`} · PDF, {file.size}
                      {file.blanks > 0 ? (
                        <span className="text-ink font-semibold"> · {file.blanks === 1 ? 'one blank' : `${file.blanks} blanks`} still to fill</span>
                      ) : null}
                    </p>
                  ) : (
                    <p className="text-xs text-gray-500 mt-1.5">Not drafted yet</p>
                  )}
                  {file?.stale ? (
                    <p className="text-xs text-gray-700 mt-1 flex items-start gap-1.5">
                      <AlertTriangle className="w-3.5 h-3.5 mt-px shrink-0 text-accent-500" aria-hidden="true" />
                      Your profile or answers have changed since this was drafted.{file.edited ? ' Redrafting replaces your edits.' : ' Redraft to bring it up to date.'}
                    </p>
                  ) : null}
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  {file ? (
                    <>
                      <a href={file.preview_url} target="_blank" rel="noopener noreferrer" className="btn btn-secondary btn-sm" aria-label={`Open ${letter.title}`}>
                        <ExternalLink className="w-4 h-4" aria-hidden="true" />
                        Open
                      </a>
                      <a href={file.download_url} className="btn btn-secondary btn-sm" aria-label={`Download ${letter.title} as a PDF`}>
                        <Download className="w-4 h-4" aria-hidden="true" />
                        PDF
                      </a>
                      <button type="button" className="btn btn-secondary btn-sm" onClick={() => startEdit(letter)} aria-label={`Edit the wording of ${letter.title}`}>
                        <Pencil className="w-4 h-4" aria-hidden="true" />
                        Edit
                      </button>
                      <button
                        type="button"
                        className="btn btn-ghost btn-sm"
                        onClick={() => setOpen(isOpen ? null : key)}
                        aria-expanded={isOpen}
                        aria-controls={`letter-panel-${key}`}
                      >
                        <RefreshCw className="w-4 h-4" aria-hidden="true" />
                        Redraft
                      </button>
                      <button type="button" className="p-2 text-gray-400 hover:text-accent-500" onClick={() => remove(letter)} aria-label={`Delete your draft of ${letter.title}`}>
                        <Trash2 className="w-4 h-4" aria-hidden="true" />
                      </button>
                    </>
                  ) : !isOpen ? (
                    <button
                      type="button"
                      className="btn btn-primary btn-sm"
                      onClick={() => setOpen(key)}
                      aria-expanded={false}
                      aria-controls={`letter-panel-${key}`}
                    >
                      Draft it
                    </button>
                  ) : null}
                </div>
              </div>

              {isOpen ? (
                <div id={`letter-panel-${key}`} className="mt-4 rounded-lg bg-card-2 p-4">
                  {firstMissing ? (
                    <p className="text-sm font-medium text-gray-800 mb-3">Add your consulate and mailing address above first; every letter uses them.</p>
                  ) : null}
                  {own.length > 0 ? (
                    <div className="space-y-6">
                      {GROUPS.map((group) => {
                        const keys = own.filter((k) => (data.form?.groups?.[k] ?? 'details') === group.id);
                        if (keys.length === 0) return null;
                        return (
                          <fieldset key={group.id} className="space-y-4 max-w-[42rem]">
                            <legend className="eyebrow mb-3">{group.label}</legend>
                            {keys.map((fieldKey) => (
                              <FieldInput
                                key={fieldKey}
                                id={`letter-${key}-${fieldKey}`}
                                fieldKey={fieldKey}
                                field={data.fields[fieldKey]}
                                value={valueFor(fieldKey)}
                                onChange={(v) => setAnswer(fieldKey, v)}
                              />
                            ))}
                            {group.id === 'money' && keys.includes('income_rows') && data.form ? (
                              <IncomeSummary value={valueFor('income_rows')} form={data.form} />
                            ) : null}
                          </fieldset>
                        );
                      })}
                    </div>
                  ) : (
                    <p className="text-sm text-gray-600">Everything this letter needs comes from your profile.</p>
                  )}
                  {letter.guidance.length > 0 ? (
                    <details className="mt-6 max-w-[42rem] group">
                      <summary className="cursor-pointer text-sm font-semibold text-primary-600 hover:text-primary-700">
                        Before you file it ({letter.guidance.length} {letter.guidance.length === 1 ? 'note' : 'notes'})
                      </summary>
                      <ul className="list-disc pl-5 mt-2 space-y-1 text-sm text-gray-700">
                        {letter.guidance.map((g) => <li key={g}>{g}</li>)}
                      </ul>
                    </details>
                  ) : null}
                  <p className="text-xs text-gray-500 mt-4 max-w-[42rem]">Anything you leave blank appears in the letter as a [bracketed prompt] to fill in before you sign.</p>
                  <div className="flex flex-wrap items-center gap-3 mt-5 pt-4 border-t border-rule">
                    <button type="button" className="btn btn-primary btn-sm" onClick={() => runDraft(letter)} disabled={busy}>
                      {busy ? 'Drafting…' : file ? 'Redraft with these answers' : 'Draft the letter'}
                    </button>
                    <button type="button" className="btn btn-ghost btn-sm" onClick={() => setOpen(null)}>
                      Cancel
                    </button>
                  </div>
                </div>
              ) : null}
            </li>
          );
        })}
      </ul>

      <Modal
        isOpen={!!editing}
        onClose={() => setEditing(null)}
        title={editing ? `Edit: ${editing.title}` : 'Edit'}
        size="xl"
        footer={
          <div className="flex flex-wrap justify-end gap-3">
            <button type="button" className="btn btn-ghost" onClick={() => setEditing(null)}>Cancel</button>
            <button type="button" className="btn btn-primary" onClick={saveEdit} disabled={edit.isPending || !editText.trim()}>
              {edit.isPending ? 'Saving…' : 'Save and rebuild the PDF'}
            </button>
          </div>
        }
      >
        <label htmlFor="letter-edit-text" className="sr-only">Letter text</label>
        <textarea
          id="letter-edit-text"
          value={editText}
          onChange={(e) => setEditText(e.target.value)}
          rows={20}
          className="input font-mono text-sm leading-relaxed"
          aria-describedby="letter-edit-hint"
        />
        <p id="letter-edit-hint" className="mt-2 text-xs text-gray-500">
          Each line prints as written; a blank line starts a new paragraph. A line starting with # is the title, ## a heading and - a bullet. [signature] draws a line to sign on. Lines starting with // are notes to you and never print.
        </p>
      </Modal>
    </section>
  );
}
