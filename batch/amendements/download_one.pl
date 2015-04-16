#!/usr/bin/perl

use Date::Format;
use WWW::Mechanize;
use HTML::TokeParser;
$a = WWW::Mechanize->new();

$url = $htmfile = shift;
$outputdir = shift || "html";
$htmfile =~ s/^\s+//gi;
$a->get($url);
$htmfile =~ s/\//_-_/gi;
$htmfile =~ s/\#.*//;
print "  $htmfile ... ";
open FILE, ">:utf8", "$outputdir/$htmfile";
print FILE $a->content;
close FILE;
print "downloaded.\n";
$a->back();

