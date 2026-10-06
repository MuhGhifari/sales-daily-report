/*
 * Which data backend the pages use.
 * - demo: everything runs in the browser with generated data (GitHub Pages).
 * - laravel: data comes from the Laravel server (server/deploy/sync-frontend.sh writes this file there).
 */
window.APP_CONFIG = { backend: 'demo' };
