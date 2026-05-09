import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
interface TrailRow {
    url: string;
    discussion_id: number;
    post_id: number;
    is_internal: boolean;
    is_attachment: boolean;
    clicked_at: string;
}
interface Attrs extends IInternalModalAttrs {
    userId: number;
    username: string;
    displayName: string;
}
export default class UserClickTrailModal extends Modal<Attrs> {
    protected loading: boolean;
    protected rows: TrailRow[];
    protected total: number;
    protected pageNumber: number;
    oninit(vnode: Mithril.Vnode<Attrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    protected load(): Promise<void>;
}
export {};
