# Load Testing

## Setup

Install k6 for your platform as described at https://k6.io/docs/get-started/installation/.

You need to get the assets referenced at `infrastructure/load_testing/k6/scripts/browser_extension_journey.js` from https://www.dropbox.com/sh/0srr181wj121db9/AACbLkGnnIj_XO3V8N3v1Adfa?dl=0 and put them in place locally - change the paths in the JS file if needed.

## Running

Run the following command from the `infrastructure/load_testing/k6` directory:

    k6 run --vus 10 --duration 60s scripts/browser_extension_journey.js

This starts a load test with 10 concurrent users surfing the site for 60 seconds.
