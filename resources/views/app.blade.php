<!DOCTYPE html>
<html lang="ru" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>VK Insights</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        {{-- LCP: фон и hero до CSS/JS Vite; stack без webfont до async-загрузки Instrument Sans --}}
        <style id="vk-lcp-critical">
            html.dark { color-scheme: dark; }
            body { margin: 0; background: #121212; color: #fff; font-family: ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
            #vk-lcp-boot { min-height: 100vh; display: flex; flex-direction: column; background: #121212; color: #fff; }
            #vk-lcp-boot .vk-lcp-header { background: #1a1a1a; box-shadow: 0 1px 0 rgba(255, 255, 255, 0.06); }
            #vk-lcp-boot .vk-lcp-header__inner { max-width: 72rem; margin: 0 auto; display: flex; align-items: center; padding: 0.75rem 1rem; font-weight: 600; }
            #vk-lcp-boot .vk-lcp-main { flex: 1; width: 100%; max-width: 72rem; margin: 0 auto; padding: 1.5rem 1rem 4rem; box-sizing: border-box; }
            @media (min-width: 48rem) { #vk-lcp-boot .vk-lcp-main { padding-top: 2rem; } }
            #vk-lcp-boot .vk-lcp-hero { display: flex; flex-direction: column; align-items: center; margin-bottom: 0.25rem; }
            #vk-lcp-boot .vk-lcp-hero h1 { margin: 0 0 0.75rem; max-width: 42rem; text-align: center; font-size: 1.5rem; font-weight: 700; line-height: 1.2; letter-spacing: -0.02em; }
            @media (min-width: 48rem) { #vk-lcp-boot .vk-lcp-hero h1 { font-size: 1.875rem; } }
            @media (min-width: 64rem) { #vk-lcp-boot .vk-lcp-hero h1 { font-size: 2.25rem; } }
            #vk-lcp-boot .vk-lcp-hero p { margin: 0 0 2rem; max-width: 36rem; text-align: center; font-size: 0.875rem; line-height: 1.5; color: #a3a3a3; }
            @media (min-width: 48rem) { #vk-lcp-boot .vk-lcp-hero p { font-size: 1rem; } }
        </style>
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" media="print" onload="this.media='all'">
        <noscript><link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap"></noscript>
        @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app">
            <div id="vk-lcp-boot">
                <header class="vk-lcp-header">
                    <div class="vk-lcp-header__inner">VK Insights</div>
                </header>
                <main class="vk-lcp-main">
                    <div class="vk-lcp-hero">
                        <h1>Аналитика сообществ ВКонтакте</h1>
                        <p>Введите ID или короткое имя сообщества и выберите период для анализа постов</p>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
