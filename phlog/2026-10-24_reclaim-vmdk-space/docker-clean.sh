#!/bin/sh

### docker-clean.sh
### Cleans up all the cruft accumulated by Docker over time.
###
### Author: Nathan Campos <nathan@innoveworkshop.com>

echo "Docker Cleanup Script"

# Show current status to user.
echo "Calculating space used by Docker..."
prevsize=$(sudo du -hs /var/lib/docker/overlay2)
echo ""
echo "Space currently used by Docker images: $prevsize"
echo ""
docker system df
echo ""

# Prune stuff up.
docker image prune -a
docker builder prune -a
echo ""

# Show the difference.
echo "Calculating gains from pruning..."
newsize=$(sudo du -hs /var/lib/docker/overlay2)
echo ""
echo "Previously: $prevsize"
echo "Now: $newsize"
