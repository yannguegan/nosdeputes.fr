<?php
class myTools {

  public static function displayContact($adresses, $emails = array()) {
    if (!$adresses) return;
    $telephones = array();
    foreach (unserialize($adresses) as $adresse) {
      if(trim($adresse) != '' && strpos($adresse, 'Assemblée nationale') === false) {
        $adresse = preg_replace('/Télécopie : ([-. ]?[0-9]{2}){5}/', '', $adresse);
        $adresse = preg_replace('/Téléphone : /', '', $adresse);
        preg_match('/(([-. ]?[0-9]{2}){5})/', $adresse, $tels);
        array_shift($tels);
        foreach ($tels as $tel) {
          $callto = preg_replace('/0/', '0033',preg_replace('/[^0-9]/', '', $tel), 1);
          if(strlen($callto) == 13) {
            $telephones[] = '<span class="tel"><a href="callto:'.$callto.'" title="'.trim(preg_replace('/'.$tel.'/', '', $adresse)).'">'.trim($tel).'</a>';
          }
        }
      }
    }
    if(count($telephones) != 0) {
      echo '<li><img src="/css/xneth/images/telephone.png" alt="Téléphone(s) :" /> ';
      foreach ($telephones as $key => $telephone) {
        echo $telephone;
        if($key < (count($telephones) - 1)) { echo ', '; }
      }
      echo '</li>';
    }
    if(!is_array($emails)) { $emails = array_filter(unserialize($emails)); }
    if(count($emails) != 0) {
      echo '<li><img src="/css/xneth/images/email.png" alt="Email(s) :" /> ';
      foreach ($emails as $key => $email) {
        if(trim($email) != '') {
          if(count($emails) > 1) { $num_ad = $key+1; } else { $num_ad = ''; }
          echo '<span><a class="email" href="mailto:'.str_replace('@', 'an@parl', $email).'">adresse '.$num_ad.'</a></span>';
          if($key < (count($emails) - 1)) { echo ', '; }
        }
      }
      echo '</li>';
    }
  }

  public static function get_solr_list_url($query="", $parlementaire='', $type='', $tags='', $options= '', $absolute=false) {
    if ($query)
      $query = '"'.$query.'"';
    if ($type)
      $query .= "&object_name=".$type;
    if ($parlementaire)
      $query .= "&tag=parlementaire%3D".self::solrize($parlementaire);
    if ($tags) {
      if ($parlementaire)
        $query .= ",";
      else $query .= "&tag=";
      $query .= str_replace('=', '%3D', $tags);
    }
    if ($options)
      $query .= "&".$options;
    $query .= "&sort=1";
    return url_for('@recherche_solr?query='.$query, $absolute);
  }

  public static function solrize($str) {
    $str = trim($str);
    $str = str_replace(array('à', 'â', 'À', 'Â'), 'a', $str);
    $str = str_replace(array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê'), 'e', $str);
    $str = str_replace(array('ï', 'î', 'Ï', 'Î'), 'i', $str);
    $str = str_replace(array('ô', 'ö', 'Ö', 'Ô'), 'o', $str);
    $str = str_replace(array('ù', 'ü', 'û', 'Ù', 'Û', 'Ü'), 'u', $str);
    $str = str_replace(array('ç', 'Ç'), 'c', $str);
    $str = preg_replace('/\s+/', '+', $str);
    return strtolower($str);
  }

  public static function betterUCFirst($str) {
    $str = ucfirst($str);
    $str = preg_replace('/^é/', 'É', $str);
    $str = preg_replace('/^ê/', 'Ê', $str);
    $str = preg_replace('/^â/', 'Â', $str);
    $str = preg_replace('/^à/', 'À', $str);
    return $str;
  }

  public static function convertYamlToArray($string) {
    $string = preg_replace('/^\s*\[\s*"\s*/', '', $string);
    $string = preg_replace('/\s*"\s*\]\s*$/', '', $string);
    $string = preg_replace('/",\s*"/', '","', $string);
    return explode('","', $string);
  }

  public static function getProtocol() {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "") return "https";
    return "http";
  }

