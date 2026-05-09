import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';

/**
 * Document-level capture-phase click handler. Intercepts every click on a
 * tracker redirect anchor (href contains `/lcc/track`) and shows a native
 * confirm dialog before letting the browser navigate.
 *
 * Match by href instead of class because:
 *   - the formatter cache might be stale and miss the LinkClicks-link
 *     class on some posts;
 *   - the destination URL is hidden behind a signed token, so we can't
 *     show "you're leaving for X" — the message stays generic.
 */
export default function confirmExternalClicks(): void {
  document.addEventListener(
    'click',
    (event) => {
      if (!app.forum || !app.forum.attribute<boolean>('linkClicksConfirmExternal')) return;

      const target = event.target as Element | null;
      const anchor = target?.closest('a') as HTMLAnchorElement | null;
      if (!anchor) return;

      const href = anchor.getAttribute('href') ?? '';
      if (!href.includes('/lcc/track')) return;

      const message = extractText(app.translator.trans('datlechin-link-clicks.forum.confirm_external'));

      if (!window.confirm(message)) {
        event.preventDefault();
        event.stopPropagation();
      }
    },
    { capture: true }
  );
}
