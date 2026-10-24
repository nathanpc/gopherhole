#!/bin/sh

### zerofill.sh
### Fills the remaining space on the current drive with zeroes.
###
### Author: Nathan Campos <nathan@innoveworkshop.com>

echo "Zero filling the current drive..."
sudo dd if=/dev/zero of=/.vmware-zero-fill bs=4M oflag=direct conv=excl,fsync \
	status=progress

echo "Reclaiming disk space..."
sudo sync /.vmware-zero-fill && sudo rm -- /.vmware-zero-fill && sync

echo "Done."