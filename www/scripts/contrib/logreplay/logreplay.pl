#!/usr/bin/perl

#
# logreplay.pl
#
# Developed by Clayton Dukes <cdukes@cdukes.com>
# Copyright (c) 2009 GDD.net
# Licensed under terms of GNU General Public License.
# All rights reserved.
#
# Changelog:
# 2009-06-26 - created
#

# $Platon$

use strict;

$| = 1;

use Sys::Syslog qw( :DEFAULT setlogsock);
use POSIX qw/strftime/;
use Switch;

#
# Declare variables to use
#
my ($InputFileName, $host, $mne, $msg, $sev, @sevs, $month, @month, $day, $time, $seq, $spoof); 
use vars qw/ %opt /;
#
# Command line options processing
#
sub init()
{
	use Getopt::Std;
	my $opt_string = 'hvf:s:';
	getopts( "$opt_string", \%opt ) or usage();
	usage() if $opt{h};
	$InputFileName = $opt{'f'} or usage();
	$spoof = $opt{'s'} or usage();
}
#
# Help message
#
sub usage()
{
	print STDERR << "EOF";
This program is used to replay a standard *Cisco* syslog dumpfile into the local syslog receiver (syslog-ng)
	usage: $0 [-hvfs] 
	-h        : this (help) message
	-v        : verbose output
	-f        : Filename to import (required)
	-s        : path to the spoof program (required)
	example: $0 -v -f ./syslog.sample -s ./spoof_syslog
EOF
	exit;
}
init();

# My syslog looks like this, you may need to change the regex below to match yours
#Jun 19 05:10:58 netcontrol_3750.some.domain 117475: Jun 19 05:10:57: %DUAL-5-NBRCHANGE: IP-EIGRP(0) 1024: Neighbor 10.15.213.61 (Vlan40) is down: Interface Goodbye received
my $regex = qr/([\w\.\_\-]+\.[\w\.\_\-]+|\d+\.\d+\.\d+\.\d+)(.*)(%.*?:)(.*)/;
my $datetime = strftime("%Y-%m-%d %H:%M:%S", localtime);
open(FILE, $InputFileName) || die("Can't open $InputFileName : $!\nTry $0 -h\n");
while(<FILE>) {
	if ($_ =~ m/$regex/) {
		$host = $1; 
		# As of this version of the script, the following vars aren't used:
		# $month
		# $day
		# $time
		# I would like to figure out a way to rewite the syslog packet to show these fields but can't figure out how just yet.
		# if you know how, please tell me :-)	
		$mne = $3;
		$msg = $4;
		$seq = $2; #try to get a sequence # (some messages won't have them)
		$seq =~ s/(\d+).*:/$1/; 
		if ($seq !~ /\d+/) {
			$seq = "";
		}
		print STDOUT "SEQ: $seq\n" if $opt{v};
		print STDOUT "HOST: $host\n" if $opt{v};
		print STDOUT "MNE: $mne\n" if $opt{v};
		print STDOUT "MSG: $msg\n" if $opt{v};
		@month = split(' ', $_);
		@sevs = split('-', $mne);
		$day = $month[1];
		$time = $month[2];
		switch ($month[0]) {
			case "Jan"	{ $month = "01" }
			case "Feb"	{ $month = "02" }
			case "Mar"	{ $month = "03" }
			case "Apr"	{ $month = "04" }
			case "May"	{ $month = "05" }
			case "Jun"	{ $month = "06" }
			case "Jul"	{ $month = "07" }
			case "Aug"	{ $month = "08" }
			case "Sep"	{ $month = "09" }
			case "Oct"	{ $month = "10" }
			case "Nov"	{ $month = "11" }
			case "Dec"	{ $month = "12" }
			else		{ print STDOUT "Unable to determine Month!" }
		}
		if ($sevs[1] != /\d+/) {
			$sev = $sevs[2]; # some messages contain 2 dashes such as: %PM-SP-4-ERR_RECOVER:
		}
		switch ($sevs[1]) {
			case 0		{ $sev = 0 }
			case 1		{ $sev = 1 }
			case 2		{ $sev = 2 }
			case 3		{ $sev = 3 }
			case 4		{ $sev = 4 }
			case 5		{ $sev = 5 }
			case 6		{ $sev = 6 }
			case 7		{ $sev = 7 }
			else		{ print STDOUT "Unable to determine Severity! $sevs[1]\n$_" }
			exit;
		}
		print STDOUT "Month: $month\n" if $opt{v};
		print STDOUT "Day: $day\n" if $opt{v};
		print STDOUT "Time: $time\n" if $opt{v};
		print STDOUT "SEV: $sev\n" if $opt{v};
		print STDOUT "FULL TEXT:\n$_\n" if $opt{v};
		system("$spoof $host 127.0.0.1 \"Log_Replay[$$]: $mne $msg\" " . $sev);
		my $sleeptime = (.0001 + rand(.99));
		select( undef, undef, undef, $sleeptime ); 
	} else {
		# If something goes wrong
		print STDOUT "INVALID MESSAGE FORMAT:\n$_\n" if $opt{v};
	}
}
close (FILE);

sub logit {
	my ($priority, $message) = @_; 
	setlogsock('unix');
	# $prog is assumed to be a global.  Also log the PID
	# and to Console if there's a problem.  Use facility 'local7' since these are (presumably) Cisco messages.
	openlog("Log_Replay", 'pid,cons', 'local7');
	syslog($priority, $message);
	closelog();
	return 1;
}
