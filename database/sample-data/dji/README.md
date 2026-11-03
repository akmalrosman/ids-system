# Real DJI flight records

Ten flights of a **DJI Mavic 3** in Johor (8 – 30 Oct 2026): seven near Batu Pahat, two at Parit Raja
and one at Pagoh, exactly as the DJI app
saved them (`FlightRecord_<date>_[<time>].txt`, log version 14). They are loaded by
`SampleFlightSeeder` (part of `php artisan migrate --seed`) as flights of aircraft 5201, and one of
them is used by the tests.

The records are **encrypted**. `keychains/` holds the decryption keys DJI returned for each file
(`<sha256 of the file>.json`), so they decode offline and without a DJI App Key. New records need
`DJI_API_KEY` in `.env` and internet once; see `fdr-parser/README.md`.

Sharp map tiles for these areas are in `public/tiles`, downloaded with
`npm run tiles:download -- --bbox 102.92,1.68,103.15,1.92 --zoom 11-15` (Batu Pahat, Parit Raja) and
`--bbox 102.68,2.10,102.78,2.20` (Pagoh).

To try the upload pipeline, upload any of these on **Flights → Add Flight → FDR file**.
