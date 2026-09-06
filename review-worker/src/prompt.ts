/**
 * The review prompt.
 *
 * Ported verbatim from FRA_Scheduled_Review::call_claude_api() so the output
 * shape stays identical - the WordPress approval screen renders these fields
 * and must not have to care which side produced them.
 */
import type { Topic } from './types';

export function buildReviewPrompt(topic: Topic, now = new Date()): string {
  const currentDate = now.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'UTC',
  });
  const currentYear = now.getUTCFullYear();

  const searchQueries = (topic.practice_hints ?? [])
    .slice(0, 3)
    .map((hint) => `France ${hint} ${currentYear} expat experience`)
    .join('\n- ');

  return `You are updating a US-to-France relocation guide. Today is ${currentDate}.

**TOPIC:** ${topic.name}
**CATEGORY:** ${topic.category}

**CURRENT CONTENT:**
\`\`\`
${topic.content}
\`\`\`

**OFFICIAL SOURCES:** ${(topic.sources ?? []).join(', ')}
**KEY FACTS TO VERIFY:** ${(topic.key_facts ?? []).join(', ')}

**PRACTICAL TOPICS TO RESEARCH:**
- ${searchQueries}
- Reddit r/expats, r/france, expat forums
- Recent blog posts and articles from Americans in France

---

**YOUR TASK - TWO PARTS:**

**PART 1: OFFICIAL INFORMATION**
Review and update the factual content. Check all numbers, fees, requirements, and deadlines against current official information.

**PART 2: IN PRACTICE (NEW SECTION)**
Research and write an "**In Practice**" section that covers:
• Grey areas and how rules are actually enforced (or not)
• Common experiences from expats and forums
• Practical tips that aren't in official documentation
• Current discussions or recent changes people are talking about
• Things that surprised people or caught them off guard

**IMPORTANT FOR IN PRACTICE:**
- Be honest about grey areas without encouraging rule-breaking
- Cite specific sources (Reddit threads, blog posts, forum discussions, articles)
- Note when information is anecdotal vs. widely reported
- Include approximate dates of sources ("as of late 2024", "reported in 2025")
- Distinguish between "the law says X" and "in practice, Y"

---

**RESPOND WITH JSON ONLY:**
\`\`\`json
{
    "needs_update": true/false,
    "update_type": "none" | "minor" | "significant" | "rewrite",
    "confidence": "high" | "medium" | "low",
    "changes_summary": "Brief description of changes to official content",
    "suggested_content": "Updated OFFICIAL content (facts, requirements, fees). Use **bold** for headers and • for bullets.",
    "in_practice_content": "The IN PRACTICE section with real-world insights. Start with **In Practice** header. Include source citations inline like (Source: Reddit r/expats, Jan 2025) or (Source: FrenchEntrée blog, 2024).",
    "practice_sources": [
        {"name": "Source name or description", "type": "forum|blog|article|social", "date": "approximate date"}
    ],
    "official_sources_checked": ["source1.gouv.fr"],
    "key_insights": ["Most important practical insight 1", "Key insight 2"]
}
\`\`\`

**NOTES:**
- Always set needs_update to true if you're adding/updating the In Practice section
- The in_practice_content should feel conversational and helpful, not legal
- Be specific with examples where possible
- If a grey area exists, explain both the official rule AND the practical reality`;
}
