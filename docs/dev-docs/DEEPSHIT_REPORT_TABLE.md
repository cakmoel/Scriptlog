# Deepshit Results Table

This file holds the Phase 6 results table for `deepshit.sh`. Fill it in after
Phase 1 (profiling). Add the load-test columns after Phase 4 if you run it.
Phase 6 writes `RESULTS.md` in the run directory automatically as an inventory.
This file is the human-readable summary that turns those artifacts into
comparable numbers.

`RESULTS.md` holds the run header, the list of cachegrind files, the Phase 1
manifest, and the names of the load-test report files. It does not hold the
`ab` measurements, so take the Phase 4 numbers from the `ab-*.txt` reports
themselves.

Use one copy of this file per run, or keep one file and replace the values.
Keep the column order so runs can be compared directly. Phase 6 already records
the run ID, start time, site, and web SAPI in `RESULTS.md`; this file holds the
table and the findings only.

## Phase 6 results table

Fill one row for each of the 11 URLs that Phase 1 profiles. The first five data
columns (HTTP through Incl %) come from Phase 1. The last five (ab req/s through
Error %) come from Phase 4. The last row is the Phase 4 404 URL (`/?p=99999`),
which is not profiled, so its profile columns stay `-`. If you override
`MONTHLY_ARCHIVE_PATH`, change the archive row to match. Use `-` when a value
does not apply.

| Endpoint | HTTP | KCachegrind file | Top self-cost function | Self % | Incl % | ab req/s | ab avg ms | P95 ms | P99 ms | Error % |
|----------|------|------------------|------------------------|--------|--------|----------|-----------|--------|--------|---------|
| `/` | | | | | | | | | | |
| `/?p=1` | | | | | | | | | | |
| `/?cat=1` | | | | | | | | | | |
| `/?tag=php` | | | | | | | | | | |
| `/?a=202607` | | | | | | | | | | |
| `/blog` | | | | | | | | | | |
| `/search?q=test` | | | | | | | | | | |
| `/?privacy` | | | | | | | | | | |
| `/admin/login.php` | | | | | | | | | | |
| `/?p=2` | | | | | | | | | | |
| `/?q=php` | | | | | | | | | | |
| `/?p=99999` (Phase 4 only) | - | - | - | - | - | | | | | |

## Column guide

The table mixes two phases. Fill the profiling columns after Phase 1. Fill the
load-test columns after Phase 4, and only for the URLs that Phase 4 tested.
Leave `-` in any cell that does not apply.

### Phase 1 columns (profiling)

| Column | What to put there |
|--------|-------------------|
| Endpoint | The URL path from the Phase 1 list, or the Phase 4 404 URL. |
| HTTP | The `http_code` value from `profile-manifest.tsv`. |
| KCachegrind file | The `profile_file` value from `profile-manifest.tsv`. |
| Top self-cost function | The function with the highest self cost in KCachegrind's Flat Profile. Sort the profile by `Total Self`. |
| Self % | The percentage of the profiled total spent in this function itself. |
| Incl % | The percentage of the profiled total spent in this function and everything it calls. |

Self % and Incl % are shares of the Xdebug-profiled total, not wall-clock
timings. Xdebug adds overhead, so compare them with other profiled runs, and
compare `ab` numbers with other `ab` numbers.

### Phase 4 columns (load tests)

These come from the matching `ab-*.txt` report in the Phase 4 run's
`load-tests/` directory. If Phase 6 runs as a separate invocation, it uses the
newest `load-tests/` directory under `LOG_DIR`. These columns describe load
behavior, not the Xdebug profile. `RESULTS.md` lists the report file names only,
so read the numbers from each report.

| Column | What to put there |
|--------|-------------------|
| ab req/s | `Requests per second` from the report. |
| ab avg ms | The first `Time per request` line, which is the mean. Ignore the second `Time per request` line, which is the mean across all concurrent requests. |
| P95 ms | The `95%` line in the `Percentage of the requests served within a certain time (ms)` block. This is the 95th percentile response time. |
| P99 ms | The `99%` line in the same block. This is the 99th percentile response time. |
| Error % | `Non-2xx responses` divided by `Complete requests`, times 100. If the report has no `Non-2xx responses` line, the value is 0. |

## Phase 4 load-test URLs

When `ab` is installed, Phase 4 tests four URLs and writes one ApacheBench
report for each:

| URL | Report |
|-----|--------|
| `/` | `ab-homepage.txt` |
| `/?p=1` | `ab-single-post.txt` |
| `/search?q=test` | `ab-search.txt` |
| `/?p=99999` | `ab-404.txt` |

The first three match rows in the table above. `/?p=99999` is the 404 load test
and is not in the Phase 1 list, so it has its own row. Do not copy `ab-404.txt`
numbers into the `/?p=2` row: `/?p=2` is the Phase 1 404 profile, and Phase 4
used a different URL.

Phase 4 also runs one siege mix and writes `siege-mixed.txt`. The table has no
siege columns, so record the siege numbers in the notes if you need them.

The other Phase 1 endpoints keep `-` in the `ab` columns.

## Notes for the analysis

Record the main findings under the table after filling it in:

- The top self-cost function on each endpoint.
- Any endpoint whose Self % or Incl % is clearly higher than the others.
- Any load-test failure or non-2xx response.
- Any `ab` Length mismatch lines and whether the Failed requests count is zero.
- The next optimization candidate, with the profile evidence that supports it.

Keep code changes out of this file. The table measures. The follow-up plan
decides what to change.
