import Component from 'flarum/common/Component';
import type Mithril from 'mithril';
interface BreakdownResponse {
    total: number;
    devices: Record<string, number>;
    browsers: Record<string, number>;
    matrix: {
        device_class: string;
        browser_family: string;
        total_clicks: number;
    }[];
}
export default class LinkClicksDeviceBreakdown extends Component {
    protected loading: boolean;
    protected data: BreakdownResponse | null;
    oninit(vnode: Mithril.Vnode): void;
    protected load(): Promise<void>;
    view(): Mithril.Children;
    protected renderColumn(group: 'devices' | 'browsers', counts: Record<string, number>, icons: Record<string, string>, total: number): Mithril.Children;
}
export {};
