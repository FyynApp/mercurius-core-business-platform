# Load Testing

## Setup

Install k6 for your platform as described at https://k6.io/docs/get-started/installation/.

You need to get the assets referenced at `infrastructure/load_testing/k6/scripts/browser_extension_journey.js` from https://www.dropbox.com/sh/0srr181wj121db9/AACbLkGnnIj_XO3V8N3v1Adfa?dl=0 and put them in place locally - change the paths in the JS file if needed.

## Running

Run the following command from the `infrastructure/load_testing/k6` directory:

    k6 run --vus 10 --duration 60s scripts/browser_extension_journey.js

This starts a load test with 10 concurrent users surfing the site for 60 seconds.


## Setup on AWS

Launch a new `c6a.8xlarge` EC2 instance with Ubuntu 22.04. SSH into the instance with user `ubuntu`.

    sudo su -
    apt-get update && apt-get -u dist-upgrade
    mkdir .gnupg
    chmod 0700 .gnupg
    sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
    echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
    sudo apt-get update
    sudo apt-get install k6
    
From your local system, transfer the test scripts and test assets to the EC2 instance:

    rsync -av infrastructure/load_testing/ ubuntu@ADDRESS:/home/ubuntu/load_testing/
    rsync -av ~/path/to/assets/ ubuntu@ADDRESS:/home/ubuntu/load_testing/assets/

SSH back into the instance with user `ubuntu`.  Edit the paths to the assets files in the load testing scripts.

    cd load_testing/k6/scripts
    k6 run --vus 100 --duration 600s browser_extension_journey.js


## Outstanding optimizations

- Increase open files limit for user www-data (`ulimit -n`)
- Put PHP sessions into Redis


## Results

### 2022-12-14 09:30 CET, preprod, browser_extension_journey.js

#### k6 stats

    running (10m30.0s), 000/100 VUs, 802 complete and 100 interrupted iterations
    default ✓ [======================================] 100 VUs  10m0s
    
         data_received..................: 17 MB 27 kB/s
         data_sent......................: 17 GB 27 MB/s
         http_req_blocked...............: avg=597.59µs min=1.05µs  med=3.4µs    max=105.82ms p(90)=4.5µs    p(95)=5.44µs
         http_req_connecting............: avg=78.54µs  min=0s      med=0s       max=10.58ms  p(90)=0s       p(95)=0s
         http_req_duration..............: avg=202.63ms min=30.9ms  med=100.89ms max=7.16s    p(90)=281.15ms p(95)=593.9ms
           { expected_response:true }...: avg=202.64ms min=30.9ms  med=100.85ms max=7.16s    p(90)=281.15ms p(95)=592.65ms
         http_req_failed................: 0.07% ✓ 11        ✗ 13861
         http_req_receiving.............: avg=69.81µs  min=20.17µs med=50.15µs  max=25.6ms   p(90)=83.59µs  p(95)=96.51µs
         http_req_sending...............: avg=1.42ms   min=5.41µs  med=984.74µs max=197.47ms p(90)=1.27ms   p(95)=1.37ms
         http_req_tls_handshaking.......: avg=285.16µs min=0s      med=0s       max=67.92ms  p(90)=0s       p(95)=0s
         http_req_waiting...............: avg=201.13ms min=30.83ms med=99.21ms  max=7.16s    p(90)=280.71ms p(95)=590.37ms
         http_reqs......................: 13872 22.018227/s
         iteration_duration.............: avg=1m12s    min=10.57s  med=1m12s    max=1m24s    p(90)=1m18s    p(95)=1m19s
         iterations.....................: 802   1.272968/s
         vus............................: 100   min=100     max=100
         vus_max........................: 100   min=100     max=100

#### Thoughts and observations

- `ps_files_cleanup_dir` failed with `permission denied`; user www-data had more than its ulimit of 1024 files open


### 2022-12-14 09:50 CET, preprod, browser_extension_journey.js

#### Relevant changes since previous run

- Increased open files limit for user www-data (`ulimit -n`) from 1024 to 8192

