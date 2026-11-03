<?php

/*
|--------------------------------------------------------------------------
| Integrated Debrief System (IDS) settings
|--------------------------------------------------------------------------
|
| Every app-specific setting lives here so there is one place to look.
| Values come from .env so the same code runs locally and on the server.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | First admin account (database/seeders/AdminUserSeeder.php)
    |--------------------------------------------------------------------------
    |
    | Locally the password defaults to "password". In production ADMIN_PASSWORD
    | must be set before running `php artisan db:seed`.
    |
    */
    'admin' => [
        'email' => env('ADMIN_EMAIL') ?: 'super@email.com',
        'password' => env('ADMIN_PASSWORD') ?: null,
    ],

    /*
    |--------------------------------------------------------------------------
    | FDR (Flight Data Recorder) parser
    |--------------------------------------------------------------------------
    |
    | Uploaded FDR files are converted to the normalised telemetry JSON by the
    | Node.js package in /fdr-parser.
    |
    | driver: "cli"  -> Laravel runs `node fdr-parser/bin/fdr-parse.js` (default, no extra service)
    |         "http" -> Laravel POSTs the file to `node fdr-parser/bin/fdr-server.js`
    |
    */
    'fdr' => [
        'driver' => env('FDR_PARSER_DRIVER', 'cli'),
        'node_binary' => env('NODE_BINARY', 'node'),
        'cli_script' => base_path('fdr-parser/bin/fdr-parse.js'),
        'http_url' => env('FDR_PARSER_URL', 'http://127.0.0.1:3100'),
        'timeout' => (int) env('FDR_PARSER_TIMEOUT', 300),

        // Timezone of the timestamps written inside DJI logs (DJI app writes local time).
        'log_timezone_offset' => env('FDR_LOG_TZ_OFFSET', '+08:00'),

        // Telemetry is resampled down to this rate (Hz) to keep the JSON small.
        'max_sample_rate' => (int) env('FDR_MAX_SAMPLE_RATE', 10),

        // Real DJI .txt flight records are encrypted. Their keys are fetched once per file
        // from DJI with this free App Key (needs internet; see fdr-parser/README.md) and
        // kept in keychains_dir, so the same file can be processed again offline.
        'dji_api_key' => env('DJI_API_KEY'),
        'keychains_dir' => env('DJI_KEYCHAINS_DIR', storage_path('app/dji-keychains')),

        // Allowed upload extensions and size (kilobytes).
        'extensions' => ['txt', 'csv', 'json'],
        'max_upload_kb' => (int) env('FDR_MAX_UPLOAD_KB', 204800),
    ],

    /*
    |--------------------------------------------------------------------------
    | BVR (video recording) uploads
    |--------------------------------------------------------------------------
    */
    'bvr' => [
        'extensions' => ['mp4', 'webm', 'mov'],
        'max_upload_kb' => (int) env('BVR_MAX_UPLOAD_KB', 2097152),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Disk used for uploaded files (FDR, BVR, 3D models) and generated JSON.
    | "local" = storage/app/private. Files are never served directly; they go
    | through controllers so access is permission-checked.
    |
    */
    'disk' => env('IDS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Offline map (Three.js presentation)
    |--------------------------------------------------------------------------
    |
    | Map tiles are served from our own server (public/tiles) so the
    | presentation works without internet. Download them with:
    |   npm run tiles:download -- --help
    |
    */
    'map' => [
        'tile_url' => env('MAP_TILE_URL', '/tiles/{z}/{x}/{y}.jpg'),
        'min_zoom' => (int) env('MAP_MIN_ZOOM', 5),
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 16),
        'attribution' => env('MAP_ATTRIBUTION', 'Sentinel-2 cloudless by EOX IT Services GmbH (contains modified Copernicus Sentinel data)'),

        // Real ground size of one imagery pixel in metres (Sentinel-2 = 10). Tunes the
        // sharpening filter; change it if you switch to sharper imagery (e.g. a drone map).
        'source_resolution_m' => (float) env('MAP_SOURCE_RESOLUTION_M', 10),

        // The map only renders and allows the camera inside this box (Malaysia).
        // [west, south, east, north] in degrees.
        'bounds' => [99.5, 0.75, 119.4, 7.6],
    ],

    /*
    |--------------------------------------------------------------------------
    | 3D aircraft model
    |--------------------------------------------------------------------------
    |
    | Used when an aircraft type has no model uploaded.
    |
    */
    'default_model' => [
        'url' => '/models/su30.glb',
        'length_m' => 21.9,
        'yaw_offset_deg' => -90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Known locations (airbases)
    |--------------------------------------------------------------------------
    |
    | Suggested in the flight/group forms and used to centre the map when a
    | flight has no telemetry yet.
    |
    */
    'locations' => [
        'Langkawi' => ['lat' => 6.3297, 'lng' => 99.7287],
        'Gong Kedak' => ['lat' => 5.7973, 'lng' => 102.4894],
        'Subang' => ['lat' => 3.1306, 'lng' => 101.5490],
        'Butterworth' => ['lat' => 5.4659, 'lng' => 100.3910],
        'Kuantan' => ['lat' => 3.7754, 'lng' => 103.2094],
        'Labuan' => ['lat' => 5.3007, 'lng' => 115.2500],
        // Where the real DJI sample flights were flown (database/sample-data/dji).
        'Batu Pahat' => ['lat' => 1.7480, 'lng' => 102.9880],
        'Parit Raja' => ['lat' => 1.8558, 'lng' => 103.0848],
        'Pagoh' => ['lat' => 2.1505, 'lng' => 102.7295],
    ],

];
