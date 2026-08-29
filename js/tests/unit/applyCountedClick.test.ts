import { describe, expect, it, beforeEach } from '@jest/globals';
import applyCountedClick from '../../src/forum/applyCountedClick';

describe('applyCountedClick', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <a class="LinkClicks-link" data-post-id="1" data-url-id="10" href="/lcc/track?u=a">link</a>
      <a class="TagMention" data-post-id="1" data-url-id="11" href="/t/support">#support</a>
      <a class="LinkClicks-link" data-post-id="1" data-url-id="12" data-custom-title="1" title="mine" href="/lcc/track?u=c">custom</a>
      <a class="LinkClicks-link" data-post-id="2" data-url-id="10" href="/lcc/track?u=d">other post</a>
    `;
  });

  const el = (urlId: number, postId = 1) => document.querySelector(`[data-post-id="${postId}"][data-url-id="${urlId}"]`)!;

  it('updates a plain link and gives it the count as a tooltip', () => {
    applyCountedClick({ post_id: 1, url_id: 10, clicks_count: 5, title: '5 people have opened this' }, 1);

    expect(el(10).getAttribute('data-clicks')).toBe('5');
    expect(el(10).getAttribute('title')).toBe('5 people have opened this');
  });

  // Mention pills carry their own classes and never gain LinkClicks-link, so
  // matching on that class would leave them frozen at their rendered count.
  it('updates a mention pill even though it has no LinkClicks-link class', () => {
    applyCountedClick({ post_id: 1, url_id: 11, clicks_count: 3, title: 'ignored' }, 1);

    expect(el(11).getAttribute('data-clicks')).toBe('3');
  });

  it('does not put a tooltip on a mention pill', () => {
    applyCountedClick({ post_id: 1, url_id: 11, clicks_count: 3, title: 'should not appear' }, 1);

    expect(el(11).hasAttribute('title')).toBe(false);
  });

  it('leaves an author-written title alone', () => {
    applyCountedClick({ post_id: 1, url_id: 12, clicks_count: 7, title: 'generated' }, 1);

    expect(el(12).getAttribute('data-clicks')).toBe('7');
    expect(el(12).getAttribute('title')).toBe('mine');
  });

  it('drops the badge when the count falls below the display threshold', () => {
    el(10).setAttribute('data-clicks', '4');

    applyCountedClick({ post_id: 1, url_id: 10, clicks_count: 2, title: 'x' }, 5);

    expect(el(10).hasAttribute('data-clicks')).toBe(false);
  });

  it('only touches the post the click belongs to', () => {
    applyCountedClick({ post_id: 1, url_id: 10, clicks_count: 5, title: 'x' }, 1);

    expect(el(10, 2).hasAttribute('data-clicks')).toBe(false);
  });
});