#### k6 stats

    running (10m30.0s), 000/100 VUs, 800 complete and 100 interrupted iterations
    default ✓ [======================================] 100 VUs  10m0s
    
         data_received..................: 16 MB 26 kB/s
         data_sent......................: 17 GB 27 MB/s
         http_req_blocked...............: avg=425.68µs min=1.07µs  med=3.42µs   max=51.14ms p(90)=4.47µs   p(95)=5.33µs
         http_req_connecting............: avg=71.66µs  min=0s      med=0s       max=10.32ms p(90)=0s       p(95)=0s
         http_req_duration..............: avg=134.79ms min=29.73ms med=85.89ms  max=2.52s   p(90)=198.98ms p(95)=303.97ms
           { expected_response:true }...: avg=134.73ms min=29.73ms med=85.88ms  max=2.52s   p(90)=198.78ms p(95)=303.74ms
         http_req_failed................: 0.06% ✓ 9         ✗ 14035
         http_req_receiving.............: avg=71.38µs  min=17.03µs med=50.97µs  max=59.34ms p(90)=84.31µs  p(95)=97.57µs
         http_req_sending...............: avg=810.25µs min=5.6µs   med=988.69µs max=3.55ms  p(90)=1.27ms   p(95)=1.33ms
         http_req_tls_handshaking.......: avg=333.47µs min=0s      med=0s       max=43.18ms p(90)=0s       p(95)=0s
         http_req_waiting...............: avg=133.91ms min=29.66ms med=84.79ms  max=2.52s   p(90)=198.69ms p(95)=303.76ms
         http_reqs......................: 14044 22.290933/s
         iteration_duration.............: avg=1m11s    min=1m7s    med=1m11s    max=1m18s   p(90)=1m14s    p(95)=1m15s
         iterations.....................: 800   1.269777/s
         vus............................: 100   min=100     max=100
         vus_max........................: 100   min=100     max=100

#### Thoughts and observations

- `ps_files_cleanup_dir` continues to fail with `permission denied` even though user www-data had had its open file limit set from 1024 to 8192
- Relevant: https://symfony.com/doc/current/components/http_foundation/session_configuration.html#configuring-garbage-collection


### 2022-12-14 10:05 CET, preprod, browser_extension_journey.js

#### Relevant changes since previous run

- Disabled Symfony override of PHP session garbage collection settings, resulting in no session garbage collection at all

#### k6 stats

    running (10m30.0s), 000/100 VUs, 802 complete and 99 interrupted iterations
    default ✓ [======================================] 100 VUs  10m0s
    
         data_received..................: 14 MB 22 kB/s
         data_sent......................: 17 GB 27 MB/s
         http_req_blocked...............: avg=364.69µs min=1.06µs  med=3.49µs  max=77.45ms p(90)=4.57µs   p(95)=5.58µs
         http_req_connecting............: avg=72.08µs  min=0s      med=0s      max=10.37ms p(90)=0s       p(95)=0s
         http_req_duration..............: avg=135.01ms min=30.15ms med=86.31ms max=2.83s   p(90)=201.33ms p(95)=311.92ms
           { expected_response:true }...: avg=134.96ms min=30.15ms med=86.31ms max=2.83s   p(90)=201.27ms p(95)=311.32ms
         http_req_failed................: 0.01% ✓ 2         ✗ 14057
         http_req_receiving.............: avg=55.5µs   min=21.24µs med=50.13µs max=1.87ms  p(90)=81.79µs  p(95)=93.02µs
         http_req_sending...............: avg=830.91µs min=6.1µs   med=1.01ms  max=3.41ms  p(90)=1.28ms   p(95)=1.36ms
         http_req_tls_handshaking.......: avg=278.06µs min=0s      med=0s      max=70.63ms p(90)=0s       p(95)=0s
         http_req_waiting...............: avg=134.13ms min=30.1ms  med=85.17ms max=2.83s   p(90)=200.67ms p(95)=311.79ms
         http_reqs......................: 14059 22.315234/s
         iteration_duration.............: avg=1m11s    min=8.96s   med=1m11s   max=1m17s   p(90)=1m14s    p(95)=1m15s
         iterations.....................: 802   1.272979/s
         vus............................: 99    min=99      max=100
         vus_max........................: 100   min=100     max=100

#### Thoughts and observations

- Thanks to the disabled session garbage collection, the `ps_files_cleanup_dir` error is gone
