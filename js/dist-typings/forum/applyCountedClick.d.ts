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
 * that's needed, no redraw, and it works on server-rendered post HTML that
 * Mithril doesn't own.
 *
 * Kept apart from the realtime subscription so it can be tested without the
 * realtime extension, and takes `minDisplay` rather than reading settings so
 * it has no imports at all.
 */
export default function applyCountedClick(data: CountedClick, minDisplay: number): void;
