import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';

interface HeatmapResponse {
  since: string;
  max: number;
  cells: number[][];
}

const DAY_LABELS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export default class LinkClicksHeatmap extends Component {
  protected loading = true;
  protected data: HeatmapResponse | null = null;

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);
    this.load();
  }

  protected async load(): Promise<void> {
    try {
      this.data = await app.request<HeatmapResponse>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/link-click-stats/heatmap`,
      });
    } catch {
      this.data = null;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  view(): Mithril.Children {
    if (this.loading) {
      return (
        <div className="LinkClicksHeatmap">
          <LoadingIndicator />
        </div>
      );
    }
    if (!this.data || this.data.max === 0) {
      return null;
    }

    const { cells, max } = this.data;

    return (
      <div className="LinkClicksHeatmap">
        <h4 className="LinkClicksHeatmap-title">{app.translator.trans('datlechin-link-clicks.admin.heatmap.title')}</h4>
        <p className="LinkClicksHeatmap-lead">{app.translator.trans('datlechin-link-clicks.admin.heatmap.lead')}</p>

        <div className="LinkClicksHeatmap-grid" style={{ gridTemplateColumns: 'auto repeat(24, 1fr)' }}>
          <div className="LinkClicksHeatmap-cornerCell"></div>
          {Array.from({ length: 24 }, (_, h) => (
            <div className="LinkClicksHeatmap-hourLabel">{h % 3 === 0 ? h : ''}</div>
          ))}
          {DAY_LABELS.map((label, dow) => [
            <div className="LinkClicksHeatmap-dayLabel">{label}</div>,
            cells[dow].map((count, hour) => (
              <div
                className="LinkClicksHeatmap-cell"
                title={`${label} ${hour}:00 — ${count} click${count === 1 ? '' : 's'}`}
                style={{
                  backgroundColor:
                    count === 0 ? 'transparent' : `rgba(var(--primary-color-rgb, 102, 102, 102), ${(0.15 + (count / max) * 0.85).toFixed(2)})`,
                }}
              >
                {count > 0 && count >= max * 0.5 ? count : ''}
              </div>
            )),
          ])}
        </div>
      </div>
    );
  }
}
