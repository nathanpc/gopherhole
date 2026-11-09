#!/usr/bin/env perl

## list-page-paths.pl
## Lists all of the paths to visible pages on the website.
##
## Author: Nathan Campos <hi@nathancampos.me>

use strict;
use warnings;

use File::Basename;
use File::Find;
use Cwd 'abs_path';

# Go through the website folder looking for visible pages.
my $proj_folder = dirname(dirname(abs_path($0)));
find({ wanted => \&process_file, follow => 0, no_chdir => 1 },
	"$proj_folder/htdocs");
find({ wanted => \&process_file, follow => 0, no_chdir => 1 },
	"$proj_folder/gopher/phlog");

# Processes all files found in the website folder.
sub process_file {
	if (m!index\.(php|html?|asp)$!i) {
		print "$_\n";
	}
}
