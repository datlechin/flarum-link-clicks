import Component from 'flarum/common/Component';
import type Mithril from 'mithril';
interface HeatmapResponse {
    since: string;
    max: number;
    cells: number[][];
}
export default class LinkClicksHeatmap extends Component {
    protected loading: boolean;
    protected data: HeatmapResponse | null;
    oninit(vnode: Mithril.Vnode): void;
    protected load(): Promise<void>;
    view(): Mithril.Children;
}
export {};
