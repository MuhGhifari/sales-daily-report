/*
 * Which data backend the pages use.
 * - demo: everything runs in the browser with generated data (GitHub Pages).
 * - laravel: data comes from the Laravel server; its Blade layout sets APP_CONFIG itself (this file is not used there).
 */
window.APP_CONFIG = { backend: 'demo' };
