export interface CountedClick {
  post_id: number;
  url_id: number;
  clicks_count: number;
  title: string;
}

/**
 * Update every rendered copy of a link after someone else clicked it.
 *
 * The badge itself is CSS reading `data-clicks`, so moving the count is all
 * that's needed — no redraw, and it works on server-rendered post HTML that
 * Mithril doesn't own.
 *
 * Kept apart from the realtime subscription so it can be tested without the
 * realtime extension, and takes `minDisplay` rather than reading settings so
 * it has no imports at all.
 */
export default function applyCountedClick(data: CountedClick, minDisplay: number): void {
  // Matched on the data attributes alone rather than on a class: every tracked
  // link carries both, but mention pills keep their own classes and never gain
  // `LinkClicks-link`.
  const selector = `[data-post-id="${data.post_id}"][data-url-id="${data.url_id}"]`;

  document.querySelectorAll<HTMLAnchorElement>(selector).forEach((el) => {
    if (data.clicks_count < minDisplay) {
      el.removeAttribute('data-clicks');
      return;
    }

    el.setAttribute('data-clicks', String(data.clicks_count));

    // Only plain links get the count as a tooltip; a mention already says what
    // it points at, and the server doesn't render a title for one either.
    if (el.classList.contains('LinkClicks-link') && !el.hasAttribute('data-custom-title')) {
      el.setAttribute('title', data.title);
      el.setAttribute('data-original-title', data.title);
    }
  });
}
