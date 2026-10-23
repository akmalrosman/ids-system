# fdr-parser

Node.js (≥ 20) package that converts Flight Data Recorder files into the **ids-telemetry** JSON used by
the debrief charts and 3D presentation. It has no npm dependencies.

```bash
node bin/fdr-parse.js ../database/sample-data/dji/<file>.txt --pretty         # print JSON
node bin/fdr-parse.js <file> --output out.json --tz +08:00 --max-rate 10
node bin/fdr-server.js                                                # optional HTTP service on :3100
npm test                                                              # unit tests
node tools/generate-dummy-dji.js                                      # rebuild database/sample-data/dji
```

Laravel calls it from `App\Services\Fdr\NodeCliFdrParser` (or `NodeHttpFdrParser` when
`FDR_PARSER_DRIVER=http`). Errors are printed to stderr as `{"error": "...", "code": "..."}`.

## Layout

```
bin/fdr-parse.js        CLI used by Laravel
bin/fdr-server.js       same thing over HTTP (POST /parse)
src/index.js            parseFile() / parseBuffer()
src/adapters/           one file per input format + index.js (detection order)
src/normalize.js        cleaning, resampling, derived values, stats, output JSON
src/lib/                csv, units, time, geo helpers
tools/generate-dummy-dji.js
test/
```

## Supported inputs

| Adapter | Detected by | Notes |
| --- | --- | --- |
| `dji-binary` | binary bytes | Real `DJIFlightRecord_*.txt`. Decoded by the external `dji-log` CLI, see below. |
| `dji-log-json` | JSON with `"osd"` | Output of `dji-log` |
| `dji-csv` | `OSD.latitude` header | Decoded DJI flight record (FlightRecord / PhantomHelp CSV). The dummy logs use this. |
| `airdata-csv` | `time(millisecond)` header | AirData UAV export |
| `generic-csv` | `lat` + `lon` columns | Anything else, e.g. converted aircraft data |

Units in headers (`[ft]`, `[MPH]`, `(feet)`, `[m/s]` …) are converted to SI automatically.
DJI `OSD.zSpeed` is NED (positive = descending) and is flipped to "positive = climbing".

### Real DJI logs (when you have the drone)

Since log version 13, DJI encrypts flight records. To read them:

1. Get a free App Key at https://developer.dji.com (create an "Open API" app).
2. Install the `dji-log` CLI from https://github.com/lvauvillier/dji-log-parser (prebuilt binaries in Releases).
3. In Laravel `.env`:
   ```dotenv
   DJI_LOG_PARSER_CMD="dji-log --api-key {apiKey} {input}"
   DJI_API_KEY=your-app-key
   ```
   `{input}` and `{apiKey}` are replaced; the command must print the JSON frames to stdout.
   Check the CLI's `--help` in case its options differ in your version.

Without this, uploading a binary log fails with the clear reason `DJI_BINARY_NOT_CONFIGURED`.

### Adding the aircraft `.tkd` format later

1. Create `src/adapters/tkd.js`:
   ```js
   export default {
       id: 'tkd',
       kind: 'aircraft',                 // attitude is taken from the data, not the path
       detect({ buffer, head }) { /* return true if this is a .tkd */ },
       parse({ buffer, text, options }) {
           return { samples: [/* { t, time, lat, lng, alt, speed, vs, pitch, roll, yaw } */], meta: {} };
       },
   };
   ```
2. Add it to the array in `src/adapters/index.js` (before `generic-csv`).
3. Add `tkd` to `config('ids.fdr.extensions')`.
4. Add a test in `test/`.

Samples use SI units: seconds, degrees, metres, m/s. `normalize.js` does the rest.

## Output: ids-telemetry v1

```jsonc
{
  "format": "ids-telemetry",
  "version": 1,
  "source": { "adapter": "dji-csv", "kind": "drone", "fileName": "…", "aircraftName": "…", "aircraftSerial": "…", "droneType": "…" },
  "startTime": "2025-05-18T16:00:00.000+08:00",   // wall clock of t = 0 (null if the file has no timestamps)
  "duration": 918.4,                               // seconds
  "sampleRate": 5,                                 // samples per second after resampling
  "stats": {
    "samples": 4593, "maxAltitude": 120, "maxSpeed": 13.01, "maxClimbRate": 5, "maxDescentRate": 3,
    "distance": 10927.1, "maxDistanceFromHome": 4197.5, "minBattery": 30,
    "home": { "lat": 6.3297, "lng": 99.7287 },
    "bounds": { "minLat": …, "maxLat": …, "minLng": …, "maxLng": … }
  },
  "channels": {                  // column arrays, all the same length
    "t": [],                     // s since start
    "lat": [], "lng": [],        // degrees
    "alt": [],                   // m above take-off
    "speed": [],                 // horizontal m/s
    "vs": [],                    // vertical m/s, + = climbing
    "pitch": [], "roll": [],     // degrees
    "yaw": [],                   // degrees 0–360, clockwise from north
    "battery": []                // % (only when the source has it)
  },
  "events": [ { "t": 5.2, "type": "mode", "label": "AutoTakeoff" } ]
}
```
