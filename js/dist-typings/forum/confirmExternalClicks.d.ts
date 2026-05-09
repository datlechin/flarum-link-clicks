/**
 * Document-level click handler that intercepts tracked links pointing
 * outside the forum and shows a native confirmation dialog before letting
 * the navigation proceed. Native `confirm()` is used because it blocks
 * synchronously; an async Mithril modal would lose the racing navigation.
 */
export default function confirmExternalClicks(): void;
