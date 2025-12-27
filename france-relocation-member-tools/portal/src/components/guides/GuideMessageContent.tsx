/**
 * Guide Message Content
 *
 * Professional message content renderer with markdown parsing
 * for the AI guide chat interface.
 */

/**
 * Parses inline formatting (bold, links) in text
 */
function parseInlineFormatting(text: string): JSX.Element {
  const parts: (string | JSX.Element)[] = [];
  let remaining = text;
  let keyIndex = 0;

  while (remaining.length > 0) {
    const linkMatch = remaining.match(/\[([^\]]+)\]\(([^)]+)\)/);
    const boldMatch = remaining.match(/\*\*([^*]+)\*\*/);

    const matches = [
      linkMatch ? { type: 'link', match: linkMatch, index: remaining.indexOf(linkMatch[0]) } : null,
      boldMatch ? { type: 'bold', match: boldMatch, index: remaining.indexOf(boldMatch[0]) } : null,
    ].filter(Boolean).sort((a, b) => (a?.index ?? Infinity) - (b?.index ?? Infinity));

    if (matches.length === 0 || matches[0] === null) {
      parts.push(remaining);
      break;
    }

    const earliest = matches[0];
    if (earliest.index > 0) {
      parts.push(remaining.slice(0, earliest.index));
    }

    if (earliest.type === 'link' && earliest.match) {
      parts.push(
        <a
          key={keyIndex++}
          href={earliest.match[2]}
          target="_blank"
          rel="noopener noreferrer"
          className="text-primary-600 hover:text-primary-700 underline font-medium"
        >
          {earliest.match[1]}
        </a>
      );
      remaining = remaining.slice(earliest.index + earliest.match[0].length);
    } else if (earliest.type === 'bold' && earliest.match) {
      parts.push(
        <strong key={keyIndex++} className="font-semibold text-gray-900">
          {earliest.match[1]}
        </strong>
      );
      remaining = remaining.slice(earliest.index + earliest.match[0].length);
    }
  }

  return <>{parts}</>;
}

interface GuideMessageContentProps {
  content: string;
}

export default function GuideMessageContent({ content }: GuideMessageContentProps) {
  const lines = content.split('\n');
  const elements: JSX.Element[] = [];
  let currentList: { text: string; formatted: JSX.Element }[] = [];
  let currentListType: 'ul' | 'ol' | null = null;

  const flushList = () => {
    if (currentList.length > 0) {
      const isOrdered = currentListType === 'ol';
      elements.push(
        isOrdered ? (
          <ol key={elements.length} className="my-3 ml-5 space-y-1.5 list-decimal text-sm">
            {currentList.map((item, i) => (
              <li key={i} className="text-gray-700 leading-relaxed pl-1">
                {item.formatted}
              </li>
            ))}
          </ol>
        ) : (
          <ul key={elements.length} className="my-3 ml-5 space-y-1.5 text-sm">
            {currentList.map((item, i) => (
              <li key={i} className="text-gray-700 leading-relaxed flex items-start gap-2">
                <span className="text-primary-500 mt-0.5">•</span>
                <span>{item.formatted}</span>
              </li>
            ))}
          </ul>
        )
      );
      currentList = [];
      currentListType = null;
    }
  };

  lines.forEach((line) => {
    const trimmedLine = line.trim();

    // H2 headers (## Header)
    if (trimmedLine.startsWith('## ')) {
      flushList();
      elements.push(
        <h3 key={elements.length} className="text-base font-semibold text-gray-900 mt-4 mb-2 pb-1 border-b border-gray-100">
          {parseInlineFormatting(trimmedLine.slice(3))}
        </h3>
      );
    }
    // H3 headers (### Header)
    else if (trimmedLine.startsWith('### ')) {
      flushList();
      elements.push(
        <h4 key={elements.length} className="text-sm font-semibold text-gray-800 mt-3 mb-1.5">
          {parseInlineFormatting(trimmedLine.slice(4))}
        </h4>
      );
    }
    // Bold-only lines as subheadings
    else if (trimmedLine.startsWith('**') && trimmedLine.endsWith('**') && !trimmedLine.slice(2, -2).includes('**')) {
      flushList();
      elements.push(
        <h4 key={elements.length} className="text-sm font-semibold text-gray-800 mt-3 mb-1.5">
          {trimmedLine.slice(2, -2)}
        </h4>
      );
    }
    // Bullet points
    else if (trimmedLine.match(/^[\-\*•]\s/)) {
      if (currentListType !== 'ul') flushList();
      currentListType = 'ul';
      const itemText = trimmedLine.replace(/^[\-\*•]\s/, '');
      currentList.push({ text: itemText, formatted: parseInlineFormatting(itemText) });
    }
    // Numbered lists
    else if (trimmedLine.match(/^\d+\.\s/)) {
      if (currentListType !== 'ol') flushList();
      currentListType = 'ol';
      const itemText = trimmedLine.replace(/^\d+\.\s/, '');
      currentList.push({ text: itemText, formatted: parseInlineFormatting(itemText) });
    }
    // Regular paragraphs
    else if (trimmedLine) {
      flushList();
      elements.push(
        <p key={elements.length} className="text-sm text-gray-700 leading-relaxed mb-2">
          {parseInlineFormatting(trimmedLine)}
        </p>
      );
    }
    // Empty lines
    else {
      flushList();
    }
  });

  flushList();

  return <div className="guide-message-content">{elements}</div>;
}
