<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fffaf3">
    <meta name="description" content="Conoce Babybu Toys y visita nuestras redes sociales en TikTok e Instagram.">
    <title>Babybu Toys | Juguetes y sonrisas</title>
    <link rel="stylesheet" href="{{ asset('css/babybu-portada.css') }}">
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <header class="site-header">
        <div class="shell header-inner">
            <a href="{{ route('inicio') }}" class="brand" aria-label="Babybu Toys - inicio">
                <span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                <span class="brand-word">babybu<span>.</span><small>TOYS</small></span>
            </a>
            <nav class="primary-nav" aria-label="Navegación principal">
                <a href="#inicio">Inicio</a>
                <a href="#videos">Videos</a>
                <a href="#redes">Redes sociales</a>
            </nav>
            @auth
                <a class="login-link" href="{{ route('panel') }}">Ir al panel <span aria-hidden="true">↗</span></a>
            @else
                <a class="login-link" href="{{ route('login') }}">Iniciar sesión <span aria-hidden="true">↗</span></a>
            @endauth
        </div>
    </header>

    <main id="contenido">
        <section class="hero shell" id="inicio" aria-labelledby="hero-title">
            <div class="hero-content">
                <span class="eyebrow"><span class="eyebrow-dot"></span> BIENVENIDOS A BABYBU TOYS</span>
                <h1 id="hero-title">Un mundo de <span>juguetes</span> para descubrir<span class="hero-period">.</span></h1>
                <p>Conoce Babybu Toys y acompáñanos en nuestras redes sociales, donde compartimos contenido de la juguetería.</p>
                <div class="hero-buttons">
                    <a href="https://www.tiktok.com/@babybu.toysgt" class="button button-dark" target="_blank" rel="noopener noreferrer">
                        <svg class="brand-icon brand-icon-fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        Visitar TikTok
                        <span aria-hidden="true">↗</span>
                    </a>
                    <a href="https://www.instagram.com/babybu.toysgt/" class="button button-outline" target="_blank" rel="noopener noreferrer">
                        <svg class="brand-icon brand-icon-fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.295 1.6361.4961 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.147-.2652 2.9098-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.005 11.977.0014 8.718.0076 8.31.0215 7.0301.0839m.1402 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.228.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.006-4.848-.0608M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077"/></svg>
                        Visitar Instagram
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>
                <p class="social-handle">Síguenos como <strong>@babybu.toysgt</strong></p>
            </div>
            <div class="hero-art" role="img" aria-label="Ilustración decorativa de bloques de juguete de colores y una caja de regalo">
                <span class="hero-art-sun" aria-hidden="true"></span>
                <span class="deco-star star-one" aria-hidden="true">✳</span>
                <span class="deco-star star-two" aria-hidden="true">✦</span>
                <div class="toy-illustration" aria-hidden="true">
                    <div class="gift">
                        <span class="gift-bow bow-left"></span><span class="gift-bow bow-right"></span>
                        <span class="gift-top"></span><span class="gift-base"></span>
                        <span class="gift-ribbon"></span>
                    </div>
                    <div class="blocks"><span class="block block-coral">B</span><span class="block block-yellow">A</span><span class="block block-mint">B</span></div>
                    <div class="toy-ball"></div>
                </div>
                <div class="art-note">babybu <span>♡</span> toys</div>
            </div>
        </section>

        <section class="video-section" id="videos" aria-labelledby="video-title">
            <div class="shell">
                <div class="section-heading">
                    <div><span class="section-eyebrow">CONTENIDO EN TIKTOK</span><h2 id="video-title">Mira nuestros videos</h2><p>Dos espacios para destacar publicaciones de Babybu Toys.</p></div>
                    <a href="https://www.tiktok.com/@babybu.toysgt" class="section-link" target="_blank" rel="noopener noreferrer">Ver perfil de TikTok <span aria-hidden="true">↗</span></a>
                </div>
                <div class="videos-grid">
                    @foreach($videos as $video)
                        <article class="video-card">
                            <div class="video-frame {{ $video['id'] ? 'video-with-player' : '' }}">
                                @if($video['id'])
                                    <iframe
                                        src="https://www.tiktok.com/player/v1/{{ $video['id'] }}?description=1&amp;music_info=1&amp;rel=0"
                                        title="Video {{ $loop->iteration }} de TikTok de Babybu Toys"
                                        loading="lazy"
                                        allow="fullscreen; encrypted-media; picture-in-picture"
                                        allowfullscreen
                                        referrerpolicy="strict-origin-when-cross-origin"></iframe>
                                @else
                                    <div class="video-empty">
                                        <span class="video-play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 6 10 6-10 6z"/></svg></span>
                                        <strong>Video {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</strong>
                                        <span>Abre el video original en TikTok. Si el reproductor está disponible, aparecerá aquí.</span>
                                        @if($video['url'])
                                            <a class="video-open" href="{{ $video['url'] }}" target="_blank" rel="noopener noreferrer">Abrir publicación ↗</a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="video-meta"><strong>Video {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</strong><a href="{{ $video['url'] ?: 'https://www.tiktok.com/@babybu.toysgt' }}" target="_blank" rel="noopener noreferrer">@babybu.toysgt ↗</a></div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="follow-section shell" id="redes" aria-labelledby="follow-title">
            <div class="follow-copy"><span class="section-eyebrow">ENCUÉNTRANOS EN REDES</span><h2 id="follow-title">Estamos en TikTok e Instagram</h2><p>Sigue nuestros perfiles para conocer el contenido que compartimos.</p></div>
            <div class="follow-actions">
                <a class="follow-chip" href="https://www.tiktok.com/@babybu.toysgt" target="_blank" rel="noopener noreferrer"><span class="follow-emoji" aria-hidden="true"><svg class="brand-icon brand-icon-fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></span><span>TikTok<small>@babybu.toysgt</small></span><span aria-hidden="true">↗</span></a>
                <a class="follow-chip" href="https://www.instagram.com/babybu.toysgt/" target="_blank" rel="noopener noreferrer"><span class="follow-emoji instagram-icon" aria-hidden="true"><svg class="brand-icon brand-icon-fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.295 1.6361.4961 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.147-.2652 2.9098-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.005 11.977.0014 8.718.0076 8.31.0215 7.0301.0839m.1402 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.228.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.006-4.848-.0608M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077"/></svg></span><span>Instagram<small>@babybu.toysgt</small></span><span aria-hidden="true">↗</span></a>
            </div>
        </section>
    </main>

    <footer class="site-footer"><div class="shell footer-inner"><span>© {{ date('Y') }} Babybu Toys</span><span>Juguetes y sonrisas</span><a href="{{ route('login') }}">Acceso administrativo ↗</a></div></footer>
</body>
</html>
