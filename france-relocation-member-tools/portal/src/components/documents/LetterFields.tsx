/**
 * LetterFields
 *
 * The inputs the letters ask for. Money is entered the way members think
 * about it, in dollars or euros, a month or a year, one row per source,
 * and converted to euros at the European Central Bank's rate for the
 * letter. A running total says how it compares with the benchmark the
 * consulate uses.
 */
import { Plus, Trash2 } from 'lucide-react';
import type { LetterField, LetterFormContext } from '@/types';

export interface IncomeRow {
  source: string;
  amount: string;
  currency: 'USD' | 'EUR';
  per: 'month' | 'year';
}

interface MoneyValue {
  amount: string;
  currency: 'USD' | 'EUR';
}

const SOURCES = [
  'Social Security',
  'Pension',
  'Salary',
  'Self-employment',
  'Rental income',
  'Investments and dividends',
  'Retirement account withdrawals',
  'Annuity',
];

const PLACEHOLDERS: Record<string, string> = {
  address_in_france: '12 rue de la Paix\n24440 Monsac',
  mailing_address: '123 Main Street\nDenver, CO 80202',
  plans: 'We will take French classes, settle into village life and travel in Europe.',
};

function parseRows(value: string): IncomeRow[] {
  try {
    const rows = JSON.parse(value) as Partial<IncomeRow>[];
    if (Array.isArray(rows) && rows.length > 0) {
      return rows.map((r) => ({
        source: String(r.source ?? ''),
        amount: String(r.amount ?? ''),
        currency: r.currency === 'EUR' ? 'EUR' : 'USD',
        per: r.per === 'year' ? 'year' : 'month',
      }));
    }
  } catch {
    /* empty or old free text: start fresh */
  }
  return [{ source: '', amount: '', currency: 'USD', per: 'month' }];
}

function parseMoney(value: string): MoneyValue {
  try {
    const m = JSON.parse(value) as Partial<MoneyValue>;
    if (m && typeof m === 'object') return { amount: String(m.amount ?? ''), currency: m.currency === 'EUR' ? 'EUR' : 'USD' };
  } catch {
    /* start fresh */
  }
  return { amount: '', currency: 'USD' };
}

const toNumber = (s: string) => Number.parseFloat(s.replace(/[^0-9.]/g, '')) || 0;
const euro = (n: number) => `€${Math.round(n).toLocaleString('en-US')}`;

/** Monthly euros for the rows, or null when a dollar row cannot be converted. */
function monthlyEuros(value: string, fx: LetterFormContext['fx']): number | null {
  let total = 0;
  for (const r of parseRows(value)) {
    const amount = toNumber(r.amount);
    if (!amount) continue;
    const monthly = r.per === 'year' ? amount / 12 : amount;
    if (r.currency === 'USD') {
      if (!fx) return null;
      total += monthly / fx.rate;
    } else {
      total += monthly;
    }
  }
  return total;
}

const inputClass = 'input';

function IncomeRows({ id, value, onChange }: { id: string; value: string; onChange: (v: string) => void }) {
  const rows = parseRows(value);
  const save = (next: IncomeRow[]) => onChange(JSON.stringify(next));
  const update = (i: number, patch: Partial<IncomeRow>) => save(rows.map((r, j) => (j === i ? { ...r, ...patch } : r)));

  return (
    <div className="space-y-3">
      <datalist id={`${id}-sources`}>
        {SOURCES.map((s) => <option key={s} value={s} />)}
      </datalist>
      <div className="hidden sm:grid sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto_auto_auto] gap-2 text-xs font-medium text-gray-600" aria-hidden="true">
        <span>Source</span>
        <span>Amount</span>
      </div>
      {rows.map((row, i) => (
        <div key={i} className="grid gap-2 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto_auto_auto] items-end rounded-lg border border-rule bg-card p-3 sm:border-0 sm:bg-transparent sm:p-0">
          <div>
            <input
              id={`${id}-source-${i}`}
              list={`${id}-sources`}
              aria-label={`Source of income ${i + 1}`}
              value={row.source}
              onChange={(e) => update(i, { source: e.target.value })}
              placeholder="Social Security"
              className={inputClass}
            />
          </div>
          <div>
            <div className="relative">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm" aria-hidden="true">{row.currency === 'EUR' ? '€' : '$'}</span>
              <input
                id={`${id}-amount-${i}`}
                inputMode="decimal"
                aria-label={`Amount of income ${i + 1}`}
                value={row.amount}
                onChange={(e) => update(i, { amount: e.target.value })}
                placeholder="2,400"
                className={`${inputClass} pl-7`}
              />
            </div>
          </div>
          <div>
            <label htmlFor={`${id}-currency-${i}`} className="sr-only">Currency</label>
            <select id={`${id}-currency-${i}`} value={row.currency} onChange={(e) => update(i, { currency: e.target.value as IncomeRow['currency'] })} className="select">
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
            </select>
          </div>
          <div>
            <label htmlFor={`${id}-per-${i}`} className="sr-only">How often</label>
            <select id={`${id}-per-${i}`} value={row.per} onChange={(e) => update(i, { per: e.target.value as IncomeRow['per'] })} className="select">
              <option value="month">a month</option>
              <option value="year">a year</option>
            </select>
          </div>
          <button
            type="button"
            onClick={() => save(rows.length > 1 ? rows.filter((_, j) => j !== i) : [{ source: '', amount: '', currency: 'USD', per: 'month' }])}
            className="p-2.5 text-gray-400 hover:text-accent-500 justify-self-start"
            aria-label={`Remove ${row.source || 'this income'}`}
          >
            <Trash2 className="w-4 h-4" aria-hidden="true" />
          </button>
        </div>
      ))}
      <button
        type="button"
        onClick={() => save([...rows, { source: '', amount: '', currency: rows[rows.length - 1]?.currency ?? 'USD', per: 'month' }])}
        className="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:text-primary-700"
      >
        <Plus className="w-4 h-4" aria-hidden="true" />
        Add another source
      </button>
    </div>
  );
}

