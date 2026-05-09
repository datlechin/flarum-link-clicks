/**
 * Document-level click handler that intercepts tracked external links
 * and shows a native confirm dialog before letting the browser navigate.
 *
 * Flarum 2.x runs initializers BEFORE app.forum is hydrated, so we
 * register the listener unconditionally and read the setting lazily at
 * click time. The listener is a no-op when the setting is off, which
 * costs effectively nothing per click.
 */
export default function confirmExternalClicks(): void;
