#!/bin/sh
# Return the demo to its pre-install state.
cd "$(dirname "$0")" && rm -rf storage/alba storage/app/edition .env database/database.sqlite public/storage
rm -f bootstrap/cache/*.php
echo "Laravel demo reset."
