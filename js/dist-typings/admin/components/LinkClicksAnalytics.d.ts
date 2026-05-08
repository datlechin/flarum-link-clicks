import Component from 'flarum/common/Component';
import Stream from 'flarum/common/utils/Stream';
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
export default class LinkClicksAnalytics extends Component {
    protected loading: boolean;
    protected exporting: boolean;
    protected rows: StatRow[];
    protected total: number;
    protected pageNumber: number;
    protected since: Stream<string>;
    protected until: Stream<string>;
    protected scope: Stream<string>;
    protected tagSlug: Stream<string>;
    protected sort: Stream<string>;
    oninit(vnode: Mithril.Vnode): void;
    protected load(): Promise<void>;
    protected filterParams(): Record<string, string>;
    protected exportCsv(): Promise<void>;
    view(): Mithril.Children;
}
export {};
