import './bootstrap';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';

Alpine.plugin(persist);
Alpine.plugin(focus);

// Cart drawer
Alpine.data('cart', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
}));

// Mobile menu
Alpine.data('mobileMenu', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
}));

// Toast notifications
Alpine.data('toasts', () => ({
    items: [],
    show(message, type = 'info') {
        const id = Date.now();
        this.items.push({ id, message, type });
        setTimeout(() => this.dismiss(id), 3000);
    },
    dismiss(id) {
        this.items = this.items.filter(t => t.id !== id);
    },
}));

window.Alpine = Alpine;
Alpine.start();
