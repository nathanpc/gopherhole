#!/usr/bin/env perl

## ltgt.pl
## Substitutes the values of variables inside of <^var^> tags.
##
## Author: Nathan Campos <nathan@innoveworkshop.com>

use strict;
use warnings;
use autodie;

use File::Basename;
use File::Spec;

# Define some static substitution variables.
our %tplvars = (
	'base_url' => 'gopher://nathanpc.eu'
);

# Determine the base folder of our gopherhole.
our $base_folder = dirname(dirname(__FILE__));

# Check if we have the appropriate number of arguments.
if (scalar(@ARGV) != 1) {
	print "usage: $0 fname\n\n";
	print "arguments:\n";
	print "    fname  File to have variables substituted\n";
	exit 1;
}

# Determine the file's relative path to our gopherhole.
my $fname = File::Spec->rel2abs($ARGV[0]);
my $tmpname = "$fname.tmp";
my $relfile = File::Spec->abs2rel($fname, $base_folder) =~ s|\\|/|gr;
my $reldir = dirname($relfile);
$tplvars{'relfile'} = $relfile;
$tplvars{'reldir'} = $reldir;

# Read the provided file and substitute the variables in it.
my $ret = 0;
open(my $fh, '<:encoding(utf8)', $fname);
open(my $tmpfh, '>:encoding(utf8)', $tmpname);
foreach my $line (<$fh>) {
	# Substitute variables.
	foreach my $key (keys %tplvars) {
		$line =~ s/\<\^${key}\^\>/${tplvars{$key}}/g;
	}

	# Check for unsubstituted variables.
	if ($line =~ m/\<\^([^\^]+)\^\>/) {
		print STDERR "error: could not find appropriate substitution for " .
			"'$1'\n";
		$ret = 1;
	}

	# Write processed line to output file.
	print $tmpfh $line;
}
close($fh);
close($tmpfh);

# Substitute old file for the new one.
if ($ret == 0) {
	unlink $fname;
	rename $tmpname, $fname;
}

# Return with the proper exit code.
exit $ret;
