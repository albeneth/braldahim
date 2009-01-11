<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3. 
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id$
 * $Author$
 * $LastChangedDate$
 * $LastChangedRevision$
 * $LastChangedBy$
 */
class Bral_Competences_Forger extends Bral_Competences_Competence {

	function prepareCommun() {
		Zend_Loader::loadClass("Echoppe");
		Zend_Loader::loadClass("RecetteEquipement");
		Zend_Loader::loadClass("Bral_Helper_DetailEquipement");

		$id_type_courant = $this->request->get("type_equipement");

		$typeEquipementCourant = null;

		// On regarde si le hobbit est dans une de ses echopppes
		$echoppeTable = new Echoppe();
		$echoppes = $echoppeTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);

		$this->view->forgerEchoppeOk = false;
		if ($echoppes == null || count($echoppes) == 0) {
			$this->view->forgerEchoppeOk = false;
			return;
		}
		
		$idEchoppe = -1;
		foreach($echoppes as $e) {
			if ($e["id_fk_hobbit_echoppe"] == $this->view->user->id_hobbit &&
				$e["nom_systeme_metier"] == "forgeron" &&
				$e["x_echoppe"] == $this->view->user->x_hobbit &&
				$e["y_echoppe"] == $this->view->user->y_hobbit) {
				$this->view->forgerEchoppeOk = true;
				$idEchoppe = $e["id_echoppe"];

				$echoppeCourante = array(
					'id_echoppe' => $e["id_echoppe"],
					'x_echoppe' => $e["x_echoppe"],
					'y_echoppe' => $e["y_echoppe"],
					'id_metier' => $e["id_metier"],
					'quantite_planche_arriere_echoppe' => $e["quantite_planche_arriere_echoppe"],
					'quantite_fourrure_arriere_echoppe' => $e["quantite_fourrure_arriere_echoppe"],
					'quantite_cuir_arriere_echoppe' => $e["quantite_cuir_arriere_echoppe"],
				);
				break;
			}
		}

		if ($this->view->forgerEchoppeOk == false) {
			return;
		}

		Zend_Loader::loadClass("TypeEquipement");
		$typeEquipementTable = new TypeEquipement();
		$typeEquipementsRowset = $typeEquipementTable->findByIdMetier($this->getIdMetier());
		$tabTypeEquipement = null;
		foreach($typeEquipementsRowset as $t) {
			$selected = "";
			if ($id_type_courant == $t["id_type_equipement"]) {
				$selected = "selected";
			}
			$t = array(
				'id_type_equipement' => $t["id_type_equipement"],
				'nom_type_equipement' => $t["nom_type_equipement"],
				'nb_runes_max_type_equipement' => $t["nb_runes_max_type_equipement"],
				'selected' => $selected
			);
			if ($id_type_courant == $t["id_type_equipement"]) {
				$typeEquipementCourant = $t;
			}
			$tabTypeEquipement[] = $t;
		}

		$tabNiveaux = null;
		$tabRunes = null;
		$tabCout = null;
		$this->view->ressourcesOk = true;
		$this->view->etape1 = false;
		$this->view->typeEquipementCourant = null;
		$this->view->cout = null;
		$this->view->niveaux = null;
		$this->view->runes = null;

