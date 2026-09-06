/**
 * Model resolution.
 *
 * A direct port of FRA_Model_Resolver's resolution rules, so the Worker and
 * WordPress pick the same model: ask for a tier, resolve it against the live
 * catalogue, take the newest in that tier.
 *
 * The WordPress version needed a hand-rolled transient cache and a failure
 * backoff to avoid re-fetching on every page load. KV with a TTL is that,
 * natively.
 */
import type { AnthropicModel, Env } from './types';

const MODELS_ENDPOINT = 'https://api.anthropic.com/v1/models';
const API_VERSION = '2023-06-01';
const CACHE_KEY = 'anthropic:catalogue';
const CACHE_TTL_SECONDS = 24 * 60 * 60;

/** Tiers to fall back through when the requested one has no live models. */
const TIER_FALLBACKS: Record<string, string[]> = {
  opus: ['opus', 'sonnet', 'haiku'],
  sonnet: ['sonnet', 'opus', 'haiku'],
  haiku: ['haiku', 'sonnet', 'opus'],
};

/** Used only if the catalogue is unreachable and nothing is cached. */
const EMERGENCY: Record<string, string> = {
  opus: 'claude-opus-5',
  sonnet: 'claude-sonnet-5',
  haiku: 'claude-haiku-4-5',
};

/** Never auto-adopt a preview model for production content. */
const EXCLUDED = ['preview'];

export function tierOf(modelId: string): string {
  for (const tier of ['fable', 'mythos', 'opus', 'sonnet', 'haiku']) {
    if (modelId.toLowerCase().includes(tier)) return tier;
  }
  return 'unknown';
}

export async function fetchCatalogue(apiKey: string): Promise<AnthropicModel[]> {
  const models: AnthropicModel[] = [];
  let afterId: string | undefined;

  for (let page = 0; page < 10; page++) {
    const url = new URL(MODELS_ENDPOINT);
    url.searchParams.set('limit', '100');
    if (afterId) url.searchParams.set('after_id', afterId);

    const response = await fetch(url, {
      headers: { 'x-api-key': apiKey, 'anthropic-version': API_VERSION },
    });

    if (!response.ok) {
      throw new Error(`Models API returned ${response.status}`);
    }

    const body = (await response.json()) as {
      data?: AnthropicModel[];
      has_more?: boolean;
      last_id?: string;
    };

    for (const model of body.data ?? []) {
      if (model?.id) models.push(model);
    }

    if (!body.has_more || !body.last_id) break;
    afterId = body.last_id;
  }

  if (models.length === 0) throw new Error('Models API returned no models');

  // Newest first - this is what makes a new release get picked up.
  models.sort((a, b) => Date.parse(b.created_at ?? '') - Date.parse(a.created_at ?? ''));
  return models;
}

export async function getCatalogue(env: Env, force = false): Promise<AnthropicModel[]> {
  if (!force) {
    const cached = await env.MODEL_CACHE.get<AnthropicModel[]>(CACHE_KEY, 'json');
    if (cached?.length) return cached;
  }

  try {
    const models = await fetchCatalogue(env.ANTHROPIC_API_KEY);
    await env.MODEL_CACHE.put(CACHE_KEY, JSON.stringify(models), {
      expirationTtl: CACHE_TTL_SECONDS,
    });
    return models;
  } catch (error) {
    // Serve the stale copy rather than failing the run. KV keeps the last
    // known good value until it expires.
    const stale = await env.MODEL_CACHE.get<AnthropicModel[]>(CACHE_KEY, 'json');
    if (stale?.length) return stale;
    throw error;
  }
}

export function pickFromCatalogue(models: AnthropicModel[], tier: string): string | null {
  const order = TIER_FALLBACKS[tier] ?? [tier, 'sonnet', 'opus', 'haiku'];

  for (const candidate of order) {
    for (const model of models) {
      if (tierOf(model.id) !== candidate) continue;
      if (EXCLUDED.some((word) => model.id.toLowerCase().includes(word))) continue;
      return model.id;
    }
  }
  return null;
}

export async function resolveModel(env: Env, tier?: string): Promise<string> {
  const wanted = (tier ?? env.MODEL_TIER ?? 'sonnet').toLowerCase();

  try {
    const models = await getCatalogue(env);
    const picked = pickFromCatalogue(models, wanted);
    if (picked) return picked;
  } catch {
    // fall through to the emergency default
  }

  return EMERGENCY[wanted] ?? EMERGENCY.sonnet!;
}
