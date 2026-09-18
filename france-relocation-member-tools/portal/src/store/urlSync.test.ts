import { beforeEach, describe, expect, it } from 'vitest';
import { readLocation, sameLocation, urlForLocation, urlWithout } from './urlSync';
import { startUrlSync, usePortalStore } from './index';

const BASE = 'https://relo2france.com/portal/';

describe('readLocation', () => {
  it('defaults to the dashboard', () => {
    expect(readLocation('')).toEqual({ view: 'dashboard', stage: null, guide: null });
  });
  it('reads view, stage and guide', () => {
    expect(readLocation('?view=stage&stage=apply')).toEqual({ view: 'stage', stage: 'apply', guide: null });
    expect(readLocation('?view=guide&guide=tax-residency-rules').guide).toBe('tax-residency-rules');
  });
});

describe('urlForLocation', () => {
  it('keeps the page path and unrelated parameters', () => {
    expect(urlForLocation(`${BASE}?lang=en`, { view: 'tasks', stage: null, guide: null })).toBe('/portal/?lang=en&view=tasks');
  });
  it('carries the stage only on a stage page, the guide only on a guide', () => {
    expect(urlForLocation(BASE, { view: 'stage', stage: 'move', guide: 'x' })).toBe('/portal/?view=stage&stage=move');
    expect(urlForLocation(`${BASE}?view=stage&stage=move`, { view: 'documents', stage: 'move', guide: null })).toBe('/portal/?view=documents');
    expect(urlForLocation(BASE, { view: 'guide', stage: 'move', guide: 'role-of-notaire' })).toBe('/portal/?view=guide&guide=role-of-notaire');
  });
  it('drops one-shot links from emails', () => {
    expect(urlForLocation(`${BASE}?view=tasks&task=12&message=4`, { view: 'messages', stage: null, guide: null })).toBe('/portal/?view=messages');
  });
});

describe('urlWithout', () => {
  it('removes only the named parameters', () => {
    expect(urlWithout(`${BASE}?view=tasks&task=12#top`, ['task'])).toBe('/portal/?view=tasks#top');
  });
});

describe('sameLocation', () => {
  it('ignores stage and guide off their own views', () => {
    expect(sameLocation({ view: 'tasks', stage: 'a', guide: null }, { view: 'tasks', stage: 'b', guide: null })).toBe(true);
    expect(sameLocation({ view: 'stage', stage: 'a', guide: null }, { view: 'stage', stage: 'b', guide: null })).toBe(false);
  });
});

describe('store navigation and the browser history', () => {
  beforeEach(() => {
    window.history.replaceState(null, '', '/portal/?view=dashboard');
    usePortalStore.setState({ activeView: 'dashboard', activeStage: null, activeGuide: null, mobileNavOpen: false });
  });

  it('pushes a history entry per screen and closes the phone menu', () => {
    const before = window.history.length;
    usePortalStore.setState({ mobileNavOpen: true });
    usePortalStore.getState().setActiveView('documents');
    expect(window.location.search).toBe('?view=documents');
    expect(window.history.length).toBe(before + 1);
    expect(usePortalStore.getState().mobileNavOpen).toBe(false);
  });

  it('does not stack entries for the screen already showing', () => {
    usePortalStore.getState().setActiveView('documents');
    const before = window.history.length;
    usePortalStore.getState().setActiveView('documents');
    expect(window.history.length).toBe(before);
  });

  it('records the stage, and a stage change on the stage page', () => {
    const { setActiveStage, setActiveView } = usePortalStore.getState();
    setActiveStage('prepare');
    setActiveView('stage');
    expect(window.location.search).toBe('?view=stage&stage=prepare');
    setActiveStage('apply');
    expect(window.location.search).toBe('?view=stage&stage=apply');
  });

  it('restores the screen on popstate', () => {
    const stop = startUrlSync();
    try {
      usePortalStore.getState().setActiveView('documents');
      // What the browser does on Back: the URL changes, then popstate fires.
      window.history.replaceState(null, '', '/portal/?view=stage&stage=arrive');
      usePortalStore.setState({ mobileNavOpen: true });
      window.dispatchEvent(new PopStateEvent('popstate'));
      const state = usePortalStore.getState();
      expect(state.activeView).toBe('stage');
      expect(state.activeStage).toBe('arrive');
      expect(state.mobileNavOpen).toBe(false);
    } finally {
      stop();
    }
  });

  it('drops ?task= and ?message= once consumed, without a new entry', () => {
    window.history.replaceState(null, '', '/portal/?view=tasks&task=12');
    const before = window.history.length;
    usePortalStore.getState().setOpenTaskId(null);
    expect(window.location.search).toBe('?view=tasks');
    window.history.replaceState(null, '', '/portal/?view=messages&message=4');
    usePortalStore.getState().setOpenMessageId(null);
    expect(window.location.search).toBe('?view=messages');
    expect(window.history.length).toBe(before);
  });
});
