#!/usr/bin/perl

use WWW::Mechanize;
use HTML::TokeParser;

@url = (
#    "http://www.assemblee-nationale.fr/13/cr-cedu/08-09/",
#    "http://www.assemblee-nationale.fr/13/cr-eco/08-09/",
#    "http://www.assemblee-nationale.fr/13/cr-cafe/08-09/",
    "http://www.assemblee-nationale.fr/13/cr-soc/08-09/",
#    "http://www.assemblee-nationale.fr/13/cr-cdef/08-09/",
#    "http://www.assemblee-nationale.fr/13/cr-dvp/08-09/",
#    "http://www.assemblee-nationale.fr/13/cr-cloi/08-09/",
#    "http://www.assemblee-nationale.fr/13/cr-cfiab/08-09/",
#    "http://www.assemblee-nationale.fr/13/cr-cafc/08-09/",
#    "http://www.assemblee-nationale.fr/13/cr-cpro/08-09/",
);

$a = WWW::Mechanize->new();

foreach $url (@url) {

    $a->get($url);
    $content = $a->content;
    $p = HTML::TokeParser->new(\$content);
    
    while ($t = $p->get_tag('a')) {
	$txt = $p->get_text('/a');
	if ($txt =~ /compte rendu/i) {
	    $a->get($t->[1]{href});
	    $file = $a->uri();
	    $file =~ s/\//_/gi;
	    $file =~ s/\#.*//;
	    print "$file\n";
	    open FILE, ">:utf8", "html/$file";
	    print FILE $a->content;
	    close FILE;
	    $a->back();
	}
    }
}
