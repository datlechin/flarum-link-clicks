import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
interface ClickerRow {
    user: {
        id: number;
        username: string;
        displayName: string;
        avatarUrl: string | null;
    } | null;
    ip_address: string | null;
    anonymized: boolean;
    click_count: number;
    first_click: string;
    last_click: string;
}
interface Attrs extends IInternalModalAttrs {
    urlHash: string;
    url: string;
}
export default class LinkClickersModal extends Modal<Attrs> {
    protected loading: boolean;
    protected rows: ClickerRow[];
    protected total: number;
    protected pageNumber: number;
    oninit(vnode: Mithril.Vnode<Attrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    protected renderRow(row: ClickerRow): Mithril.Children;
    protected load(): Promise<void>;
}
export {};
