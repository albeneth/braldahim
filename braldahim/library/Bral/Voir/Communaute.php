<?php

class Bral_Voir_Communaute {

	function __construct($request, $view) {
		Zend_Loader::loadClass("Communaute");
		Zend_Loader::loadClass("RangCommunaute");

		$this->_request = $request;
		$this->view = $view;
	}

	function getNomInterne() {
		return "box_voir_communaute_inner";
	}

	function setDisplay($display) {
		$this->view->display = $display;
	}

	function render() {
		$this->view->connue = false;
		$this->view->communaute = null;
		
		$communauteTable = new Communaute();
		$communauteRowset = $communauteTable->findById($this->getValeurVerif($this->_request->get("communaute")));
		if (count($communauteRowset) == 1) {
			$this->view->communaute = $communauteRowset[0];
			$this->view->connue = true;
			
			$hobbitTable = new Hobbit();
			$nbMembresTotal = $hobbitTable->countByIdCommunaute($this->view->communaute["id_communaute"]);
			
			$this->view->nbMembresTotal = $nbMembresTotal;
		} else {
			$communaute = null;
		}
		
		if ($this->_request->get("menu") == "membres" && $this->view->connue != null) {
			return $this->renderMembres();
		} else { 
			return $this->view->render("voir/communaute.phtml");
		}
	}
	
	function renderMembres() {
		
		$this->preparePage();
		
		$this->view->tri = "";
		$this->view->filtre = "";
		$this->view->page = "";
		$this->view->precedentOk = false;
		$this->view->suivantOk = false;
		
		$hobbitTable = new Hobbit();
		$communauteRowset = $hobbitTable->findByIdCommunaute($this->view->communaute["id_communaute"], $this->_filtre, $this->_page, $this->_nbMax, $this->_ordreSql, $this->_sensOrdreSql);
		$tabMembres = null;

		foreach($communauteRowset as $m) {
			$tabMembres[] = array(
				"id_hobbit" => $m["id_hobbit"],
				"nom_hobbit" => $m["nom_hobbit"],
				"prenom_hobbit" => $m["prenom_hobbit"],
				"niveau_hobbit" => $m["niveau_hobbit"],
				"date_entree" => $m["date_entree_communaute_hobbit"],
				"id_rang_communaute" => $m["id_rang_communaute"],
				"nom_rang_communaute" => $m["nom_rang_communaute"],
				"ordre_rang_communaute" => $m["ordre_rang_communaute"],
			);
		}
		
		$rangCommunauteTable = new RangCommunaute();
		$rangsCommunauteRowset = $rangCommunauteTable->findByIdCommunaute($this->view->communaute["id_communaute"]);
		$tabRangs = null;

		foreach($rangsCommunauteRowset as $r) {
			$tabRangs[] = array(
				"id_type_rang" => $r["id_rang_communaute"],
				"nom" => $r["nom_rang_communaute"],
				"ordre_rang_communaute" => $r["ordre_rang_communaute"],
			);
		}
		
		if ($this->_page == 1) {
			$this->view->precedentOk = false;
		} else {
			$this->view->precedentOk = true;
		}

		if (count($tabMembres) == 0) {
			$this->view->suivantOk = false;
		} else {
			$this->view->suivantOk = true;
		}
		
		$this->view->page = $this->_page;
		$this->view->filtre = $this->_filtre;
		$this->view->ordre = $this->_ordre;
		$this->view->sensOrdre = $this->_sensOrdre;
		$this->view->tabRangs = $tabRangs;
		$this->view->tabMembres = $tabMembres;
		$this->view->nom_interne = $this->getNomInterne();
		return $this->view->render("voir/communaute/membres.phtml");
	}
	
	private function preparePage() {
		$this->_page = 1;
		
		if (($this->_request->get("caction") == "ask_voir_communaute") && ($this->_request->get("valeur_1") == "f")) {
			$this->_filtre = $this->getValeurVerif($this->_request->get("valeur_2"));
			$ordre = $this->getValeurVerif($this->_request->get("valeur_5"));
			$sensOrdre = $this->getValeurVerif($this->_request->get("valeur_6"));
		} else if (($this->_request->get("caction") == "ask_voir_communaute") && ($this->_request->get("valeur_1") == "p")) {
			$this->_page = $this->getValeurVerif($this->_request->get("valeur_3")) - 1;
			$this->_filtre = $this->getValeurVerif($this->_request->get("valeur_4"));
			$ordre = $this->getValeurVerif($this->_request->get("valeur_5"));
			$sensOrdre = $this->getValeurVerif($this->_request->get("valeur_6"));
		} else if (($this->_request->get("caction") == "ask_voir_communaute") && ($this->_request->get("valeur_1") == "s")) {
			$this->_page = $this->getValeurVerif($this->_request->get("valeur_3")) + 1;
			$this->_filtre = $this->getValeurVerif($this->_request->get("valeur_4"));
			$ordre = $this->getValeurVerif($this->_request->get("valeur_5"));
			$sensOrdre = $this->getValeurVerif($this->_request->get("valeur_6"));
		} else if (($this->_request->get("caction") == "ask_voir_communaute") && ($this->_request->get("valeur_1") == "o")) {
			$this->_filtre = $this->getValeurVerif($this->_request->get("valeur_2"));
			$ordre = $this->getValeurVerif($this->_request->get("valeur_5"));
			$sensOrdre = $this->getValeurVerif($this->_request->get("valeur_6")) + 1;
		} else {
			$this->_page = 1;
			$this->_filtre = -1;
			$ordre = -1;
			$sensOrdre = 1;
		}
		
		$this->_ordre = $ordre;
		$this->_sensOrdre = $sensOrdre;
		
		$this->_ordreSql = $this->getChampOrdre($ordre);
		$this->_sensOrdreSql = $this->getSensOrdre($sensOrdre);
		
		if ($this->_page < 1) {
			$this->_page = 1;
		}
		$this->_nbMax = $this->view->config->communaute->membres->nb_affiche;
	}
	
	private function getValeurVerif($val) {
		if (((int)$val.""!=$val."")) {
			throw new Zend_Exception(get_class($this)." Valeur invalide : val=".$val);
		} else {
			return (int)$val;
		}
	}
	
	private function getChampOrdre($ordre) {
		$retour = "";
		if ($ordre == 1) {
			$retour = "prenom_hobbit";
		} elseif ($ordre == 2) {
			$retour = "nom_hobbit";
		} elseif ($ordre == 3) {
			$retour = "id_hobbit";
		} elseif ($ordre == 4) {
			$retour = "niveau_hobbit";
		} elseif ($ordre == 5) {
			$retour = "date_entree_communaute_hobbit";
		} elseif ($ordre == 6) {
			$retour = "ordre_rang_communaute";
		} else {
			$retour = "prenom_hobbit";
		}
		return $retour;
	}
	
	private function getSensOrdre($sensOrdre) {
		$sens = " ASC ";
		if ($sensOrdre % 2 == 0) {
			return " DESC ";
		} else {
			return " ASC ";
		}
		return $sens;
	}
}
