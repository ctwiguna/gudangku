import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

/**
 * Resolusi halaman memakai import.meta.glob eager supaya:
 * 1. Semua halaman ikut ter-bundle, jadi refresh di route dalam tidak 404.
 * 2. Tidak ada request dinamis tambahan ke server untuk memuat komponen.
 */
const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });

createInertiaApp({
    title: (title) => (title ? `${title} · gudangku` : 'gudangku'),
    resolve: (name) => {
        const page = pages[`./Pages/${name}.jsx`];

        if (!page) {
            throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
        }

        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#0F766E',
    },
});
