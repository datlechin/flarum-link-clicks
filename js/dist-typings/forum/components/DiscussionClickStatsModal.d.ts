import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
interface StatRow {
    url: string;
    url_hash: string;
    is_internal: boolean;
    is_attachment: boolean;
    total_clicks: number;
    unique_users: number;
    first_clicked: string | null;
    last_clicked: string | null;
}
interface Attrs extends IInternalModalAttrs {
    discussionId: number;
    discussionTitle: string;
}
export default class DiscussionClickStatsModal extends Modal<Attrs> {
    protected loading: boolean;
    protected rows: StatRow[];
    protected total: number;
    oninit(vnode: Mithril.Vnode<Attrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    protected load(): Promise<void>;
}
export {};
