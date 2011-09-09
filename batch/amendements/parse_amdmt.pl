#!/usr/bin/perl

$file = shift;
use HTML::Entities;
use URI::Escape;
use Encode;

my %amdmt;

open(FILE, $file);
@string = <FILE>;
$string = "@string";
utf8::decode($string);
$string = decode_entities($string);
$string =~ s/<br\/?>//ig;
$string =~ s/\r//g;
$string =~ s/[\n\s]+/ /g;
$string =~ s/^.*<body> *//i;
$string =~ s/ *<\/body>.*$//i;
$string =~ s/<!--/\n<!--/g;
$string =~ s/ *\n *(<!--\s*fin[^>]*-->) */$1\n/ig;
$string =~ s/(<t\w+) *[^>]* colspan="(\d+)"[^>]*>/$1colspan$2>/ig;
$string =~ s/(<[^aA!][\w]*) *[^>]*>/$1>/g;
$string =~ s/<a[^>]*href=["']([^"']+)["'][^>]*>/<a href='$1'>/ig;
$string =~ s/<br\/?>//ig;
$string =~ s/colspan(\d+)/ colspan='$1'/g;
$string =~ s/> +/>/g;
$string =~ s/ *(<t[dh]>)<\/?p>/$1/ig;
$string =~ s/ *<\/?p>(<\/t[dh]>)/$1/ig;
$string =~ s/"([^"<]*)"/« $1 »/g;
$string =~ s/\n((<[^=>]+>(.|Objet)?)+\n)+/\n/g;
$string =~ s/ *\n */\n/g;
$string =~ s/"/'/g;
$string =~ s/ *, */, /g;
$string =~ s/\\/\//g;
$string =~ s/(position_amdt=\-?\d+ -->)/$1\nNEXT\n/ig;
$string =~ s/<div><table><tr><td><img><\/p><p><strong>/\n<!-- contexte=/ig;
$string =~ s/<\/strong><\/td><td><strong>[^\n]+<\/strong><\/p><p>\(/ -->\n<!-- etape_leg=/ig;
$string =~ s/\)<\/p><p>\(n\W+/ -->\n<!-- lois=/ig;
$string =~ s/\)<\/td><td><strong>n\W+/ -->\n<!-- numero=/ig;
$string =~ s/<\/strong><\/p><p>/ -->\n<!-- debut_date=/ig;
$string =~ s/<\/td><\/tr><\/table><hr><table><tr><td><\/td><td><h1>/ -->\n<!-- debut_type=/ig;
$string =~ s/<\/h1>[^\n]*\n/ -->\n/ig;
close FILE;

utf8::encode($string);
print $string;


$file =~ s/^.*(http.*)$/$1/i;
$file = uri_unescape($file);

$com = 0;
# Récupération des informations identifiantes à partir de l'url plus sure :
if ($file =~ /\/amendements(\/commissions)?\/(\d{4}-\d{4})\/(\d+)\//i) {
  $com = 1 if ($1);
  $amdmt{'session'} = $2;
  $amdmt{'loi'} = $3;
  $source = $file;
  $source =~ s/jeu_complet/Amdt_##ID##/i;
}

#print $amdmt{'session'}."/".$amdmt{'loi'}."/".$source."\n";
exit;

#  $lettre = $4;
#  if ($lettre =~ /[a-z]/i) {
#    $amdmt{'numero'} = $num.uc($lettre);
#  } else {
#    $amdmt{'numero'} = (10000*$lettre+$num);
#  }

my $presente = 0;
my $texte = 0;
my $identiques = 0;
my $num_ident = -1;

sub numero {
    $line =~ s/^.*content="//; 
    $line =~ s/".*$//;
    $line =~ s/[\(\)]//g;
    if ($line =~ /^\s*(\d+)\s+([1-9a-zA-Z].*)$/i) {
      # $amdmt{'numero'} = $1;
	$suite = $2;
	if (!$suite =~ /rect/i) {
	    $amdmt{'rectif'} = 0;
	} else {
	    if ($suite =~ /(\d+)/) {
		$amdmt{'rectif'} = $1;
	    } else {
		$amdmt{'rectif'} = 1;
	    } 
	    if ($suite =~ /bis/i) {
		$amdmt{'rectif'}++;
	    }
     	}
     } else {
	$line =~ /(\d+)/;
     #  $amdmt{'numero'} = $1;
	$amdmt{'rectif'} = 0;
     }
}

sub auteurs {
    $line =~ s/\<br\/\>/, /g;
    $line =~ s/\s*\<\/?[^\>]+\>//g;
    $line =~ s/\s*présenté\s*par\s*//g;
    $line =~ s/\s+e{1,2}t\s+/, /g;
    $line =~ s/^et\s+/, /g;
    $line =~ s/\s*EXPOSÉ SOMMAIRE\s*//g;
    $line =~ s/\s+,/,/g;
    if (!$line =~ /^$/) {
	$amdmt{'auteurs'} = $amdmt{'auteurs'}.", ".$line;
    }
}

