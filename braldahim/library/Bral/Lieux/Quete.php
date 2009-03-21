<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3.
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: $
 * $Author: $
 * $LastChangedDate: $
 * $LastChangedRevision: $
 * $LastChangedBy: $
 */
class Bral_Lieux_Quete extends Bral_Lieux_Lieu {

	private $_utilisationPossible = false;
	private $_coutCastars = null;

	function prepareCommun() {
		Zend_Loader::loadClass("Quete");
		$queteTable = new Quete();

		$quete = $queteTable->findByIdHobbitAndIdLieu($this->view->user->id_hobbit, $this->view->idLieu);
		if ($quete != null || count($quete) > 0) {
			$this->view->queteObtenue = true;
		} else {
			$this->view->queteObtenue = false;
		}


		$this->_coutCastars = $this->calculCoutCastars();
		$this->_utilisationPossible = (($this->view->user->castars_hobbit -  $this->_coutCastars) >= 0);
	}

	function prepareFormulaire() {
		$this->view->utilisationPossible = $this->_utilisationPossible;
		$this->view->coutCastars = $this->_coutCastars;
	}

	function prepareResultat() {

		// verification qu'il y a assez de castars
		if ($this->view->queteObtenue === true) {
			throw new Zend_Exception(get_class($this)." Quete impossible : id:".$this->view->user->id_hobbit." l:".$this->view->idLieu);
		}

		// verification qu'il y a assez de castars
		if ($this->_utilisationPossible == false) {
			throw new Zend_Exception(get_class($this)." Achat impossible : castars:".$this->view->user->castars_hobbit." cout:".$this->_coutCastars);
		}

		$this->calculQuete();
		$this->view->user->castars_hobbit = $this->view->user->castars_hobbit - $this->_coutCastars;
		$this->majHobbit();

		$this->view->coutCastars = $this->_coutCastars;
	}

	private function calculQuete() {
		$queteTable = new Quete();

		$data = array(
			"id_fk_lieu_quete" => $this->view->idLieu,
			"id_fk_hobbit_quete" => $this->view->user->id_hobbit,
			"date_creation_quete" => date("Y-m-d H:i:s"),		
		);
		$idQuete = $queteTable->insert($data);

		$this->calculEtapes($idQuete);
	}

	private function calculEtapes($idQuete) {
		Zend_Loader::loadClass("Etape");
		Zend_Loader::loadClass("TypeEtape");
		Zend_Loader::loadClass("HobbitsMetiers");

		$typeEtapes = $this->getTypesEtapesPossibles();

		$etapeTable = new Etape();
		$nbEtapesAFaire = Bral_Util_De::get_2d3();

		for ($i = 1; $i<= $nbEtapesAFaire; $i++) {
			$n = Bral_Util_De::get_de_specifique(0, count($typeEtapes) - 1);
			//			$dataEtape = $this->prepareEtape($i, $idQuete, $typeEtapes[$n]);

			$dataEtape = $this->prepareEtape($i, $idQuete, $typeEtapes[0]);
			$etapes[] = $dataEtape;
			$etapeTable->insert($dataEtape);
		}

		$this->view->nbEtapesAFaire = $nbEtapesAFaire;
		$this->view->etapes = $etapes;
	}

	private function getTypesEtapesPossibles() {
		$hobbitsMetiersTable = new HobbitsMetiers();
		$hobbitsMetierRowset = $hobbitsMetiersTable->findMetiersByHobbitId($this->view->user->id_hobbit);

		$idMetiers = array();
		foreach($hobbitsMetierRowset as $m) {
			$idMetiers[] = $m["id_metier"];
		}

		$typeEtapeTable = new TypeEtape();
		$typeEtapes = $typeEtapeTable->fetchAllSansMetier();
		$typeEtapesMetier = $typeEtapeTable->fetchAllAvecIdsMetier($idMetiers);

		if ($typeEtapesMetier != null) {
			foreach($typeEtapesMetier as $e) {
				array_push($typeEtapes, $e);
			}
		}
		return $typeEtapes;
	}

	private function prepareEtape($ordre, $idQuete, $typeEtape) {

		$dataTypeEtape = $this->pepareParamTypeEtape($typeEtape);

		if ($ordre == 1) {
			$dateDebutEtape = date("Y-m-d H:i:s");
		} else {
			$dateDebutEtape = null;
		}

		$data = array(
			"id_fk_quete_etape" => $idQuete,
			"id_fk_type_etape" => $typeEtape["id_type_etape"],
			"id_fk_hobbit_etape" => $this->view->user->id_hobbit, // denormalisation
			"libelle_etape" => $dataTypeEtape["libelle_etape"],
			"date_debut_etape" => $dateDebutEtape,
			"param_1_etape" => $dataTypeEtape["param1"],
			"param_2_etape" => $dataTypeEtape["param2"],
			"param_3_etape" => $dataTypeEtape["param3"],
			"param_4_etape" => $dataTypeEtape["param4"],
			"param_5_etape" => $dataTypeEtape["param5"],
			"ordre_etape" => $ordre,
		);

		return $data;
	}

