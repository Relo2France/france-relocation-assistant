/**
 * SafeHtml Component
 *
 * Renders HTML content safely by sanitizing it with DOMPurify.
 * Use this instead of dangerouslySetInnerHTML to prevent XSS attacks.
 *
 * @since 2.1.0
 */

import DOMPurify from 'dompurify';

interface SafeHtmlProps {
  html: string;
  className?: string;
  as?: keyof JSX.IntrinsicElements;
}

/**
 * Safely render HTML content with XSS protection
 */
export default function SafeHtml({
  html,
  className,
  as: Component = 'div',
}: SafeHtmlProps) {
  const sanitizedHtml = DOMPurify.sanitize(html, {
    ALLOWED_TAGS: [
      'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'ul', 'ol', 'li',
      'a', 'blockquote', 'code', 'pre',
      'table', 'thead', 'tbody', 'tr', 'th', 'td',
      'span', 'div',
    ],
    ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'id'],
    ALLOW_DATA_ATTR: false,
  });

  return (
    <Component
      className={className}
      dangerouslySetInnerHTML={{ __html: sanitizedHtml }}
    />
  );
}
