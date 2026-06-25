import './bootstrap';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';

Alpine.plugin(persist);
Alpine.plugin(focus);

// ── Hero Slider ──────────────────────────────────────────
Alpine.data('heroSlider', () => ({
    current: 0,
    total: 0,
    timer: null,

    init() {
        this.total = this.$el.querySelectorAll('[data-slide]').length;
        if (this.total > 1) this.startAuto();
    },

    destroy() {
        if (this.timer) clearInterval(this.timer);
    },

    startAuto() {
        if (this.timer) clearInterval(this.timer);
        this.timer = setInterval(() => this.next(), 4500);
    },

    next() {
        this.current = (this.current + 1) % this.total;
    },

    prev() {
        this.current = (this.current - 1 + this.total) % this.total;
    },

    goTo(index) {
        this.current = index;
        this.startAuto();
    },
}));

// ── Site Search ──────────────────────────────────────────
Alpine.data('siteSearch', () => ({
    open: false,
    query: '',
    results: [],
    loading: false,

    toggle() {
        this.open = !this.open;
        if (this.open) {
            this.$nextTick(() => {
                const input = this.$el.querySelector('input[type=text]');
                if (input) input.focus();
            });
        } else {
            this.query = '';
            this.results = [];
        }
    },

    async search() {
        if (this.query.length < 2) { this.results = []; return; }
        this.loading = true;
        try {
            const res = await fetch(`/search?q=${encodeURIComponent(this.query)}`, {
                headers: { 'Accept': 'application/json' }
            });
            this.results = await res.json();
        } catch (e) {
            this.results = [];
        }
        this.loading = false;
    },
}));

// ── Cart ─────────────────────────────────────────────────
Alpine.data('cart', () => ({
    open: false,
    items: [],
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
}));

// ── Mobile Menu ───────────────────────────────────────────
Alpine.data('mobileMenu', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
}));

// ── Toast System ──────────────────────────────────────────
window.showToast = function(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const colors = {
        success: 'bg-green-500',
        error:   'bg-red-500',
        info:    'bg-primary',
        warning: 'bg-amber-500',
    };

    const toast = document.createElement('div');
    toast.className = `toast-item pointer-events-auto flex items-center gap-2 px-4 py-3 rounded-xl text-white text-sm font-medium shadow-lg ${colors[type] || colors.info}`;
    toast.innerHTML = `
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            ${type === 'success'
                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
                : type === 'error'
                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'
                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
        </svg>
        <span>${message}</span>`;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// ── Sticky Header ─────────────────────────────────────────
window.addEventListener('scroll', () => {
    const header = document.getElementById('main-header');
    if (header) {
        header.classList.toggle('header-scrolled', window.scrollY > 50);
        header.classList.toggle('shadow-md', window.scrollY > 50);
    }
}, { passive: true });

// ── Back to Top ───────────────────────────────────────────
window.addEventListener('scroll', () => {
    const btn = document.getElementById('back-to-top');
    if (btn) btn.classList.toggle('hidden', window.scrollY < 300);
}, { passive: true });

window.scrollToTop = function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ── Scroll Animations ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('opacity-100', 'translate-y-0');
                entry.target.classList.remove('opacity-0', 'translate-y-4');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-animate]').forEach(el => {
        el.classList.add('opacity-0', 'translate-y-4', 'transition-all', 'duration-500');
        observer.observe(el);
    });
});

window.Alpine = Alpine;
Alpine.start();
