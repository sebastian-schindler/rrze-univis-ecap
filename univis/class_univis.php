<?PHP

require_once('univis_dicts.php');

if(!class_exists('UNIVIS')) {
class UNIVIS {

	/**
	* Optionen
	*
	* @var array
	* @access private
	*/
	private $optionen = NULL;


	/**
	* Enthaelt die geparsten XML Daten in Form von Arrays
	*
	* @var array
	* @access private
	*/
	private $daten = NULL;


	/**
	* UNIVIS Url
	*
	* @var string
	* @access private
	*/
	private $univis_url = "http://univis.uni-erlangen.de/prg";

	/**
	 * Constructor.
	 *
	 *
	 * @param Uebergebene argumente
	 * @param Pfad zu Conf Datei
	 * @access 	public
	 */
	function __construct($optionen) {

		$this->optionen = $optionen;

	}
        /*
        public function test() {
            if (!empty($this->optionen)) {
		$this->daten = $this->_ladeMitarbeiterAlle();
            }
        return $this->daten;
        }
	*/
        public function ladeDaten() {

		if (!empty($this->optionen)) {
			switch($this->optionen["task"]){
				case "mitarbeiter-alle":
					$this->daten = $this->_ladeMitarbeiterAlle();
					break;

				case "mitarbeiter-orga":
                                case "mitarbeiter-telefonbuch": 
					$this->daten = $this->_ladeMitarbeiterOrga();
					break;
                                    
				case "mitarbeiter-einzeln":
                                case "mitarbeiter-content":
					$this->daten = $this->_ladeMitarbeiterEinzeln();
					break;

				case "publikationen":
					$this->daten = $this->_ladePublikationen();
					break;

				case "lehrveranstaltungen-alle":
					$this->daten = $this->_ladeLehrveranstaltungenAlle();
					break;

				case "lehrveranstaltungen-einzeln":
					$this->daten = $this->_ladeLehrveranstaltungenEinzeln();
					break;

//				case "lehrveranstaltungen-kalender":
//					$this->daten = $this->_ladeLehrveranstaltungenKalender();
//					break;

				default:
					echo "Fehler: Unbekannter Befehl\n";
					break;
			}
		}

		return $this->daten;
	}

	private function _ladeMitarbeiterAlle() {
		// Hole Daten von Univis
		$url = esc_url_raw( $this->univis_url."?search=departments&number=".$this->optionen["UnivISOrgNr"]."&show=xml" );
 
		if(!fopen($url, "r")) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnte zu UnivIS keine Verbindung aufgebaut werden.";
                    }
			// Univis Server ist nicht erreichbar
			return -1;
		}

                $handle = fopen($url, "r");
                $content = fread($handle, 100);
                if( substr( $content, 0, 5) != '<?xml' ) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider brachte Ihre Suche kein Ergebnis. Bitte überprüfen Sie die Suchparameter.";
                    }
                        // Univis Server ist nicht erreichbar
                    return -1;
                } 
                fclose($handle);                
                
		// XML Daten Parsen
		$daten = $this->xml2array($url);

                if(empty($daten)) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnte die Organisationseinheit nicht gefunden werden.";       
                    }
                    return -1;
                } elseif(!isset($daten["Person"])) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "In dieser Organisationseinheit konnten keine Mitarbeiter gefunden werden.";
                    }
                        return -1;                                    
                } else {
		if($this->optionen["Sortiere_Jobs"]) {

			$jobs = $daten["Org"][0]["jobs"][0]["job"];
                        $jobnamen = array();
                        $jobs_vergeben = array();
			$xjobs = array();

                        foreach ($jobs as $job)
			{
				$jobnamen[] = $job['description'];
			}
                        
                        // Sprachunterstützung
                        $description = $this->optionen['lang']['description'];
                        $suffix = $this->optionen['lang']['suffix'];
                        $text = $this->optionen['lang']['text'];

			if($this->optionen["Ignoriere_Jobs"]) {
                            if ( is_array($this->optionen["Ignoriere_Jobs"] ) ) {
                                if ( $suffix == '_en' ) {
                                    $xjobs = explode("|", $this->optionen['Ignoriere_Jobs'][$suffix]);
                                } else {
                                    $xjobs = explode("|", $this->optionen['Ignoriere_Jobs']['_de']);
                                }
                            } else {
                                $xjobs = explode("|", $this->optionen['Ignoriere_Jobs']);
                            }
			}

			$personen_jobs = array();
                        $daten_text = array();
  
			for ($i=0; $i < count($jobs); $i++) {
                            $text_out = '';
                            if ( $suffix != '' && isset( $jobs[$i][$description] ) ) {
                                $description_out = $jobs[$i][$description];
                            } else {
                                $description_out = $jobs[$i]['description'];
                            }
                            if ( $suffix != '' && isset( $jobs[$i][$text] ) ) {
                                $text_out = $jobs[$i][$text];
                            } elseif ( isset( $jobs[$i]['text'] ) ) {
                                $text_out = $jobs[$i]['text'];
                            }
				if( in_array($description_out, $xjobs)) {
					continue; 				
                                }
                                if (
					(!in_array($description_out, $jobs_vergeben))
					AND
                                        ((isset($jobs[$i]["pers"])
                                        AND
					(count($jobs[$i]["pers"][0]["per"]) > 0))
                                        OR
                                        (isset($text_out)))
				)
				{
					$jobs_vergeben[] = $description_out;
				}
              
                                if(isset($jobs[$i]["pers"])) {
                                    for ($j=0; $j < count($jobs[$i]["pers"][0]["per"]); $j++) {
					if(isset($personen_jobs[$jobs[$i]["pers"][0]["per"][$j]["UnivISRef"][0]["key"]])) {
						$personen_jobs[$jobs[$i]["pers"][0]["per"][$j]["UnivISRef"][0]["key"]] .= "|".$description_out;
					}else{
						$personen_jobs[$jobs[$i]["pers"][0]["per"][$j]["UnivISRef"][0]["key"]] = $description_out;
					}
                                    }
				}
                                if(!empty($text_out)) {
                                    $k = count($daten_text);
                                    $daten_text[$k]["text"] = $text_out;
                                    $daten_text[$k]["rang"] = $description_out;
                                  
                                }
			}

                        for ($k=0; $k < count($daten["Person"]); $k++) {
				$key = $daten["Person"][$k]["@attributes"]["key"];
                                $daten["Person"][$k]["semester"] = $daten["@attributes"]["semester"];

				if(isset($personen_jobs[$key])) {
					$daten["Person"][$k]["rang"] = $personen_jobs[$key];
				}
                        }
                        $daten['Person'] = array_merge($daten["Person"], $daten_text);
                        
		} 
                $daten['jobs'] = $jobs_vergeben;
		return $daten;
                }
                                                         
	}


	// Todo: wenn kein mitarbeiter gefunden alle anzeigen
	// sprungmarken optional
	// nach ueberschrift filtern
	// wenn nur eine org unit da is dann sprungmarke weglassen
	// nach alphabet sortieren

	private function _ladeMitarbeiterOrga() {
		// Hole Daten von Univis
		$url = esc_url_raw( $this->univis_url."?search=persons&department=".$this->optionen["UnivISOrgNr"]."&show=xml" );

		if(!fopen($url, "r")) {
                    if($this->optionen['errormsg'] == 1) {
                            echo "Leider konnte zu UnivIS keine Verbindung aufgebaut werden.";
                    }
                        // Univis Server ist nicht erreichbar
			return -1;
		}
                
                $handle = fopen($url, "r");
                $content = fread($handle, 100);
                if( substr( $content, 0, 5) != '<?xml' ) {
                    echo "Leider brachte Ihre Suche kein Ergebnis. Bitte überprüfen Sie die Suchparameter.";
                    // Univis Server ist nicht erreichbar
                    return -1;
                } 
                fclose($handle);                

		// XML Daten Parsen
		$daten = $this->xml2array($url);
                if(empty($daten)) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnte die Organisationseinheit nicht gefunden werden.";
                    }
                    return -1;
                } else {
                    return $daten["Person"];
                }
	}

	private function _ladeMitarbeiterEinzeln() {
            if( $this->optionen["univisid"] ) {
                $id = $this->optionen["univisid"];
                $url = esc_url_raw( $this->univis_url."?search=persons&id=".$id."&show=xml" );
            } else {
                if( $this->optionen["name"] ) {
                    $name = explode(',', $this->optionen["name"]);
                    $firstname = $name[1];
                    $lastname = $name[0];
                } else {
                    $firstname = $this->optionen["firstname"];
                    $lastname = $this->optionen["lastname"];
                }
//		//Ueberpruefe ob Vor- und Nachname gegeben sind.
//		$noetige_felder = array("firstname", "lastname");
//		foreach ($noetige_felder as $feld) {
//			if(!array_key_exists($feld, $this->optionen) || $this->optionen[$feld] == "") {
//				// Fehler: Bitte geben Sie Vor- und Nachname der gesuchten Person an
//				echo "<div class=\"hinweis_wichtig\">Bitte geben Sie Vor- und Nachname der gesuchten Person an.</div>";
//				return -1;
//			}
//
//			if(strrpos($this->optionen[$feld], "&") !== false) {
//				echo "Ung&uuml;ltige Eingabe.";
//				return -1;
//			}
//		}

		// Hole Daten von Univis
		$url = esc_url_raw( $this->univis_url."?search=persons&name=".$lastname."&firstname=".$firstname."&show=xml" );

		$url = $this->umlaute_ersetzen($url);
            }


		if(!fopen($url, "r")) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnte zu UnivIS keine Verbindung aufgebaut werden.";
                    }
			// Univis Server ist nicht erreichbar
			return -1;
		}

                $handle = fopen($url, "r");
                $content = fread($handle, 100);
                if( substr( $content, 0, 5) != '<?xml' ) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider brachte Ihre Suche kein Ergebnis. Bitte überprüfen Sie die Suchparameter.";
                    }
                    // Univis Server ist nicht erreichbar
                    return -1;
                } 
                fclose($handle);                
		$persArray = $this->xml2array($url);
                if(empty($persArray)) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnte die Person nicht gefunden werden.";
                    }
                    return -1;
                } else {
		$person = $persArray["Person"];

		if(count($persArray) == 0 ) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnte die Person nicht gefunden werden.";
                    }
			// Keine Person gefunden
			return -1;
		}

		// Falls mehrere Personen gefunden wurden, wähle die erste
		if($person) $person = $person[0];

		// Lade Publikationen und Lehrveranstaltungen falls noetig
		if (!empty($this->optionen["Personenanzeige_Publikationen"])) {
			$person["publikationen"] = $this->_ladePublikationen($person["id"]);
		}

		if (!empty($this->optionen["Personenanzeige_Lehrveranstaltungen"])) {
			$person["lehrveranstaltungen"] = $this->_ladeLehrveranstaltungenAlle($person["id"]);
		}
		return $person;
                }
	}

        // $authorid muss evtl. noch mit $univisid ersetzt werden, dann aber auch in rrze_univis.php
	private function _ladePublikationen($authorid = NULL) {
		// Hole Daten von Univis
		$url = esc_url_raw( $this->univis_url."?search=publications&show=xml" );
		if($authorid) {
			// Suche nur Publikationen von einen bestimmten Autoren
			$url .= "&authorid=" . $authorid;
		} elseif($this->optionen["UnivISOrgNr"]) {
                    //Suche Publikationen zu einer UnivISOrgNr
                    $url .= "&department=" . $this->optionen["UnivISOrgNr"];
                }

		if(!fopen($url, "r")) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnte zu UnivIS keine Verbindung aufgebaut werden.";
                    }
			// Univis Server ist nicht erreichbar
			return -1;
		}

                $handle = fopen($url, "r");
                $content = fread($handle, 100);
                if( substr( $content, 0, 5) != '<?xml' ) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider brachte Ihre Suche kein Ergebnis. Bitte überprüfen Sie die Suchparameter.";
                    }
                    // Univis Server ist nicht erreichbar
                    return -1;
                } 
                fclose($handle);
                
		$array = $this->xml2array($url);
                if(empty($array)) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnten keine Publikationen gefunden werden.";
                    }
                    return -1;
                } else {
		$publications = $array["Pub"];

		//Personen laden
		$refs = array();
		foreach ($array["Person"] as $person) {
			if($person["@attributes"]["key"]) {
				$key = $person["@attributes"]["key"];
				unset($person["@attributes"]);
				$refs[$key] = $person;
			}
		}

		//Personen informationen einfügen
		$this->univis_refs_ersetzen($refs, $publications);

		return $publications;
                }

	}


	private function _ladeLehrveranstaltungenAlle($univisid = NULL) {
		// Hole Daten von Univis
		//&sem=2012w
                $url = esc_url_raw( $this->univis_url."?search=lectures&show=xml" );
                //Auskommentiert, da das aktuelle Semester in UnivIS beliebieg zum Ende der vorlesungsfreien Zeit umgestellt wird
		//$url = esc_url_raw( $this->univis_url."?search=lectures&show=xml&sem=".$this->aktuellesSemester() );
                if( $univisid ) {
                    $url .= "&lecturerid=" . $univisid;
                } elseif( $this->optionen["univisid"] ) {
                    $url .= "&lecturerid=" . $this->optionen["univisid"];
		} elseif ( $this->optionen["name"] ) {
                    $url .= "&lecturer=" . $this->optionen["name"];
                } elseif ( $this->optionen["UnivISOrgNr"] ) {
                    $url .= "&department=" . $this->optionen["UnivISOrgNr"];
                }
                
                if( $this->optionen["type"] ) {
                    $url .= "&type=" . $this->optionen["type"];
                }
                
                if( $this->optionen["sem"] ) {
                    $url .= "&sem=" . $this->optionen["sem"];
                } 
		if(!fopen($url, "r")) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnte zu UnivIS keine Verbindung aufgebaut werden.";
                    }
			// Univis Server ist nicht erreichbar
			return -1;
		} 
                
                $handle = fopen($url, "r");
                $content = fread($handle, 100);
                if( substr( $content, 0, 5) != '<?xml' ) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider brachte Ihre Suche kein Ergebnis. Bitte überprüfen Sie die Suchparameter.";
                    }
                    // Univis Server ist nicht erreichbar
                    return -1;
                } 
                fclose($handle);
             
		$array = $this->xml2array($url);
                
                if(empty($array)) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnten keine Lehrveranstaltungen gefunden werden.";
                    }
                    return -1;
                } else {
                    $veranstaltungen = $array["Lecture"];
                    
                    // Ausblenden importierter Lehrveranstaltungen über Shortcodeparameter lv_import="0"
                    if($this->optionen["lv_import"] == 0) {
                        for ( $i=0; $i<count($veranstaltungen); $i++ ) {
                            if( isset( $veranstaltungen[$i]["import_parent_id"] ) ) {
                                array_splice($veranstaltungen, $i, 1);
                                $i = $i - 1;
                            } 
                        }
                    }
                    $univis_refs = $this->_get_univis_ref($array);

                    //Referenzinformationen einfügen
                    $this->univis_refs_ersetzen($univis_refs, $veranstaltungen);
                    return $veranstaltungen;
                }

	}

	private function _ladeLehrveranstaltungenKalender($univisid = NULL) {
		return $this->_ladeLehrveranstaltungenAlle($univisid);
	}

	private function _ladeLehrveranstaltungenEinzeln() {
		// Hole Daten von Univis
		if($this->optionen["lv_id"] == "") {
                            if($this->optionen['errormsg'] == 1) {
				// Fehler: Bitte geben Sie eine Lehrveranstaltung an
				echo "<div class=\"hinweis_wichtig\">Bitte geben Sie eine Lehrveranstaltung an.</div>";
                            }
                            return -1;
			}
                $url = esc_url_raw( $this->univis_url."?search=lectures&show=xml" );
		//Auskommentiert, da das aktuelle Semester in UnivIS beliebieg zum Ende der vorlesungsfreien Zeit umgestellt wird
                //$url = $this->univis_url."?search=lectures&show=xml&sem=".$this->aktuellesSemester() ;

		if(isset($this->optionen["lv_id"])) {
			$url .=  "&id=".$this->toNumber($this->optionen["lv_id"]) ;
		}
                if( isset($this->optionen["sem"]) ) {
                    $url .= "&sem=" . $this->optionen["sem"];
                } 

		if(!fopen($url, "r")) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider konnte zu UnivIS keine Verbindung aufgebaut werden.";
                    }
			// Univis Server ist nicht erreichbar
			return -1;
		} 
                
                $handle = fopen($url, "r");
                $content = fread($handle, 100);
                if( substr( $content, 0, 5) != '<?xml' ) {
                    if($this->optionen['errormsg'] == 1) {
                        echo "Leider brachte Ihre Suche kein Ergebnis. Bitte überprüfen Sie die Suchparameter.";
                    }
                    // Univis Server ist nicht erreichbar
                    return -1;
                } 
                fclose($handle);

                $array = $this->xml2array($url);
                if(empty($array)) {
                    if($this->optionen['errormsg'] == 1) {                    
                        echo "Leider brachte Ihre Suche kein Ergebnis.";
                    }
                    return -1;
                } else {
                    $veranstaltung = $array["Lecture"][0];                   

                    //Ersetze Referenzen
                    $univis_refs = $this->_get_univis_ref($array);
                    $this->univis_refs_ersetzen($univis_refs, $veranstaltung);

                    return $veranstaltung;
                }
	}


	///////////////////////////////////////////////////////////////
	/////		Hilfsmethoden
	///////////////////////////////////////////////////////////////

        // XML Parser
        public function xml2array($url) {
            $sxi = new SimpleXmlIterator($url, null, true);
            return $this->sxi2array($sxi);
        }

        private function sxi2array($sxi) {
            $a = array();

            for ($sxi->rewind(); $sxi->valid(); $sxi->next()) {

                if (!array_key_exists($sxi->key(), $a)) {
                    $a[$sxi->key()] = array();
                }

                if ($sxi->hasChildren()) {
                    if (empty($a[$sxi->key()])) {
                        $a[$sxi->key()] = array();
                    }
                    $a[$sxi->key()][] = $this->sxi2array($sxi->current());
                } elseif (in_array($sxi->key(), array('orgunit', 'orgunit_en', 'location'))) {
                    $a[$sxi->key()][] = strval($sxi->current());
                } else {
                    $a[$sxi->key()] = strval($sxi->current());

                    // Fuege die UnivisRef Informationen ein
                    if ($sxi->UnivISRef) {
                        if (empty($a[$sxi->key()])) {
                            $a[$sxi->key()] = array();
                        }
                        $attributes = (array) $sxi->UnivISRef->attributes();
                        $a[$sxi->key()][] = $attributes["@attributes"];
                    }
                }

                if ($sxi->attributes()) {
                    $attributes = (array) $sxi->attributes();
                    $a["@attributes"] = $attributes["@attributes"];
                }
            }
            return $a;
        }

	private function umlaute_ersetzen($text){
		$such_array  = array ('ä', 'ö', 'ü', 'ß');
		$ersetzen_array = array ('ae', 'oe', 'ue', 'ss');
		$neuer_text  = str_replace($such_array, $ersetzen_array, $text);
		return $neuer_text;
	}

	// Ersetzt die Referenzen von Univis durch den jeweilig dazugehoerigen Datensatz.
	private function univis_refs_ersetzen($refs, &$arr) {
		$search_results = array();
		$search_key = "UnivISRef";

		foreach ($arr as &$child) {
			if(is_array($child) && array_key_exists($search_key, $child)) {    
                            if( array_key_exists( $child[$search_key][0]["key"], $refs ) )
				$child = $refs[$child[$search_key][0]["key"]];             
			}
			if(is_array($child)) {
				$this->univis_refs_ersetzen($refs, $child);
			}
		}
		return $search_results;
	}

	private function _get_univis_ref($arr) {
		$univis_refs = array();

		$dict = array("Room", "Person", "Title", "Lecture");
		foreach ($dict as $type) {
                    if( !isset( $arr[$type] ) ) {
                        $arr[$type] = array();
                    }
			$univis_refs = array_merge($univis_refs, $arr[$type]);
		}

		$refs = array();
		foreach ($univis_refs as $ref) {
			if($ref["@attributes"]["key"]) {
				$key = $ref["@attributes"]["key"];
				unset($ref["@attributes"]);
				$refs[$key] = $ref;
			}
		}

		return $refs;
	}

	// Gibt aktuelles Semester zurueck:
	// 01.04 - 01.10 Sommersemester
	// 01.10 - 01.04 Wintersemester
	// Beispiel: Aktuelles Datum: 12.02.2013 -> 2012w
	private function aktuellesSemester() {
		$heute = explode(".", date("d.m"));
		$fruehling = explode(".", $this->optionen["START_SOMMERSEMESTER"]);
		$herbst = explode(".", $this->optionen["START_WINTERSEMESTER"]);

		if($heute[1] > $fruehling[1] || ($heute[1] == $fruehling[1] && $heute[0] >= $fruehling[0]) ) {
			if($heute[1] < $herbst[1] || ($heute[1] == $herbst[1] && $heute[0] <= $herbst[0]) ) {
				// Sommersemester
				return date("Y") . "s";
			}
		}
		// Wintersemester
		$jahr = $this->toNumber(date("Y"));

		//Wenn das neue Kalenderjahrangefangen hat, aber das Semester noch vom Vorjahr gilt. -> Einmal runterzaehlen
		if($heute[1] < $fruehling[1]) $jahr--;
		return $jahr . "w";
	}

	private function toNumber($data) {
		return (int)$data;
	}

}
}
