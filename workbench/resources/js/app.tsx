import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolve: (name: string): any => {
        const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });
        return pages[`./Pages/${name}.tsx`];
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setup({ el, App, props }: any) {
        createRoot(el).render(<App {...props} />);
    },
});