sub texte {
    $line2 = $line;
    $line2 =~ s/\s*\<\/?[^\>]+\>//g;
    if (!($line2 =~ /\s*Adt\s+n°\s*$/)) {
    	$output = 'texte';
    	if ($texte == 2) { $output = 'expose'; }
    	if (!$amdmt{$output}) { $amdmt{$output} = "<p>".$line2."</p>"; }
    	else { $amdmt{$output} = $amdmt{$output}."<p>".$line2."</p>"; }
    }
}

sub sortseance {
    if ($line =~ /irrecevable/i) {
	$amdmt{'sort'} = 'Irrecevable';
    } elsif ($line =~ /retiré.*séance/i) {
	$amdmt{'sort'} = 'Retiré avant séance';
    } elsif ($line =~ /retiré/i) {
	$amdmt{'sort'} = 'Retiré';
    } elsif ($line =~ /non.*(soutenu|défendu)/i) {
	$amdmt{'sort'} = 'Non soutenu';
    } elsif ($line =~ /tombe/i) {
	$amdmt{'sort'} = 'Tombe';
    } elsif ($line =~ /rejet/i) {
	$amdmt{'sort'} = 'Rejeté';
    } elsif ($line =~ /adopt/i) {
	$amdmt{'sort'} = 'Adopté';
    }
}

sub identiques {
    if ($line =~ /\<div\>\s*(de)?\s*(M[\s\.Mml].*)\s*\<\/div\>/) {
	$line = $2;
	if ($amdmt{'numero'} != $num_ident) {
	    auteurs();
	}
    } else {
	$line =~ s/\s*\<\/?[^\>]+\>//g;
	if ($line =~ /^.*Adt\s+n°\s+(\d+).*\s+de\s+(.*)\s*$/) {
    	    $num = $1;
	    $line = $2;
	    if ($num_ident == -1) {
		$num_ident = $num;
		$amdmt{'serie'} = $num_ident."-";
	    }
	    if ($amdmt{'numero'} != $num) {
		auteurs();
	    }
	    if ($num > $num_ident + 1) {
		$amdmt{'serie'} = $amdmt{'serie'}.$num_ident.",".$num."-";
	    }
	    $num_ident = $num;
	} elsif ($line =~ /^\s*(\d+)\s*$/) {
    	    $num = $1;
	    if ($num_ident == -1) {
		$num_ident = $num;
		$amdmt{'serie'} = $num_ident."-";
	    }
	    if ($num > $num_ident + 1) {
		$amdmt{'serie'} = $amdmt{'serie'}.$num_ident.",".$num."-";
	    }
	    $num_ident = $num;
	} elsif ($line =~ /^\s*de\s+(M.*)\s*$/) {
    	    $line = $1;
	    if ($amdmt{'numero'} != $num) {
		auteurs();
	    }
	    $num_ident = $num;
	}
    }
}



