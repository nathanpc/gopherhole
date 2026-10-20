#!/usr/bin/env perl

## build-phlog-index.pl
## Builds the Phlog gophermap.
##
## Author: Nathan Campos <nathan@innoveworkshop.com>

use strict;
use warnings;
use autodie;

use File::Basename;

# Open file for writing.
my $phlog_folder = dirname(dirname(__FILE__)) . '/phlog/';
my $gophermap_fn = $phlog_folder . '/gophermap';
open(my $gophermap_fh, '>', $gophermap_fn);
print "Writing phlog gophermap to $gophermap_fn\n";

# Print header part of the file.
print $gophermap_fh <<'HEADER';
Nathan's random musings

 _____  _     _
|  __ \| |   | |
| |__) | |__ | | ___   __ _
|  ___/| '_ \| |/ _ \ / _` |
| |    | | | | | (_) | (_| |
|_|    |_| |_|_|\___/ \__, |
                       __/ |
                      |___/

My stream of thought brought to you in glorious plain text, just as
intended.
HEADER

# Get list of entries in blog directory.
opendir(my $phlog_dir, $phlog_folder);
my @entries = reverse(sort(readdir($phlog_dir)));
closedir($phlog_dir);
my $last_year = '';
foreach (@entries) {
	# Filter only directories.
	my $dir = $phlog_folder . '/' . $_;
	if (-d $dir) {
		# Ignore dot folders.
		next if (m/^\./);

		# Ignore folders that don't contain a post.txt file.
		next if (!-e "$dir/post.txt");

		# Get parts.
		if (m/^(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})_(?<slug>.+)/i) {
			my $date = $+{year} . '-' . $+{month} . '-' . $+{day};
			my $slug = $+{slug};

			# Print year section if needed.
			if ($+{year} ne $last_year) {
				$last_year = $+{year};
				print $gophermap_fh "\ni--- $last_year ----------------------" .
					"---------------------------------------	header null 0" .
					"\n\n";
			}

			# Get post title.
			open(my $fh, '<', $dir . '/post.txt');
			my $title = <$fh>;
			chomp $title;
			$title =~ s/\r//g;
			close($fh);

			# Print post entry.
			print "Adding $date - $title... ";
			print $gophermap_fh "0$date  $title	${date}_${slug}/post.txt\n";

			print "ok\n";
		}
	}
}

# Print page footer.
print $gophermap_fh <<FOOTER;

:)

FOOTER

# Close file handle and exit.
close($gophermap_fh);
print "Done!\n";