	private function pepareParamTypeEtape($typeEtape) {
		switch($typeEtape["nom_systeme_type_etape"]) {
			case "tuer":
				return $this->pepareParamTypeEtapeTuer();
				break;
			case "manger":
				return $this->pepareParamTypeEtapeManger();
				break;
			case "fumer":
				return $this->pepareParamTypeEtapeFumer();
				break;
			case "posseder":
				return $this->pepareParamTypeEtapePosseder();
				break;
			case "equiper":
				return $this->pepareParamTypeEtapeEquiper();
				break;
			case "construire":
				return $this->pepareParamTypeEtapeConstruire();
				break;
			case "fabriquer":
				return $this->pepareParamTypeEtapeFabriquer();
				break;
			case "collecter":
				return $this->pepareParamTypeEtapeCollecter();
				break;
			default:
				throw new Zend_Exception(get_class($this)." nom_systeme_type_etape invalide:".$typeEtape["nom_systeme_type_etape"]);
				break;
		}
	}

	private function initDataTypeEtape() {
		$dataTypeEtape = array (
			"param1" => null,
			"param2" => null,
			"param3" => null,
			"param4" => null,
			"param5" => null,
			"libelle_etape" => "",
			"libelle_etape_fin" => "",
		);
		return $dataTypeEtape;
	}
	private function pepareParamTypeEtapeTuer() {
		$dataTypeEtape = $this->initDataTypeEtape();
		
		$this->pepareParamTypeEtapeTuerParam1et2($dataTypeEtape);
		$this->pepareParamTypeEtapeTuerParam3et4($dataTypeEtape);

		$dataTypeEtape["libelle_etape"] = $dataTypeEtape["libelle_etape"].$dataTypeEtape["libelle_etape_fin"].".";
		return $dataTypeEtape;
	}

	private function pepareParamTypeEtapeTuerParam1et2(&$dataTypeEtape) {
		$dataTypeEtape["param1"] = Bral_Util_De::get_1d3();

		if ($this->view->config->game->quete->etape->tuer->param1->nombre == $dataTypeEtape["param1"]) {
			$dataTypeEtape["param2"] = Bral_Util_De::get_2d10();
			$dataTypeEtape["libelle_etape"] = "Vous devez tuer ".$dataTypeEtape["param2"]. " monstres";
		} else if ($this->view->config->game->quete->etape->tuer->param1->jour == $dataTypeEtape["param1"]) {
			$dataTypeEtape["param2"] = Bral_Util_De::get_1D7() - 1;
			$dataTypeEtape["libelle_etape"] = "Vous devez tuer 1 monstre";
			$dataTypeEtape["libelle_etape_fin"] = ", un ".Bral_Helper_Calendrier::getJourSemaine($dataTypeEtape["param2"]);
		} else if ($this->view->config->game->quete->etape->tuer->param1->etat == $dataTypeEtape["param1"]) {
			$dataTypeEtape["param2"] = Bral_Util_De::get_1D2();
			if ($this->view->config->game->quete->etape->tuer->param2->etat->affame == $dataTypeEtape["param2"]) {
				$dataTypeEtape["libelle_etape"] = "En étant affamé, vous devez tuer 1 monstre";
			} else {
				$dataTypeEtape["libelle_etape"] = "En étant repu, vous devez tuer 1 monstre";
			}
		} else {
			throw new Zend_Exception(get_class($this)."::pepareParamTypeEtapeTuerParam1et2 invalide:".$dataTypeEtape["param1"]);
		}
	}

	private function pepareParamTypeEtapeTuerParam3et4(&$dataTypeEtape) {
		$dataTypeEtape["param3"] = Bral_Util_De::get_1d3();

		if ($this->view->config->game->quete->etape->tuer->param3->taille == $dataTypeEtape["param3"]) {
			Zend_Loader::loadClass("TailleMonstre");
			$tailleMonstreTable = new TailleMonstre();
			$tailles = $tailleMonstreTable->fetchAll();
			$deTaille = Bral_Util_De::get_de_specifique(0, count($tailles) - 1);
			$dataTypeEtape["param4"] = $tailles[$deTaille]["id_taille_monstre"];
			$dataTypeEtape["libelle_etape"] .= " de taille ".$tailles[$deTaille]["nom_taille_f_monstre"];
		} else if ($this->view->config->game->quete->etape->tuer->param3->type == $dataTypeEtape["param3"]) {
			Zend_Loader::loadClass("TypeMonstre");
			$typeMonstreTable = new TypeMonstre();
			$types = $typeMonstreTable->fetchAll();
			$deType = Bral_Util_De::get_de_specifique(0, count($types) - 1);
			$dataTypeEtape["param4"] = $types[$deType]["id_type_monstre"];
			$dataTypeEtape["libelle_etape"] .= " de type ".$types[$deType]["nom_type_monstre"];
		} else if ($this->view->config->game->quete->etape->tuer->param3->niveau == $dataTypeEtape["param3"]) {
			$dataTypeEtape["param4"] = $this->view->user->niveau_hobbit + Bral_Util_De::get_1d6();
			$dataTypeEtape["libelle_etape"] .= " de niveau ".$dataTypeEtape["param4"];
		} else {
			throw new Zend_Exception(get_class($this)."::pepareParamTypeEtapeTuer param1 invalide:".$param1);
		}
	}

	private function pepareParamTypeEtapeManger() {
		//TODO
	}

	private function pepareParamTypeEtapeFumer() {
		//TODO
	}

	private function pepareParamTypeEtapePosseder() {
		//TODO
	}

	private function pepareParamTypeEtapeEquiper() {
		//TODO
	}

	private function pepareParamTypeEtapeConstruire() {
		//TODO
	}

	private function pepareParamTypeEtapeFabriquer() {
		//TODO
	}

	private function pepareParamTypeEtapeCollecter() {
		//TODO
	}

	function getListBoxRefresh() {
		return $this->constructListBoxRefresh(array("box_quetes"));
	}

	private function calculCoutCastars() {
		return 5;
	}
}