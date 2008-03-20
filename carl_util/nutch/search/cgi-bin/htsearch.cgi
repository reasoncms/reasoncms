#!/usr/bin/perl -w


#
# this grabs the POST args from our old htdig forms and redirects them to the new Nutch php wrapper
#

use CGI qw/:all/;

my $words = "";
my $restrict = ""; 

if(defined(param('words'))){ $words = "words=" . param('words') } else { $words = ""; }

# if we're passed a PHP array of values
if(defined(param('restrict[]'))){ 

	my @restrict_set = param('restrict[]');
	foreach my $r (@restrict_set){
		$restrict .= "&restrict[]=" . $r ;
		}
	}

# if we're passed a single value
if(defined(param('restrict'))){ $restrict = "&restrict=" . param('restrict') } 

my $URL = "/search.php?" . $words . $restrict ;

print "Status: 302 Moved\nLocation: $URL\n\n";
