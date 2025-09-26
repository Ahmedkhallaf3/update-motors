<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

   // 'paths' => ['api/*', 'sanctum/csrf-cookie'],

   // 'allowed_methods' => ['*'],

   // 'allowed_origins' => ['*'],

   // 'allowed_origins_patterns' => [],

   // 'allowed_headers' => ['*'],

    //'exposed_headers' => [],

   // 'max_age' => 0,

   // 'supports_credentials' => false,
  
   'paths' => ['api/*', 'sanctum/csrf-cookie', 'images/*'], // <- add custom routes if needed

    'allowed_methods' => ['*'],

    // 'allowed_origins' => ['*'], // or ['https://your-frontend.com']
         'allowed_origins' => [
        'https://motorssooq.com',
        'https://dashboard.motorssooq.com/',
        'https://www.motorssooq.com',
        'http://localhost:3000', // for development
        // Add other domains as needed
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
