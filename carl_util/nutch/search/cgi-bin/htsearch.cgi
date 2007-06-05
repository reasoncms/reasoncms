#!/usr/bin/perl -w


#
# this grabs the POST args from our old htdig forms and redirects them to the new Nutch php wrapper
#

use CGI qw/:all/;

my $words ;
my $restrict ; 

if(defined(param('words'))){ $words = "words=" . param('words') } else { $words = ""; }
if(defined(param('restrict'))){ $restrict = "&restrict=" . param('restrict') } else { $restrict = ""; }

my $URL = "/search.php?" . $words . $restrict ;

print "Status: 302 Moved\nLocation: $URL\n\n";

