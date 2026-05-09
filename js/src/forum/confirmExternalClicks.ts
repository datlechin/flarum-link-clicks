import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';

/**
 * Document-level click handler that intercepts tracked links pointing
 * outside the forum and shows a native confirmation dialog before letting
 * the navigation proceed. Native `confirm()` is used because it blocks
 * synchronously; an async Mithril modal would lose the racing navigation.
 */
export default function confirmExternalClicks(): void {
  if (!app.forum.attribute<boolean>('linkClicksConfirmExternal')) return;

  const forumHost = (() => {
    try {
      return new URL(app.forum.attribute<string>('baseUrl')).host.toLowerCase();
    } catch {
      return '';
    }
  })();

  document.addEventListener(
    'click',
    (event) => {
      const target = event.target as Element | null;
      const anchor = target?.closest('a.LinkClicks-link') as HTMLAnchorElement | null;
      if (!anchor) return;

      // The anchor href is the tracker redirect URL; the real destination
      // sits behind the signed token. We can't peek inside the token from
      // the browser, but we can fall back to the link text or the title
      // attribute, which is the original URL.
      const dest = anchor.title || anchor.textContent || '';
      let isExternal = true;
      try {
        const u = new URL(dest, app.forum.attribute<string>('baseUrl'));
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
