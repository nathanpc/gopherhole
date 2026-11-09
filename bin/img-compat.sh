#!/bin/sh

## img-compat.sh
## Makes amazingly small and compatible images to save on bandwidth.
##
## Author: Nathan Campos <nathan@innoveworkshop.com>

# Get the PHP directory.
if [ $# -ne 1 ]; then
	echo "usage: $0 imgpath"
	exit 1
fi
imgpath="$1"

convert "$imgpath" -verbose -resize '770x500>' -unsharp 0.25x0.25+8+0.065 \
	-dither FloydSteinberg -colors 16 -colorspace sRGB -strip -format gif \
	"${imgpath}_compat.gif"