$string =~ s/\r//g;
$string =~ s/&nbsp;/ /g;
$string =~ s/\|(\W+)\|/$1/g;
foreach $line (split /\n/, $string)
{
    if ($line =~ /meta.*content=/) {
	if ($line =~ /name="DATE_BADAGE"/i) { 
	    $line =~ s/^.*content="//i; 
	    $line =~ s/".*$//;
	    if ($line =~ /(\d{1,2})\/(\d{2})\/(\d{4})/) {
		$amdmt{'date'} = $3.'-'.$2.'-'.sprintf('%02d', $1);
	    }
	} elsif ($line =~ /name="DESIGNATION_ARTICLE"/i && !$amdmt{'sujet'}) {
	    $line =~ s/^.*content="//i; 
	    $line =~ s/"\s*(name|\>).*$//;
	    $amdmt{'sujet'} = $line;
	} elsif ($line =~ /name="SORT_EN_SEANCE"/i) { 
	    $line =~ s/^.*content="//i; 
	    $line =~ s/".*$//;
	    sortseance();
	} elsif ($line =~ /name="NUM_AMENDG"/i) { 
	    numero();
	}
    } elsif ($line =~ /date_amend.(\d{1,2}) avril (\d{4})\D/i && !$amdmt{'date'}) {
           $amdmt{'date'} = $2.'-04-'.sprintf('%02d', $1);
    } elsif ($line =~ /class="amddispotitre"/i && !$amdmt{'sujet'}) {
            $line =~ s/<[^>]+>//g;
            $amdmt{'sujet'} = $line;
    } elsif ($line =~ /class="presente"/i) {
	if ($presente == 0) {
	    $presente = 1;
	} elsif ($texte >= 1 && $line =~ /font-style: italic/i) {
	    texte();
	}
    } elsif ($presente == 1 && $line =~ /class="tirets"/i) {
	$presente = 2;
    }
    if ($line =~ /(NOEXTRACT|EXPOSE)/i) {
	if (!$amdmt{'numero'} && ($line =~ /class="numamendement"/i || $line =~ /class="titreamend".*num_partie/i)) {
	    if ($line =~ /\<num_amend\>\s*(.*)\s*\<\/num_amend\>/i) { 
	    	$line = $1;
	    	numero();
	    } elsif ($line =~ /\<\/num_partie\>\s*-\s*(.*)\<\/p\>/i) {
		$line = $1;
		numero();
	    }
        } elsif (!$amdmt{'serie'} && ($line =~ /class="numamendement".*à\s+(\d+)\W/i)) {
          $num_ident = $1;
          $amdmt{'serie'} = ($amdmt{'numero'}+1).'-';
	} elsif ($line =~ /class="presente"/i) {
	    if ($line =~ /amendement/) {
		$line =~ /(\d+)/;
		$amdmt{'parent'} = $1;
	    } elsif ($presente == 1) {
		auteurs();
	    } else {
		texte();
	    }
	} elsif ($line =~ /class="amddispotitre"/i) {
	    $texte = 1;
	    if ($line =~ /amendement.*[\s°](\d+)[\s\<]/i) {
		$amdmt{'parent'} = $1;
	    }
	} elsif ($line =~ /class="amddispotexte"/i) {
	    texte();
	} elsif ($line =~ /class="amdexpotitre"/i) {
	    if ($amdmt{'texte'} || !$line =~ /article/i) {
		$texte = 2;
	    }
	} elsif ($line =~ /class="amdexpotexte"/i) {
	    texte();
	} elsif ($line =~ /amendements\s*identiques/i) {
	    $identiques = 1;
	} elsif ($line =~ /\<div.*\>.*M[\.Mml]/ && !($line =~ /EXPOSE SOMMAIRE/i)) {
	    if ($identiques == 1) {
		identiques();
	    } elsif ($presente == 1) {	
		auteurs();
	    }
	} elsif ($identiques == 1 && $line =~ /\<div\>.*(\d+).*\<\/div\>/) {
		identiques();
	} elsif ($texte >= 1 && $line =~ /\<div\>(.*)\<\/div\>/) {
	    $line = $1;
	    texte();
	}
    } elsif (!$amdt{'sort'} && $line =~ /\<div.*id="sort"/i) {
	sortseance();
    } elsif ($identiques == 1 && $line =~ /\<p style=".*text-align:.*\>.*M[\.Mml]/i) {
	identiques();
    } elsif ($line =~ /class="presente"/i) {
	if ($line =~ /amendement/) {
	    $line =~ /(\d+)/;
	    $amdmt{'parent'} = $1;
	} elsif ($texte < 1) {
	    auteurs();
	} else {
	    texte();
	}
    } elsif ($presente == 1 && $line =~ /\<p style=".*text-indent:.*\>.*M[\.Mml]/i) { 
	auteurs();
    } elsif ($line =~ /\<p style=".*text-indent:/i) {
	if ($line =~ /amendement.*(irrecevable|retir)/i) {
	    if (!$amdmt{'sort'}) {
		if ($line =~ /irrecevable/i) {
		    $amdmt{'sort'} = "Irrecevable";
		} elsif ($line =~ /retir/i) {
		    $amdmt{'sort'} = "Retiré avant séance";
		}
	    }
	}
	texte();
    } elsif ($line =~ /\<p\>(.*)\<\/p\>/ && $texte > 1) {
	$line = $1;
	texte();
    }
}

if ($num_ident > 0) {
    $amdmt{'serie'} = $amdmt{'serie'}.$num_ident;
}

$amdmt{'auteurs'} =~ s/\s+Mme,\s*/ Mme /g;
$amdmt{'auteurs'} =~ s/([a-z])\s+(M[\.Mml])/\1, \2/g;
$amdmt{'auteurs'} =~ s/,\s*M[\s\.mle]+\s*,/,/g;
$amdmt{'auteurs'} =~ s/\s*[,]?\s*les\s+[cC]ommissaires.*$//g;
$amdmt{'auteurs'} =~ s/\s*[,]?\s*[rR]apporteur[\s,a-zéèêà\-']*M(.*)/, M\1/g;
$amdmt{'auteurs'} =~ s/\s*[,]?\s*[rR]apporteur[\s,a-zéèêà\-']*//g;
$amdmt{'auteurs'} =~ s/(,\s*,|,+)/,/g;
$amdmt{'auteurs'} =~ s/,+/,/g;
$amdmt{'auteurs'} =~ s/^\s*,\s*//g;
$amdmt{'auteurs'} =~ s/\s*,\s*$//g;
$amdmt{'auteurs'} =~ s/ et(\W)/\1/g;
$amdmt{'auteurs'} =~ s/([^,\s])\s*(les\s*membres.*groupe.*)$/\1, \2/i;

print '{"source": "'.$source.'", "legislature": "'.$amdmt{'legislature'}.'", "loi": "'.$amdmt{'loi'}.'", "numero": "'.$amdmt{'numero'}.'", "serie": "'.$amdmt{'serie'}.'", "rectif": "'.$amdmt{'rectif'}.'", "parent": "'.$amdmt{'parent'}.'", "date": "'.$amdmt{'date'}.'", "auteurs": "'.$amdmt{'auteurs'}.'", "sort": "'.$amdmt{'sort'}.'", "sujet": "'.$amdmt{'sujet'}.'", "texte": "'.$amdmt{'texte'}.'", "expose": "'.$amdmt{'expose'}.'" } '."\n";