import assert from 'node:assert/strict';
import test, { after } from 'node:test';
import { renderToString } from '@vue/server-renderer';
import { createSSRApp, h } from 'vue';
import { createServer } from 'vite';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/index.esm.js';

const vite = await createServer({
    appType: 'custom',
    logLevel: 'silent',
    server: { middlewareMode: true },
});

after(async () => {
    await vite.close();
});

test('ThreadList renders its last-post link during SSR', async () => {
    const { default: ThreadList } = await vite.ssrLoadModule('/resources/js/Components/ThreadList.vue');
    const threads = {
        data: [{
            id: 42,
            title: 'Discussion de test',
            pinned: false,
            locked: false,
            created_at: '2026-08-05T12:00:00.000Z',
            reply_count: 20,
            visible_posts_count: 21,
            author: { name: 'Alice' },
            category: { id: 3, title: 'Général' },
            lastPost: {
                created_at: '2026-08-05T12:30:00.000Z',
                author: { name: 'Bob' },
            },
        }],
    };
    const ziggy = {
        url: 'https://vinyls-collection.com',
        port: null,
        defaults: {},
        routes: {
            'forum.thread.show': {
                uri: 'forum/thread/{thread_id}',
                methods: ['GET', 'HEAD'],
                parameters: ['thread_id'],
            },
            'forum.category.show': {
                uri: 'forum/category/{category_id}',
                methods: ['GET', 'HEAD'],
                parameters: ['category_id'],
            },
        },
        location: new URL('https://vinyls-collection.com/forum/recent'),
    };

    const app = createSSRApp({
        render: () => h(ThreadList, { threads }),
    });
    app.use(ZiggyVue, ziggy);

    const html = await renderToString(app);

    assert.match(html, /forum\/thread\/42\?page=2&amp;scroll=last/);
});
