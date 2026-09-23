#!/bin/sh
# Return the demo to its pre-install state.
cd "$(dirname "$0")" && rm -rf storage/alba storage/*.sqlite storage/installed_at.txt .env app/Edition && mkdir -p app/Edition app/Legacy && echo "old file removed by the installer" > app/Legacy/old.txt
echo "Demo reset."
