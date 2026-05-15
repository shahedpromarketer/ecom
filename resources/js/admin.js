import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

Alpine.plugin(persist);

// ─── Theme store (light / dark) ───────────────────────────────────────────────
Alpine.store('theme', {
    dark: localStorage.getItem('adminTheme') === 'dark',

    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('adminTheme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
    },

    init() {
        document.documentElement.classList.toggle('dark', this.dark);
    },
});

// ─── Sidebar (mobile toggle only) ─────────────────────────────────────────────
Alpine.data('sidebar', () => ({
    mobileOpen: false,

    toggle() {
        this.mobileOpen = !this.mobileOpen;
    },

    closeMobile() {
        this.mobileOpen = false;
    },
}));

// ─── Confirm-delete modal (event-driven) ──────────────────────────────────────
Alpine.data('confirmDelete', () => ({
    open: false,
    url: '',
    show(url) {
        this.url = url;
        this.open = true;
    },
    cancel() {
        this.open = false;
        this.url = '';
    },
}));

window.confirmDelete = function (id, url) {
    window.dispatchEvent(new CustomEvent('open-confirm-delete', { detail: { url } }));
};

window.Alpine = Alpine;
Alpine.start();
