<?php

return [
    // Los dos videos elegidos por el equipo de Babybu Toys.
    // Se pueden sustituir por URLs completas .../@babybu.toysgt/video/ID
    // desde el archivo .env (no debe subirse a GitHub).
    'videos' => [
        env('BABYBU_TIKTOK_VIDEO_1', 'https://vt.tiktok.com/ZSpFxLv3R/'),
        env('BABYBU_TIKTOK_VIDEO_2', 'https://vt.tiktok.com/ZSpFxYx4E/'),
    ],
];
