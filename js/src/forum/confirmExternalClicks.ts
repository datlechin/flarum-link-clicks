import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';

/**
 * Document-level click handler that intercepts tracked links pointing
 * outside the forum and shows a native confirmation dialog before letting
 * the navigation proceed. Native `confirm()` is used because it blocks
 * synchronously; an async Mithril modal would lose the racing navigation.
 */
export default function confirmExternalClicks(): void {
  // app.forum / app.session etc are only populated AFTER initializers run
  // in some Flarum 2.x boot paths, so reach for app.data which is set
  // earlier and survives both orderings.
  const data = (app as any).data?.resources?.find?.((r: any) => r.type === 'forums')?.attributes ?? (app as any).data?.forum?.attributes ?? {};

  if (!data.linkClicksConfirmExternal) return;

  const forumHost = (() => {
    try {
      return new URL(data.baseUrl).host.toLowerCase();
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
        const u = new URL(dest, data.baseUrl);
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
