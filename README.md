# Integrated Debrief System (IDS)

Final-year project: a web system to debrief flights. Flight data (FDR) files are uploaded,
converted to telemetry, shown as charts, and replayed in a 3D map of Malaysia, one aircraft
at a time or a whole formation together, with the cockpit video (BVR) synced beside each aircraft.

| Part | Technology |
| --- | --- |
| Web app, auth, database | Laravel 13 (PHP 8.4+), Blade, Alpine.js |
| Styling | Tailwind CSS 4 |
| Database | PostgreSQL (SQLite works for quick local tries) |
| FDR conversion | Node.js package in [`fdr-parser/`](fdr-parser/README.md) (no npm dependencies) |
| 3D presentation | Three.js, offline satellite tiles in `public/tiles`, `.glb` aircraft models |
| Charts | Chart.js |

---

## 1. Run it on your computer

Requirements: PHP 8.4+ with `pdo_pgsql` (or `pdo_sqlite`), Composer, Node.js 20+, PostgreSQL.

```bash
composer install
npm install
cp .env.example .env          # then set DB_* (or DB_CONNECTION=sqlite)
php artisan key:generate
php artisan migrate --seed    # creates demo data and converts the dummy DJI logs
composer run dev              # web server + queue worker + Vite, together
```

Open http://127.0.0.1:8000 and log in with **super@email.com / password**
(demo pilots: `pilot1@email.com` … `pilot10@email.com`, same password).

> **Windows + PostgreSQL:** enable the extensions in `php.ini` by removing the `;` in front of
> `extension=pdo_pgsql` and `extension=pgsql`.

> **Upload limits:** PHP allows only 2 MB uploads by default. For FDR logs and videos raise these in
> `php.ini`: `upload_max_filesize = 2048M` and `post_max_size = 2100M`. (Docker already does this.)

Useful commands:

| Command | What it does |
| --- | --- |
| `php artisan test` | PHP tests (includes a real FDR upload → Node parser → telemetry run) |
| `npm run fdr:test` | Parser unit tests |
| `npm run fdr:dummy` | Regenerate the dummy DJI logs in `sample-data/dji` |
| `php artisan fdr:process 3` / `--all` | Re-convert one / all FDR files and print the result |
| `node fdr-parser/bin/fdr-parse.js file.txt --pretty` | Run the parser by hand on any file |
| `npm run tiles:download -- --preset malaysia` | Download offline map tiles (see §4) |
| `php artisan route:list --except-vendor` | Every URL and its controller |

---

## 2. How it works

```
Upload FDR (.txt/.csv/.json)
   │  FlightController@store → FlightFileService::storeFdr()  saves storage/app/private/fdr/{id}/…
   ▼
Queue job  App\Jobs\ProcessFdrFile
   │  App\Services\Fdr\NodeCliFdrParser runs:  node fdr-parser/bin/fdr-parse.js <file> --output <json>
   ▼
fdr-parser (Node.js)  detects the format → adapter → normalise (SI units, resample, stats)
   │  writes "ids-telemetry" JSON
   ▼
storage/app/private/telemetry/flight-{id}.json   + summary in flights.stats, status = ready
   │
   ├─ Flight page charts      resources/js/pages/flight-charts.js   (GET /flights/{id}/telemetry)
   └─ 3D presentation         resources/js/presentation/*            (GET /flights/{id}/present, /groups/{id}/present)
```

The FDR status (`none → pending → processing → ready | failed`) is shown on the flight page;
a failed conversion shows the parser's reason (e.g. `[UNKNOWN_FORMAT] …`).

### DJI drone data (current) and aircraft .tkd (later)

Real DJI `DJIFlightRecord_*.txt` files are **binary and encrypted**. The parser accepts:

1. **Decoded DJI CSV** (`OSD.latitude`, `OSD.height [ft]`, …): the dummy files in `sample-data/dji` use this layout.
2. **Real binary `.txt`**: decoded through the open-source `dji-log` CLI with a DJI App Key (set `DJI_LOG_PARSER_CMD` + `DJI_API_KEY`).
3. dji-log JSON, AirData CSV, or any CSV with `lat`/`lon` columns.

Adding `.tkd` later means one new adapter file. See [fdr-parser/README.md](fdr-parser/README.md).

---

## 3. Where things are (to find bugs quickly)

```
app/
  Enums/FdrStatus.php              FDR processing states
  Http/Controllers/                one controller per page group (Flight, FlightGroup, Aircraft, …)
    FlightFileController.php       FDR download, video stream, telemetry JSON, re-process
    PresentationController.php     builds the JSON config for the 3D page
  Http/Requests/                   validation rules for every form
  Jobs/ProcessFdrFile.php          the queued FDR conversion
  Models/                          Flight, FlightGroup, Aircraft, AircraftType, User, Role
  Services/FlightFileService.php   all file storage for a flight
  Services/Fdr/                    Laravel ↔ Node.js parser (cli / http drivers)
  Support/Permission.php           every permission key (used as Gates)
config/ids.php                     ALL app settings (parser, map, model, locations)
database/seeders/                  roles, admin, demo data (DemoSeeder uses sample-data/dji)
fdr-parser/                        Node.js FDR → telemetry converter (+ tests, dummy generator)
resources/views/                   Blade pages; components/ui/* are the shared UI pieces
resources/js/presentation/         Three.js presentation (see main.js for a file map)
resources/js/pages/flight-charts.js
routes/web.php                     all URLs + permission middleware
routes/console.php                 `php artisan fdr:process`
scripts/map-tiles/                 offline map tile downloader
sample-data/dji/                   dummy DJI flight logs
docs/DEPLOYMENT.md                 putting it on a server
```

Debugging tips:

- **Logs:** `storage/logs/laravel.log` (app) and `storage/logs/fdr-*.log` (every FDR conversion).
- **Strict models:** outside production Laravel throws on N+1 queries and on saving attributes
  missing from `#[Fillable]` (`AppServiceProvider`), so mistakes show up immediately.
- **3D page:** open the browser console; `window.idsPresentation` is the running app
  (e.g. `idsPresentation.tracks[0].state`, `idsPresentation.playback.seek(300)`).
- **Parser:** run `node fdr-parser/bin/fdr-parse.js <file> --pretty` to see exactly what Laravel receives.

---

## 4. Offline map (Malaysia only)

The presentation never calls an online map service. Tiles are downloaded once into
`public/tiles/{z}/{x}/{y}.jpg` and listed in `public/tiles/manifest.json`:

```bash
npm run tiles:download -- --preset malaysia                       # whole country, zoom 5–10 (~15 MB)
npm run tiles:download -- --preset airbases                       # airbases in config/ids.php, zoom 11–15
npm run tiles:download -- --bbox 99.6,6.2,99.9,6.45 --zoom 11-16  # any extra area
```

The camera and map are limited to `config('ids.map.bounds')` (Malaysia). Imagery: Sentinel-2
cloudless by EOX (CC BY-NC-SA 4.0, fine for non-commercial/FYP use; the attribution is shown on the map).

## 5. Roles

| Role | Can |
| --- | --- |
| Super Admin | everything |
| Admin | everything except managing roles |
| Engineer | flights, groups, FDR/BVR uploads, aircraft |
| Pilot | view and present flights and groups |

Permissions are editable per role in **Settings → Roles**.

## 6. Deploying

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md): Docker Compose (recommended) or a plain Ubuntu server.