  public static function url_forAPI($args) {
    sfProjectConfiguration::getActive()->loadHelpers(array('Url'));
    $url = url_for($args, 'absolute=true');
    return preg_replace('/^http:\/\//', 'https://', $url);
  }

  public static function getAnnounceLink() {
    return (sfConfig::get('app_announce_link'));
  }

  public static function getAnnounceText() {
    return (sfConfig::get('app_announce_text'));
  }

  public static function hasAnnounce() {
    return self::getAnnounceLink() && self::getAnnounceText();
  }

  public static function getLegislature() {
    return (sfConfig::get('app_legislature', 13));
  }

  public static function getPreviousHost() {
    return (sfConfig::get('app_host_previous_legislature', null));
  }

  public static function getNextHost() {
    return (sfConfig::get('app_host_next_legislature', null));
  }

  public static function getDebutLegislature() {
    $date = sfConfig::get('app_debut_legislature');
    if (!$date)
      $date = "2007-06-20";
    return $date;
  }

  public static function getFinLegislature() {
    $date = self::getDebutLegislature();
    preg_match('/^(2\d\d\d)/', $date, $m);
    return str_replace($m[1], $m[1] + 5, $date);
  }

  public static function getAnalytics() {
    return (sfConfig::get('app_analytics_id'));
  }

  public static function getPiwik() {
    return array("domain" => sfConfig::get('app_piwik_domain'),
                 "id" => sfConfig::get('app_piwik_id'));
  }

  public static function isFinLegislature() {
    return (sfConfig::get('app_fin_legislature'));
  }

  public static function isLegislatureCloturee() {
    return preg_match('/clotur/', sfConfig::get('app_fin_legislature'));
  }

  public static function isCommentairesLocked() {
    return (sfConfig::get('app_lock_commentaires'));
  }

  public static function getGroupesInfos() {
    $conf = sfConfig::get('app_groupes_infos', '');
    if (!$conf) {
      $config = sfYaml::load(dirname(__FILE__).'/../../../config/app.yml');
      $conf = $config['all']['groupes_infos'];
    }
    $gpes = self::convertYamlToArray($conf);
    $res = array();
    foreach ($gpes as $gpe)
      $res[] = explode(' / ', $gpe);
    return $res;
  }

  public static function getGroupesInfosOrder() {
    $gpes = self::getGroupesInfos();
    $map = array();
    foreach ($gpes as $gpe)
      $map[$gpe[1]] = $gpe;
    $gpes = array();
    foreach (self::convertYamlToArray(sfConfig::get('app_groupes_actuels', '')) as $gpe)
      $gpes[] = $map[$gpe];
    return $gpes;
  }

  public static function getAllGroupesOrder() {
    $groupesmap = array();
    $ct = 0;
    foreach (myTools::getGroupesInfos() as $gpe)
      $groupesmap[$gpe[1]] = $ct++;
    return $groupesmap;
  }

  public static function getGroupesColorMap() {
    $colormap = array();
    foreach (myTools::getGroupesInfos() as $gpe)
      $colormap[$gpe[1]] = $gpe[2];
    return $colormap;
  }

  static $num_mois = array(
     "01" => "janvier",
     "02" => "février",
     "03" => "mars",
     "04" => "avril",
     "05" => "mai",
     "06" => "juin",
     "07" => "juillet",
     "08" => "août",
     "09" => "septembre",
     "10" => "octobre",
     "11" => "novembre",
     "12" => "décembre");

