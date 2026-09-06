/**
 * Guide Message Content
 *
 * Renders the markdown the model returns for AI guide answers.
 *
 * Written by hand rather than pulled from a library because the output is a
 * known, narrow subset of markdown and the bundle is served to members on
 * slow rural connections. It covers: headings, horizontal rules, bullet and
 * numbered lists, blockquotes, paragraphs, and inline bold, italic, code and
 * links.
 *
 * Two things this fixes that the previous version got wrong:
 *   - Consecutive lines are joined into one paragraph. Previously every source
 *     line became its own <p>, which broke wrapped prose into choppy fragments.
 *   - "---" is a section rule, not literal text.
 */

import { JSX } from 'react';

/** Inline patterns, ordered so bold is matched before italic. */
const INLINE_PATTERN =
  /\[([^\]]+)\]\(([^)\s]+)\)|\*\*([^*]+)\*\*|__([^_]+)__|\*([^*\n]+)\*|`([^`]+)`/;

/**
 * Parses inline formatting (bold, italic, code, links) in a run of text.
 */
function parseInlineFormatting(text: string): JSX.Element {
  const parts: (string | JSX.Element)[] = [];
  let remaining = text;
  let key = 0;

  while (remaining.length > 0) {
    const match = remaining.match(INLINE_PATTERN);

    if (!match || match.index === undefined) {
      parts.push(remaining);
      break;
    }

    if (match.index > 0) {
      parts.push(remaining.slice(0, match.index));
    }

    const [full, linkText, linkHref, bold, boldAlt, italic, code] = match;

    if (linkText && linkHref) {
      const safeHref = /^https?:\/\//i.test(linkHref) ? linkHref : '#';
      parts.push(
        <a
          key={key++}
          href={safeHref}
          target="_blank"
          rel="noopener noreferrer"
          className="text-primary-600 hover:text-primary-700 underline decoration-primary-300 underline-offset-2"
        >
          {linkText}
        </a>
      );
    } else if (bold || boldAlt) {
      parts.push(
        <strong key={key++} className="font-semibold text-gray-900">
          {bold || boldAlt}
        </strong>
      );
    } else if (italic) {
      parts.push(
        <em key={key++} className="italic">
          {italic}
        </em>
      );
    } else if (code) {
      parts.push(
        <code
          key={key++}
          className="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[0.85em] text-gray-800"
        >
          {code}
        </code>
      );
    }

    remaining = remaining.slice(match.index + full.length);
  }

  return <>{parts}</>;
}

interface GuideMessageContentProps {
  content: string;
}

const RULE_RE = /^(-{3,}|\*{3,}|_{3,})$/;
const BULLET_RE = /^[-*•]\s+/;
const NUMBER_RE = /^\d+[.)]\s+/;

export default function GuideMessageContent({ content }: GuideMessageContentProps) {
  const lines = content.replace(/\r\n/g, '\n').split('\n');
  const elements: JSX.Element[] = [];

  let listItems: string[] = [];
  let listType: 'ul' | 'ol' | null = null;
  let paragraph: string[] = [];
  let quote: string[] = [];
  let pendingRule = false;

  const flushList = () => {
    if (!listItems.length) return;
    const items = listItems;
    const ordered = listType === 'ol';
    elements.push(
      ordered ? (
        <ol key={elements.length} className="my-3 ml-5 list-decimal space-y-2 marker:text-gray-400">
          {items.map((item, i) => (
            <li key={i} className="pl-1.5 text-sm leading-relaxed text-gray-700">
              {parseInlineFormatting(item)}
            </li>
          ))}
        </ol>
      ) : (
        <ul key={elements.length} className="my-3 space-y-2">
          {items.map((item, i) => (
            <li key={i} className="flex items-start gap-2.5 text-sm leading-relaxed text-gray-700">
              <span aria-hidden="true" className="mt-[0.55em] h-1.5 w-1.5 shrink-0 rounded-full bg-primary-400" />
              <span className="min-w-0">{parseInlineFormatting(item)}</span>
            </li>
          ))}
        </ul>
      )
    );
    listItems = [];
    listType = null;
  };

  const flushParagraph = () => {
    if (!paragraph.length) return;
    const text = paragraph.join(' ');
    paragraph = [];
    elements.push(
      <p key={elements.length} className="mb-3 text-sm leading-relaxed text-gray-700">
        {parseInlineFormatting(text)}
      </p>
    );
  };

  const flushQuote = () => {
    if (!quote.length) return;
    const text = quote.join(' ');
    quote = [];
    elements.push(
      <blockquote
        key={elements.length}
        className="my-3 border-l-2 border-primary-300 bg-gray-50 py-2 pl-4 pr-3 text-sm italic leading-relaxed text-gray-600"
      >
        {parseInlineFormatting(text)}
      </blockquote>
    );
  };

  const flushAll = () => {
    flushParagraph();
    flushList();
    flushQuote();
  };

  /**
   * Section breaks.
   *
   * A rule immediately before a heading is absorbed into that heading, so the
   * "--- / In Practice" pattern the model emits renders as one considered
   * break instead of a stray line above a title.
   */
  const pushHeading = (text: string, level: 1 | 2 | 3, afterRule: boolean) => {
    const isSectionStart = afterRule || level === 1;

    if (isSectionStart) {
      elements.push(
        <div key={elements.length} className="mt-7 mb-3 first:mt-0">
          <div className="mb-3 flex items-center gap-3" aria-hidden="true">
            <span className="h-px w-8 bg-primary-400" />
            <span className="h-px flex-1 bg-gray-200" />
          </div>
          <h3 className="text-[0.95rem] font-semibold tracking-tight text-gray-900">
            {parseInlineFormatting(text)}
          </h3>
        </div>
      );
      return;
    }

    if (level === 2) {
      elements.push(
        <h3
          key={elements.length}
          className="mt-5 mb-2 text-[0.95rem] font-semibold tracking-tight text-gray-900"
        >
          {parseInlineFormatting(text)}
        </h3>
      );
      return;
    }

    elements.push(
      <h4 key={elements.length} className="mt-4 mb-1.5 text-sm font-semibold text-gray-800">
        {parseInlineFormatting(text)}
      </h4>
    );
  };

  lines.forEach((rawLine) => {
    const line = rawLine.trim();

    // Horizontal rule - held back in case a heading follows.
    if (RULE_RE.test(line)) {
      flushAll();
      pendingRule = true;
      return;
    }

    // A blank line flushes open blocks but must NOT cancel a pending rule:
    // the model writes "---", a blank line, then the heading.
    if (line === '') {
      flushAll();
      return;
    }

    const headingMatch = line.match(/^(#{1,4})\s+(.*)$/);
    if (headingMatch) {
      flushAll();
      const level = Math.min(headingMatch[1].length, 3) as 1 | 2 | 3;
      pushHeading(headingMatch[2], level, pendingRule);
      pendingRule = false;
      return;
    }

    // A bold-only line acts as a subheading, which is how the model most often
    // marks the "In Practice" section.
    const boldHeading = line.match(/^\*\*([^*]+)\*\*:?$/);
    if (boldHeading) {
      flushAll();
      pushHeading(boldHeading[1], 2, pendingRule);
      pendingRule = false;
      return;
    }

    // A rule with no heading after it renders as a plain divider.
    if (pendingRule) {
      elements.push(
        <hr key={elements.length} className="my-6 border-0 border-t border-gray-200" />
      );
      pendingRule = false;
    }

    if (line.startsWith('> ')) {
      flushParagraph();
      flushList();
      quote.push(line.slice(2));
      return;
    }

    if (BULLET_RE.test(line)) {
      flushParagraph();
      flushQuote();
      if (listType !== 'ul') flushList();
      listType = 'ul';
      listItems.push(line.replace(BULLET_RE, ''));
      return;
    }

    if (NUMBER_RE.test(line)) {
      flushParagraph();
      flushQuote();
      if (listType !== 'ol') flushList();
      listType = 'ol';
      listItems.push(line.replace(NUMBER_RE, ''));
      return;
    }

    // Continuation of a list item that wrapped onto its own line.
    if (listItems.length && !paragraph.length) {
      listItems[listItems.length - 1] += ' ' + line;
      return;
    }

    flushList();
    flushQuote();
    paragraph.push(line);
  });

  flushAll();

  if (pendingRule) {
    elements.push(<hr key={elements.length} className="my-6 border-0 border-t border-gray-200" />);
  }

  return <div className="guide-message-content">{elements}</div>;
}
