/**
 * Document-level capture-phase click handler. Intercepts every click on a
 * tracker redirect anchor (href contains `/lcc/track`) and shows a native
 * confirm dialog before letting the browser navigate.
 *
 * Match by href instead of class because:
 *   - the formatter cache might be stale and miss the LinkClicks-link
 *     class on some posts;
 *   - the destination URL is hidden behind a signed token, so we can't
 *     show "you're leaving for X", the message stays generic.
 */
export default function confirmExternalClicks(): void;
