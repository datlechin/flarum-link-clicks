import Component from 'flarum/common/Component';
import type Mithril from 'mithril';
interface DomainRow {
    domain: string;
    url_count: number;
    total_clicks: number;
    unique_users: number;
    first_clicked: string | null;
    last_clicked: string | null;
}
export default class LinkClicksDomains extends Component {
    protected loading: boolean;
    protected rows: DomainRow[];
    oninit(vnode: Mithril.Vnode): void;
    protected load(): Promise<void>;
    view(): Mithril.Children;
}
export {};
