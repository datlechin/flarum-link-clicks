import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';

/**
 * Document-level click handler that intercepts tracked external links
 * and shows a native confirm dialog before letting the browser navigate.
 *
 * Flarum 2.x runs initializers BEFORE app.forum is hydrated, so we
 * register the listener unconditionally and read the setting lazily at
 * click time. The listener is a no-op when the setting is off, which
 * costs effectively nothing per click.
 */
export default function confirmExternalClicks(): void {
  document.addEventListener(
    'click',
    (event) => {
      if (!app.forum || !app.forum.attribute<boolean>('linkClicksConfirmExternal')) return;

      const target = event.target as Element | null;
      const anchor = target?.closest('a.LinkClicks-link') as HTMLAnchorElement | null;
      if (!anchor) return;

      // The anchor href is the tracker redirect URL; the real destination
      // sits in the title attribute (set by RewriteLinksForTracking when
      // there's no user-supplied title) or in the link text.
      const dest = anchor.title || anchor.textContent || '';

      let isExternal = true;
      try {
        const baseUrl = app.forum.attribute<string>('baseUrl');
        const forumHost = baseUrl ? new URL(baseUrl).host.toLowerCase() : '';
        const u = new URL(dest, baseUrl);
        isExternal = !!forumHost && u.host.toLowerCase() !== forumHost;
      } catch {
        isExternal = true;
      }

      if (!isExternal) return;

      const message = extractText(app.translator.trans('datlechin-link-clicks.forum.confirm_external', { url: dest }));

      if (!window.confirm(message)) {
        event.preventDefault();
      }
    },
    { capture: true }
  );
}
