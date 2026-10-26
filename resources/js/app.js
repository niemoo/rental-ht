import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { registerCharts } from './charts';

const root = document.documentElement;

Alpine.store('theme', {
    dark: root.getAttribute('data-bs-theme') === 'dark',

    toggle() {
        this.dark = !this.dark;
        root.setAttribute('data-bs-theme', this.dark ? 'dark' : 'light');

        try {
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        } catch {
            // Storage unavailable (private mode) — the toggle still works for this page view.
        }

        window.dispatchEvent(new CustomEvent('theme-changed'));
    },
});

/**
 * Velzon's vertical sidebar: full width on desktop, icon-only on tablets (768–1025px),
 * and an off-canvas panel on phones that the hamburger opens over the page.
 */
Alpine.data('appShell', () => ({
    navOpen: false,

    init() {
        this.fitSidebar();
        window.addEventListener('resize', () => this.fitSidebar());
    },

    fitSidebar() {
        const width = root.clientWidth;
        root.setAttribute('data-sidebar-size', width > 767 && width <= 1025 ? 'sm' : 'lg');

        if (width > 767) {
            this.navOpen = false;
        }
    },

    toggleNav() {
        if (root.clientWidth <= 767) {
            this.navOpen = !this.navOpen;

            return;
        }

        root.setAttribute('data-sidebar-size', root.getAttribute('data-sidebar-size') === 'sm' ? 'lg' : 'sm');
    },
}));

/**
 * Velzon's select (Choices.js, the template's data-choices), started by Alpine rather than Velzon's app.js.
 * The <select> keeps its wire:model or x-model: Choices writes the picked value back to it and fires
 * "change", and a server-side change to a wire:model property is copied back into the widget.
 * Rendered by <x-select>, whose wrapper is wire:ignore so Livewire doesn't morph the widget's markup.
 */
Alpine.data('choicesSelect', (options = {}) => ({
    choices: null,

    init() {
        const select = this.$refs.select;

        // Choices announces a pick as a CustomEvent("change") with detail { value }, and wire:model / x-model
        // take an event's detail over the field's value, so they would receive that object. Catch it on the
        // way down (capture, before the <select>'s own listeners) and re-send a plain change event instead.
        this.$el.addEventListener('change', (event) => {
            if (event.target === select && event instanceof CustomEvent) {
                event.stopPropagation();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }, true);

        // Wait a tick so wire:model / x-model have put the current value on the <select> first.
        this.$nextTick(() => {
            this.choices = new window.Choices(select, {
                searchEnabled: false,
                shouldSort: false,
                itemSelectText: '',
                allowHTML: false,
                ...options,
            });

            const model = [...select.attributes].find((attribute) => attribute.name.startsWith('wire:model'));

            if (model && this.$wire) {
                this.$wire.$watch(model.value, (value) => this.choices.setChoiceByValue(String(value ?? '')));
            }
        });
    },

    destroy() {
        this.choices?.destroy();
    },
}));

/**
 * Velzon's SweetAlert2 dialogs, resolving true when the user confirms. Options: title, text or html (html is
 * server-rendered, already-escaped Blade output), confirmLabel, danger (a delete: red confirm, neutral Batal)
 * and blocked (the action isn't allowed: an info dialog with only "Tutup", always resolving false).
 * The dialog closes as soon as a button is pressed, like Velzon's demo; the caller's own loading state shows
 * progress, since waiting inside the dialog would leave it stuck if a request never came back.
 */
const confirmDialog = (options = {}) => {
    const body = options.html ? { html: options.html } : { text: options.text ?? '' };

    // Velzon's own button classes for SweetAlert2 (its demo passes them with the pre-v11 option names).
    if (options.blocked) {
        return window.Swal.fire({
            title: options.title ?? 'Tidak bisa dilanjutkan',
            ...body,
            icon: 'info',
            showCloseButton: true,
            confirmButtonText: 'Tutup',
            buttonsStyling: false,
            customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
        }).then(() => false);
    }

    return window.Swal.fire({
        title: options.title ?? 'Apakah Anda yakin?',
        ...body,
        icon: 'warning',
        showCancelButton: true,
        showCloseButton: true,
        confirmButtonText: options.confirmLabel ?? 'Ya, simpan',
        cancelButtonText: 'Batal',
        buttonsStyling: false,
        // Batal on the left, the confirm button on the right.
        reverseButtons: true,
        customClass: options.danger
            ? { confirmButton: 'btn btn-danger w-xs mt-2', cancelButton: 'btn btn-light w-xs me-2 mt-2' }
            : { confirmButton: 'btn btn-primary w-xs mt-2', cancelButton: 'btn btn-danger w-xs me-2 mt-2' },
    }).then((result) => result.isConfirmed);
};

/** In markup: x-on:click="$confirm({ … }).then((ok) => ok && $wire.delete(12))". */
Alpine.magic('confirm', () => confirmDialog);

/**
 * Ask before saving. Put x-data="confirmAction({ text: '…', confirmLabel: '…' })" on the form with
 * x-on:submit.prevent="ask('save')" (or x-on:click="ask('save', true)" on another button). The Livewire
 * action only runs once confirmed; the form's own wire:loading state shows progress.
 */
Alpine.data('confirmAction', (options = {}) => ({
    ask(method, ...params) {
        confirmDialog(options).then((confirmed) => {
            if (confirmed) {
                this.$wire[method](...params);
            }
        });
    },
}));

/** 25000 → "25.000"; anything that isn't a digit is dropped. */
const thousands = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');

    return digits === '' ? '' : Number(digits).toLocaleString('id-ID');
};

/**
 * Rupiah field for <x-money-input>: the visible input shows "25.000" as the user types, while the
 * hidden x-ref="field" carries the wire:model and only ever holds the digits ("25000").
 */
Alpine.data('moneyInput', () => ({
    display: '',

    init() {
        const field = this.$refs.field;

        // Wait a tick so wire:model has put the current value on the hidden field first.
        this.$nextTick(() => {
            this.display = thousands(field.value);
        });

        // Show amounts the server sets (e.g. the late-fee suggestion button) with dots too.
        const model = [...field.attributes].find((attribute) => attribute.name.startsWith('wire:model'));

        if (model && this.$wire) {
            this.$wire.$watch(model.value, (value) => {
                this.display = thousands(value);
            });
        }
    },

    type(event) {
        const input = event.target;
        const digitsBeforeCaret = input.value.slice(0, input.selectionStart ?? input.value.length).replace(/\D/g, '').length;
        const digits = input.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');

        this.display = thousands(digits);
        input.value = this.display;

        // Put the caret back after the same number of digits, skipping the dots that were added or removed.
        let caret = 0;
        for (let seen = 0; caret < this.display.length && seen < digitsBeforeCaret; caret++) {
            if (/\d/.test(this.display[caret])) {
                seen++;
            }
        }
        input.setSelectionRange(caret, caret);

        this.$refs.field.value = digits;
        this.$refs.field.dispatchEvent(new Event('input', { bubbles: true }));
    },
}));

registerCharts(Alpine);

Livewire.start();
