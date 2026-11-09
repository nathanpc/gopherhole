#!/usr/bin/env perl

## update-compat-images.pl
## Ensures that all images in our website and gopherhole have their compatible
## version generated and are maintained up-to-date.
##
## Author: Nathan Campos <hi@nathancampos.me>

use strict;
use warnings;

use File::Basename;
use File::Find;
use Cwd 'abs_path';

# Go through the website folder looking for images.
my $proj_folder = dirname(dirname(abs_path($0)));
find({ wanted => \&process_file, follow => 0, no_chdir => 1 }, $proj_folder);

# Processes all files found in the website folder.
sub process_file {
	# Filter for images only.
	if (m/\.(jpe?g|png|bmp|tiff?)$/i) {
		my $compat = $_ . '_compat.gif';

		# Check if a compatible version already exists.
		if (-e $compat) {
			my $mod_orig = (stat($_))[9];
			my $mod_compat = (stat($compat))[9];

			# Check if the original image has been updated.
			if ($mod_orig > $mod_compat) {
				print "[UPDATE] $_\n";
				print create_compat($_) . "\n";
			}
		} else {
			# Create a brand new compatible version of the image.
			print "[NEW] $_\n";
			print create_compat($_) . "\n";
		}
	}
}

# Creates a compatible version of the image.
sub create_compat {
	my ($fn) = @_;
	return readpipe("$proj_folder/bin/img-compat.sh $fn");
}
