import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import Stream from 'flarum/common/utils/Stream';
import EditTagModal from 'ext:flarum/tags/admin/components/EditTagModal';

export default function extendEditTagModal() {
  extend(EditTagModal.prototype, 'oninit', function () {
    this.linkClicksDisabled = Stream(this.tag.attribute<boolean>('linkClicksDisabled') ?? false);
  });

  extend(EditTagModal.prototype, 'fields', function (items) {
    items.add(
      'linkClicksDisabled',
      <div className="Form-group">
        <div>
          <label className="checkbox">
            <input type="checkbox" bidi={this.linkClicksDisabled} />
            {app.translator.trans('datlechin-link-clicks.admin.tags.disable_tracking_label')}
          </label>
          <div className="helpText">{app.translator.trans('datlechin-link-clicks.admin.tags.disable_tracking_help')}</div>
        </div>
      </div>,
      5
    );
  });

  extend(EditTagModal.prototype, 'submitData', function (data) {
    data.linkClicksDisabled = this.linkClicksDisabled();
  });
}
