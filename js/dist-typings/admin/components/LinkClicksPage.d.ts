import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import type Mithril from 'mithril';
type Tab = 'settings' | 'analytics' | 'webhook';
export default class LinkClicksPage extends ExtensionPage {
    protected activeTab: Tab;
    protected setTab(tab: Tab): void;
    content(): Mithril.Children;
    protected renderActiveTab(): Mithril.Children;
    protected renderTabButton(tab: Tab, icon: string): Mithril.Children;
    protected renderSettings(): Mithril.Children;
    protected renderWebhook(): Mithril.Children;
    protected renderAnalytics(): Mithril.Children;
}
export {};