		if (isset($typeEquipementCourant)) {
			Zend_Loader::loadClass("RecetteCout");
			Zend_Loader::loadClass("RecetteCoutMinerai");
			Zend_Loader::loadClass("EchoppeMinerai");

			$this->view->etape1 = true;

			for ($i = 0; $i <= $this->view->user->niveau_hobbit / 10 ; $i++) {
				$tabNiveaux[$i] = array('niveauText' => 'Niveau '.$i, 'ressourcesOk' => true, 'a_afficher' => false);
			}

			$recetteEquipementTable = new RecetteEquipement();
			$recetteEquipement = $recetteEquipementTable->findByIdTypeEquipement($typeEquipementCourant["id_type_equipement"]);
			
			foreach($recetteEquipement as $r) {
				$tabCaracs[$r["niveau_recette_equipement"]][$r["id_fk_type_qualite_recette_equipement"]][] = array(
						'nom_qualite' => $r["nom_type_qualite"],
						'niveau' => $r["niveau_recette_equipement"], 
						'poids' => $r["niveau_recette_equipement"], 
						'armure' => $r["armure_recette_equipement"], 
						'force' => $r["force_recette_equipement"], 
						'agilite' => $r["agilite_recette_equipement"], 
						'vigueur' => $r["vigueur_recette_equipement"], 
						'sagesse' => $r["sagesse_recette_equipement"], 
						'vue' => $r["vue_recette_equipement"], 
						'bm_attaque' => $r["bm_attaque_recette_equipement"], 
						'bm_degat' => $r["bm_degat_recette_equipement"], 
						'bm_defense' => $r["bm_defense_recette_equipement"], 
						'nom_emplacement' => $r["nom_type_emplacement"], 
				);
				if (array_key_exists($r["niveau_recette_equipement"], $tabNiveaux)) {
					$tabNiveaux[$r["niveau_recette_equipement"]]["a_afficher"] = true;
				}
			}
			
			$recetteCoutTable = new RecetteCout();
			$recetteCout = $recetteCoutTable->findByIdTypeEquipement($typeEquipementCourant["id_type_equipement"]);

			foreach($recetteCout as $r) {
				if ($r["niveau_recette_cout"] <= floor($this->view->user->niveau_hobbit / 10) ) {
					if ($r["cuir_recette_cout"] > 0) {
						$tabCout[$r["niveau_recette_cout"]][] = array("nom"=>"Cuir", "nom_systeme"=>"cuir", "cout" => $r["cuir_recette_cout"]);
						if ($r["cuir_recette_cout"] > $echoppeCourante["quantite_cuir_arriere_echoppe"]) {
							$tabNiveaux[$r["niveau_recette_cout"]]["ressourcesOk"] = false;
						}
					}
					if ($r["fourrure_recette_cout"] > 0) {
						$tabCout[$r["niveau_recette_cout"]][] = array("nom"=>"Fourrure", "nom_systeme"=>"fourrure", "cout" => $r["fourrure_recette_cout"]);
						if ($r["fourrure_recette_cout"] > $echoppeCourante["quantite_fourrure_arriere_echoppe"]) {
							$tabNiveaux[$r["niveau_recette_cout"]]["ressourcesOk"] = false;
						}
					}
					if ($r["planche_recette_cout"] > 0) {
						if ($r["planche_recette_cout"] > 1) {
							$nom = "Planches";
						} else {
							$nom = "Planche";
						}
						$tabCout[$r["niveau_recette_cout"]][] = array("nom"=>$nom, "nom_systeme"=>"planche", "cout" => $r["planche_recette_cout"]);
						if ($r["planche_recette_cout"] > $echoppeCourante["quantite_planche_arriere_echoppe"]) {
							$tabNiveaux[$r["niveau_recette_cout"]]["ressourcesOk"] = false;
						}
					}
				}
			}

			$recetteCoutMineraiTable = new RecetteCoutMinerai();
			$recetteCoutMinerai = $recetteCoutMineraiTable->findByIdTypeEquipement($typeEquipementCourant["id_type_equipement"]);

			$echoppeMineraiTable = new EchoppeMinerai();
			$this->echoppeMinerai = $echoppeMineraiTable->findByIdEchoppe($idEchoppe);
			
			foreach($recetteCoutMinerai as $r) {
				if (($r["quantite_recette_cout_minerai"] > 0) &&
				($r["niveau_recette_cout_minerai"] <=floor($this->view->user->niveau_hobbit / 10))) {
					$tabCout[$r["niveau_recette_cout_minerai"]][] = array(
						"nom"=>$r["nom_type_minerai"], 
						"nom_systeme"=>$r["nom_systeme_type_minerai"], 
						"id_type_minerai"=>$r["id_type_minerai"], 
						"cout" => $r["quantite_recette_cout_minerai"], 
						"unite" => "lingot"
					);
					$ressourceMinerai = false;
					foreach($this->echoppeMinerai as $m) {
						if ($m["id_fk_type_echoppe_minerai"] == $r["id_type_minerai"]) {
							if ($r["quantite_recette_cout_minerai"] <= $m["quantite_lingots_echoppe_minerai"]) {
								$ressourceMinerai = true;
							} else {
								$ressourceMinerai = false;
							}
						}
					}
					if ($ressourceMinerai == false) {
						$tabNiveaux[$r["niveau_recette_cout_minerai"]]["ressourcesOk"] = false;
					}
				}
			}

			if ($typeEquipementCourant["nb_runes_max_type_equipement"] > 0) {
				$this->view->peutRunes = true;
				for ($i = 0; $i<=$typeEquipementCourant["nb_runes_max_type_equipement"]; $i++) {
					$tabRunes[] = array('nombre' => $i);
				}
			} else {
				$this->view->peutRunes = false;
			}

			$this->view->caracs = $tabCaracs;
			$this->view->cout = $tabCout;
			$this->view->niveaux = $tabNiveaux;
			$this->view->runes = $tabRunes;

			$this->view->typeEquipementCourant = $typeEquipementCourant;
		}