  public static function displayDate($date) {
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $date, $match)) {
      $match[3] = preg_replace('/^0(\d)/', '\\1', $match[3]);
      if ($match[3] == '1') $match[3] .= 'er';
      return $match[3].' '.self::$num_mois[$match[2]].' '.$match[1];
    } else return $date;
  }

  public static function displayDateMoisAnnee($date) {
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $date, $match)) {
      if($match[2] != '00') {
        return self::$num_mois[$match[2]].' '.$match[1];
      } else return $date;
    } else return $date;
  }

  static $day_week = array(
     "0" => "Dimanche",
     "1" => "Lundi",
     "2" => "Mardi",
     "3" => "Mercredi",
     "4" => "Jeudi",
     "5" => "Vendredi",
     "6" => "Samedi");

  public static function displayDateSemaine($date) {
    $day = self::$day_week[date('w', strtotime($date))];
    return $day.' '.self::displayDate($date);
  }

  public static function displayShortDate($d) {
    $d = preg_replace ('/\-/', '', $d);
    $date = substr($d,6,2)."/";        // jour
    $date = $date.substr($d,4,2)."/";  // mois
    $date = $date.substr($d,0,4);      // année
    return $date;
  }

  public static function displayVeryShortDate($d) {
    $d = preg_replace ('/\-/', '', $d);
    $date = substr($d,6,2)."/";        // jour
    $date = $date.substr($d,4,2)."/";  // mois
    $date = $date.substr($d,2,2);      // année
    return $date;
  }

  public static function displayMoisAnnee($d) {
    $d = preg_replace ('/\-/', '', $d);
    $date = self::$num_mois[substr($d,4,2)].' ';  // mois txt
    $date = $date.substr($d,0,4);      // année num
    return $date;
  }

  public static function displayDateTime($d) {
    $date = self::displayShortDate($d)." à ";
    $date = $date.substr($d,11,5);     // heures et minutes
    return $date;
  }

  public static function getAge($dob) {
    list($year,$month,$day) = explode("-",$dob);
    $year_diff  = date("Y") - $year;
    $month_diff = date("m") - $month;
    $day_diff   = date("d") - $day;
    if (($month_diff == 0 && $day_diff < 0) || $month_diff < 0)
      $year_diff--;
    return $year_diff;
  }

  public static function getLinkDossier($urlan) {
    return link_to('Dossier sur le site de l\'Assemblée', "http://www.assemblee-nationale.fr/".self::getLegislature()."/dossiers/".$urlan.".asp");
  }

  public static function getLinkLoi($id) {
    return link_to($id, "http://recherche2.assemblee-nationale.fr/resultats-avancee.jsp?11AUTPropositions=&11AUTRap-enq=&11AUTRap-info=&11AUTRapports=&12AUTPropositions=&12AUTRap-enq=&12AUTRap-info=&12AUTRap-infoLoi=&12AUTRapports=&".self::getLegislature()."AUTComptesRendusReunions=&".self::getLegislature()."AUTComptesRendusReunionsDeleg=&".self::getLegislature()."AUTPropositions=&".self::getLegislature()."AUTRap-info=&".self::getLegislature()."AUTRap-infoLoi=&".self::getLegislature()."".self::getLegislature()."AUTRapports=&legislature=".self::getLegislature()."&legisnum=&num_init_11=&num_init_12=&num_init_13=".$id."&searchadvanced=Rechercher&searchtype=&texterecherche=&type=".self::getLegislature()."ProjetsLoi");
  }

  public static function getLiasseLoiAN($id) {
    return link_to('liasse de l\'Assemblée', "http://recherche2.assemblee-nationale.fr/amendements/resultats.jsp?typeEcran=avance&chercherDateParNumero=non&NUM_INIT=".$id."&NUM_AMEND=&AUTEUR=&DESIGNATION_ARTICLE=&DESIGNATION_ALINEA=&SORT_EN_SEANCE=&DELIBERATION=&NUM_PARTIE=&DateDebut=&DateFin=&periode=&LEGISLATURE=".self::getLegislature()."Amendements&QueryText=&Scope=TEXTEINTEGRAL&SortField=ORDRE_TEXTE&SortOrder=Asc&format=PDF&searchadvanced=Rechercher");
  }

  public static function getLiasseLoiImpr($id) {
    return link_to('liasse imprimable', "/liasses/liasse_".$id.".pdf");
  }

  public static function escape_blanks($txt) {
    $txt = preg_replace('/« /', '«&nbsp;', $txt);
    $txt = preg_replace('/ +([0»:;\?!\-%])/', '&nbsp;\\1', $txt);
    return $txt;
  }

  public static function escapeHtml($s) {
    if ($s)
        return preg_replace('/<[^>]*>/', '', $s);
    return $s;
  }

  public static function clearHtml($s, $authorized_tags = '<strong><i><b><a><em>') {
    sfProjectConfiguration::getActive()->loadHelpers(array('Url'));

    if ($authorized_tags)
      $s = strip_tags($s, $authorized_tags.'<depute>');

    //Protection des liens
    $s = preg_replace('/on[^=\s]+=[^\s>]+/i', '', $s);
    $s = preg_replace('/=[\'"]?javascript:[^\s\>]+/i', '=""', $s);
    $s = preg_replace('/<a /i', '<a rel="nofollow" ', $s);
    //Conversion des urls en liens
    $s = preg_replace('/(^|\s)(http\S+)/', ' <a rel="nofollow" href="\\2">\\2</a>', $s);
    if (preg_match_all('/(({+|\<depute\>)([^}<]+)(}+|\<\/?depute\>))/i', $s, $matches)) {
      for($i = 0 ; $i < count($matches[0]) ; $i++) {
  $parlementaire = Doctrine::getTable('Parlementaire')->similarTo($matches[3][$i]);
  $matches[1][$i] = preg_replace('/\//', '\/', $matches[1][$i]);
  if ($parlementaire) {
    $s = preg_replace('/'.$matches[1][$i].'/', '<a href="'.url_for('@parlementaire?slug='.$parlementaire->slug).'"><img src="'.url_for('@photo_parlementaire?slug='.$parlementaire->slug).'/20" height=20/>'.$parlementaire->nom.'</a>', $s);
  }else{
    $s = preg_replace('/'.$matches[1][$i].'/', '<b class="erreur" style="color:red">'.$matches[2][$i].'</b>', $s);
  }
      }
    }
    $s = '<p>'.$s.'</p>';
    $s = preg_replace('/\n/', '</p><p>', $s);
    return $s;
  }

  public static function echo_synthese_groupe($list, $bulles, $class, $ktop, $cpt) {
    foreach ($list as $gpe => $t) {
      $cpt++;
      echo '<tr'.($cpt %2 ? ' class="tr_odd"' : '').'>';
      echo '<td id="'.$gpe.'" class="jstitle c_'.strtolower($gpe).' '.$class['parl'].'" title="'.$t[0]['nom'];
      if (isset($t[0]['desc'])) {
        echo ' -- '.$t[0]['desc'].'"><a href="'.url_for('@list_parlementaires_groupe?acro='.$gpe).'">'.$gpe.' : '.$t[0]['nb'].' députés</a>';
      } else {
        echo '">'.$t[0]['nom']." : ".$t[0]['nb'];
      }
      echo '</td>';
      for($i = 1 ; $i < count($t) ; $i++) {
        $t[$i] = round($t[$i]/$t[0]['nb']);
        echo '<td title="'.$t[$i].' '.($t[$i] < 2 ? preg_replace('/s (.*-- )/', ' \\1', preg_replace('/s (.*-- )/', ' \\1', $bulles[$i])) : $bulles[$i]).'" class="jstitle '.$class[$ktop[$i]].'">';
        if (preg_match('/\./', $t[$i]))
          printf('%02d', $t[$i]);
        else echo $t[$i];
        echo '</td>';
      }
      echo '</tr>';
    }
    return $cpt;
  }

  public static function array2hash($array, $hashname) {
    if (!$array)
      return array();
    $hash = array();
    if (!isset($array[0])) {
      if (isset($array->fonction))
        return array("organisme" => $array->getNom(), "fonction" => $array->fonction);
      else return $array;
    }
    foreach($array as $e) if ($e) {
      if (isset($e->fonction))
        $hash[] = array($hashname => array("organisme" => $e->getNom(), "fonction" => $e->fonction));
      else $hash[] = array($hashname => preg_replace('/\n/', ', ', $e));
    }
    return $hash;
  }

  public static function depile_assoc_xml($asso, $breakline, $alreadyline) {
    foreach (array_keys($asso) as $k) {
      if (!$alreadyline && $k == $breakline) {
#        echo "\n";
        $alreadyline = 1;
      }
      echo "<$k>";
      echo self::depile_xml($asso[$k], $breakline, $alreadyline);
      echo "</$k>";
      if ($k == $breakline) {
        echo "\n";
      }
    }
  }

  public static function depile_xml($res, $breakline, $alreadyline = 0) {
    if (is_array($res)) {
      if (!isset($res[0])) {
        self::depile_assoc_xml($res, $breakline, $alreadyline);
      }else{
        foreach($res as $r) {
  	  self::depile_xml($r, $breakline, $alreadyline);
        }
      }
    }else{
      $res = str_replace('<', '&lt;', $res);
      $res = str_replace('>', '&gt;', $res);
      $res = str_replace('&', '&amp;', $res);
      echo $res;
    }
  }

  public static function depile_assoc_csv($asso, $breakline, $multi, $alreadyline) {
    $semi = 0;
    foreach (array_keys($asso) as $k) {
      if (isset($multi[$k]) && $multi[$k]) {
        $semi = 1;
      }
      self::depile_csv($asso[$k], $breakline, $multi, $semi, $alreadyline);
      if ($k == $breakline) {
        echo "\n";
      }
    }
    return $semi;
  }

  public static function depile_csv($res, $breakline, $multi, $comma = 0, $alreadyline = 0) {
    if (is_array($res)) {
      if (isset($res['organisme']) && isset($res['fonction']))
        return self::depile_csv($res['organisme']." - ".$res['fonction'], $breakline, $multi, $comma, $alreadyline);
      if (!isset($res[0])) {
        if (array_keys($res))
  	return self::depile_assoc_csv($res, $breakline, $multi, $alreadyline);
        echo ";";
        return;
      }
      foreach($res as $r)
        $semi = self::depile_csv($r, $breakline, $multi, 0, $alreadyline);
      if ($semi)
        echo ';';
    }else{
      if ($comma)
        $res = preg_replace('/[,;]/', '', $res);
      $string = preg_match('/[,;"]/', $res);
      if ($string) {
        $res = preg_replace('/"/', '\"', $res);
        echo '"';
      }
      echo $res;
      if ($string)
        echo '"';
      if ($comma)
        echo '|';
      else echo ';';
    }
  }

  public static function templatize($action, $request, $filename) {
	self::headerize($action, $request, $filename);
	$action->setTemplate($request->getParameter('format'), 'api');
  }

  public static function headerize($action, $request, $filename) {
    $action->setLayout(false);
    switch($request->getParameter('format')) {
      case 'json':
        if (!$request->getParameter('textplain')) {
          $action->getResponse()->setContentType('text/plain; charset=utf-8');
          $action->getResponse()->setHttpHeader('content-disposition', 'attachment; filename="'.$filename.'.json"');
        }
        break;
      case 'xml':
        if (!$request->getParameter('textplain')) {
          $action->getResponse()->setContentType('text/xml; charset=utf-8');
          //    $action->getResponse()->setHttpHeader('content-disposition', 'attachment; filename="'.$filename.'.xml"');
        }
        break;
      case 'csv':
        if (!$request->getParameter('textplain')) {
          $action->getResponse()->setContentType('application/csv; charset=utf-8');
          $action->getResponse()->setHttpHeader('content-disposition', 'attachment; filename="'.$filename.'.csv"');
        }
        break;
    default:
      $action->forward404();
    }
    if ($request->getParameter('textplain')) {
      $action->getResponse()->setContentType('text/plain; charset=utf-8');
    }
  }
}
