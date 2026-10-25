import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { registerCharts } from './charts';

Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);

        try {
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        } catch {
            // Storage unavailable (private mode) — the toggle still works for this page view.
        }

        window.dispatchEvent(new CustomEvent('theme-changed'));
    },
});

registerCharts(Alpine);

Livewire.start();