		$this->view->typeEquipement = $tabTypeEquipement;
		$this->idEchoppe = $idEchoppe;
		$this->echoppeCourante = $echoppeCourante;
	}

	function prepareFormulaire() {
		if ($this->view->assezDePa == false) {
			return;
		}
	}

	function prepareResultat() {
		// Verification des Pa
		if ($this->view->assezDePa == false) {
			throw new Zend_Exception(get_class($this)." Pas assez de PA : ".$this->view->user->pa_hobbit);
		}

		// Verification forger
		if ($this->view->forgerEchoppeOk == false) {
			throw new Zend_Exception(get_class($this)." Forger Echoppe interdit ");
		}

		$idTypeEquipement = (int)$this->request->get("valeur_1");
		$niveau = (int)$this->request->get("valeur_2");
		$nbRunes = (int)$this->request->get("valeur_3");

		if ($idTypeEquipement != $this->view->typeEquipementCourant["id_type_equipement"]) {
			throw new Zend_Exception(get_class($this)." idTypeEqupement interdit A=".$idTypeEquipement. " B=".$this->view->typeEquipementCourant["id_type_equipement"]);
		}

		$niveauxOk = false;
		foreach ($this->view->niveaux as $k => $v) {
			if ($k == $niveau && $v["ressourcesOk"] === true) {
				$niveauxOk = true;
			}
		}
		if ($niveauxOk == false) {
			throw new Zend_Exception(get_class($this)." Niveau interdit ");
		}

		$runesOk = false;
		if ($this->view->peutRunes === true) {
			foreach ($this->view->runes as $r) {
				if ($nbRunes == $r["nombre"]) {
					$runesOk = true;
				}
			}
		} else {
			$runesOk = true;
		}
		if ($runesOk == false) {
			throw new Zend_Exception(get_class($this)." NbRunes interdit ");
		}

		// calcul des jets
		$this->calculJets();

		if ($this->view->okJet1 === true) {
			$this->calculForger($idTypeEquipement, $niveau, $nbRunes);
		} else {
			$this->calculRateForger($niveau);
		}

		$this->calculPx();
		$this->calculBalanceFaim();
		$this->majHobbit();
	}
	
	private function calculRateForger($niveau) {
		$this->majCout($niveau, false);	
	}
	
	private function calculForger($idTypeEquipement, $niveau, $nbRunes) {
		$this->view->effetRune = false;
		
		$maitrise = $this->hobbit_competence["pourcentage_hcomp"] / 100;
		
		if (Bral_Util_Commun::isRunePortee($this->view->user->id_hobbit, "GE")) { // s'il possede une rune AP
			$this->view->effetRune = true;
			$chance_a = 0;
			$chance_b = 100-(10 * $maitrise);
			$chance_c = 10 * $maitrise;
		} else {
			$chance_a = 11.1-11 * $maitrise;
			$chance_b = 100-(11.1-11 * $maitrise)-(10 * $maitrise);
			$chance_c = 10 * $maitrise;
		}

		/*
		 * Seul le meilleur des n jets est gardé. n=(BM FOR/2)+1.
		 */
		$n = (($this->view->user->force_bm_hobbit + $this->view->user->force_bbdf_hobbit) / 2 ) + 1;
		
		if ($n < 1) $n = 1;
		
		$tirage = 0;
		
		for ($i = 1; $i <= $n; $i ++) {
			$tirageTemp = Bral_Util_De::get_1d100();
			if ($tirageTemp > $tirage) {
				$tirage = $tirageTemp;
			}
		}
		
		$qualite = -1;
		if ($tirage > 0 && $tirage <= $chance_a) {
			$qualite = 1;
			$this->view->qualite = "m&eacute;diocre";
		} elseif ($tirage > $chance_a && $tirage <= $chance_b) {
			$qualite = 2;
			$this->view->qualite = "standard";
		} elseif ($tirage > $chance_b && $tirage <= 100) {
			$qualite = 3;
			$this->view->qualite = "bonne";
		}
		$this->view->niveau = $niveau;
		$this->view->nbRunes = $nbRunes;
		$this->view->niveauQualite = $qualite;
		
		Zend_Loader::loadClass("RecetteEquipement");
		$recetteEquipementTable = new RecetteEquipement();
		$recetteEquipement = $recetteEquipementTable->findByIdTypeAndNiveauAndQualite($idTypeEquipement, $niveau, $qualite);

		if (count($recetteEquipement) > 0) {
			$echoppeMineraiTable = new EchoppeMinerai();

			$this->majCout($niveau, true);

			foreach($recetteEquipement as $r) {
				$id_fk_recette_equipement = $r["id_recette_equipement"];
				break;
			}

			Zend_Loader::loadClass("EchoppeEquipement");
			$echoppeEquipementTable = new EchoppeEquipement();
			$data = array(
				'id_fk_echoppe_echoppe_equipement' => $this->idEchoppe,
				'id_fk_recette_echoppe_equipement' => $id_fk_recette_equipement,
				'nb_runes_echoppe_equipement' => $nbRunes,
				'type_vente_echoppe_equipement' => 'aucune',
			);
			$echoppeEquipementTable->insert($data);
		} else {
			throw new Zend_Exception(get_class($this)." Recette inconnue: id=".$idTypeEquipement." n=".$niveau. " q=".$qualite);
		}
	}
	
	private function majCout($niveau, $estReussi) {
		
		if ($estReussi) {
			$coef = 1;	
		} else {
			$coef = 2;
		}
		
		$echoppeMineraiTable = new EchoppeMinerai();

		foreach($this->view->cout[$niveau] as $c) {
			switch ($c["nom_systeme"]) {
				case "cuir" :
					$this->echoppeCourante["quantite_cuir_arriere_echoppe"] = $this->echoppeCourante["quantite_cuir_arriere_echoppe"] - intval($c["cout"] / $coef);
					if ($this->echoppeCourante["quantite_cuir_arriere_echoppe"] < 0) {
						$this->echoppeCourante["quantite_cuir_arriere_echoppe"] = 0;
					}
					break;
				case "fourrure" :
					$this->echoppeCourante["quantite_fourrure_arriere_echoppe"] = $this->echoppeCourante["quantite_fourrure_arriere_echoppe"] - intval($c["cout"] / $coef);
					if ($this->echoppeCourante["quantite_fourrure_arriere_echoppe"] < 0) {
						$this->echoppeCourante["quantite_fourrure_arriere_echoppe"] = 0;
					}
					break;
				case "planche" :
					$this->echoppeCourante["quantite_planche_arriere_echoppe"] = $this->echoppeCourante["quantite_planche_arriere_echoppe"] - intval($c["cout"] / $coef);
					if ($this->echoppeCourante["quantite_planche_arriere_echoppe"] < 0) {
						$this->echoppeCourante["quantite_planche_arriere_echoppe"] = 0;
					}
					break;
				default :
					if (!isset($c["id_type_minerai"])) {
						throw new Zend_Exception(get_class($this)." Minerai inconnu ".$c["nom_systeme"]);
					}
					foreach($this->echoppeMinerai as $m) {
						if ($m["id_fk_type_echoppe_minerai"] == $c["id_type_minerai"]) {
							$quantite = $m["quantite_lingots_echoppe_minerai"] - intval($c["cout"] / $coef);
							if ($quantite < 0) {
								$quantite = 0;
							}
							$data = array('quantite_lingots_echoppe_minerai' => $quantite);
							$where = 'id_fk_type_echoppe_minerai = '. $c["id_type_minerai"];
							$where .= ' AND id_fk_echoppe_echoppe_minerai='.$this->echoppeCourante["id_echoppe"];
							$echoppeMineraiTable->update($data, $where);
						}
					}
			}
		}
		
		Zend_Loader::loadClass("Echoppe");
		$echoppeTable = new Echoppe();
		$data = array(
			'quantite_cuir_arriere_echoppe' => $this->echoppeCourante["quantite_cuir_arriere_echoppe"],
			'quantite_fourrure_arriere_echoppe' => $this->echoppeCourante["quantite_fourrure_arriere_echoppe"],
			'quantite_planche_arriere_echoppe' => $this->echoppeCourante["quantite_planche_arriere_echoppe"],
		);
		$echoppeTable->update($data, 'id_echoppe = '.$this->echoppeCourante["id_echoppe"]);
	}

	// Gain : [(nivP+1)/(nivH+1)+1+NivQ]*10 PX
	public function calculPx() {
		$this->view->nb_px_commun = 0;
		$this->view->calcul_px_generique = true;
		if ($this->view->okJet1 === true) {
			$this->view->nb_px_perso = (($this->view->niveau +1)/(floor($this->view->user->niveau_hobbit/10) + 1) + 1 + ($this->view->niveauQualite - 1) )*10;
		} else {
			$this->view->nb_px_perso = 0;
		}
		$this->view->nb_px = floor($this->view->nb_px_perso + $this->view->nb_px_commun);
	}	
	
	public function getIdEchoppeCourante() {
		if (isset($this->idEchoppe)) {
			return $this->idEchoppe;
		} else {
			return false;
		}
	}

	function getListBoxRefresh() {
		return $this->constructListBoxRefresh(array("box_competences_metiers", "box_echoppes"));
	}
}