function MoneyInput({ id, value, onChange }: { id: string; value: string; onChange: (v: string) => void }) {
  const m = parseMoney(value);
  const save = (next: MoneyValue) => onChange(JSON.stringify(next));
  return (
    <div className="flex gap-2 max-w-sm">
      <div className="relative flex-1">
        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm" aria-hidden="true">{m.currency === 'EUR' ? '€' : '$'}</span>
        <input id={id} inputMode="decimal" value={m.amount} onChange={(e) => save({ ...m, amount: e.target.value })} placeholder="0" className={`${inputClass} pl-7`} />
      </div>
      <label htmlFor={`${id}-currency`} className="sr-only">Currency</label>
      <select id={`${id}-currency`} value={m.currency} onChange={(e) => save({ ...m, currency: e.target.value as MoneyValue['currency'] })} className="select w-auto">
        <option value="USD">USD</option>
        <option value="EUR">EUR</option>
      </select>
    </div>
  );
}

interface FieldInputProps {
  id: string;
  fieldKey: string;
  field: LetterField;
  value: string;
  onChange: (value: string) => void;
}

export function FieldInput({ id, fieldKey, field, value, onChange }: FieldInputProps) {
  const hintId = field.hint ? `${id}-hint` : undefined;
  const placeholder = PLACEHOLDERS[fieldKey];
  const grouped = field.type === 'income';
  const Label = grouped ? 'p' : 'label';
  return (
    <div>
      <Label {...(grouped ? { id: `${id}-label` } : { htmlFor: id })} className="block text-sm font-medium text-gray-800">
        {field.label}
      </Label>
      {field.hint ? <p id={hintId} className="mt-0.5 mb-2 text-xs text-gray-500 max-w-[60ch]">{field.hint}</p> : <div className="mb-2" />}
      {field.type === 'income' ? (
        <div role="group" aria-labelledby={`${id}-label`} aria-describedby={hintId}>
          <IncomeRows id={id} value={value} onChange={onChange} />
        </div>
      ) : field.type === 'money' ? (
        <MoneyInput id={id} value={value} onChange={onChange} />
      ) : field.type === 'textarea' ? (
        <textarea id={id} rows={3} value={value} onChange={(e) => onChange(e.target.value)} className={inputClass} aria-describedby={hintId} placeholder={placeholder} />
      ) : field.type === 'select' ? (
        <select id={id} value={value} onChange={(e) => onChange(e.target.value)} className="select max-w-sm" aria-describedby={hintId}>
          <option value="">Choose…</option>
          {(field.options ?? []).map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>
      ) : (
        <input
          id={id}
          type={field.type === 'date' ? 'date' : 'text'}
          inputMode={field.type === 'number' ? 'decimal' : undefined}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          className={field.type === 'date' ? `${inputClass} max-w-xs` : inputClass}
          aria-describedby={hintId}
          placeholder={placeholder}
        />
      )}
    </div>
  );
}

/** The running total against the consulate's benchmark. */
export function IncomeSummary({ value, form }: { value: string; form: LetterFormContext }) {
  const total = monthlyEuros(value, form.fx);
  const need = form.benchmark.monthly * form.benchmark.adults;
  const perAdult = form.benchmark.adults > 1 ? `, ${euro(form.benchmark.monthly)} for each of you` : '';
  if (total === null) {
    return <p className="text-sm text-gray-600">The euro total appears once today&apos;s exchange rate loads.</p>;
  }
  if (total === 0) return null;
  const above = total >= need;
  return (
    <div className="rounded-lg border border-rule bg-card px-4 py-3 text-sm" role="status" aria-live="polite">
      <p className="font-semibold text-ink">About {euro(total)} a month, {euro(total * 12)} a year</p>
      <p className="text-gray-600 mt-0.5">
        {above ? 'Above' : 'Below'} the {euro(need)} a month consulates look for ({form.benchmark.label}{perAdult}, since {form.benchmark.since}).
        {above ? '' : ' Savings and a clear cover letter carry more weight when income is below it.'}
      </p>
      {form.fx ? (
        <p className="text-xs text-gray-500 mt-1">
          Dollars converted at 1 EUR = {form.fx.rate} USD, the European Central Bank rate of {new Date(`${form.fx.date}T00:00:00Z`).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' })}.
        </p>
      ) : null}
    </div>
  );
}
