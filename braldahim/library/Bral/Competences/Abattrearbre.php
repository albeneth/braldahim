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
class Bral_Competences_Abattrearbre extends Bral_Competences_Competence {

	function prepareCommun() {
		Zend_Loader::loadClass("Charrette");
		Zend_Loader::loadClass("Bosquet");
		Zend_Loader::loadClass("Bral_Util_Quete");

		$bosquetTable = new Bosquet();
		$bosquets = $bosquetTable->findByCase($this->view->user->x_braldun, $this->view->user->y_braldun, $this->view->user->z_braldun);

		$bosquet = null;
		$this->view->abattreArbreEnvironnementOk = false;
		if ($bosquets != null) {
			$bosquet = $bosquets[0];
			$this->view->abattreArbreEnvironnementOk = true;
		}

		$charretteTable = new Charrette();
		$nombre = $charretteTable->countByIdBraldun($this->view->user->id_braldun);
		$this->view->charettePleine = true;
		if ($nombre == 1) {
			$this->view->possedeCharrette = true;

			$tabPoidsCharrette = Bral_Util_Poids::calculPoidsCharrette($this->view->user->id_braldun);
			$nbPossibleDansCharretteMaximum = floor($tabPoidsCharrette["place_restante"] / Bral_Util_Poids::POIDS_RONDIN);

			if ($nbPossibleDansCharretteMaximum > 0) {
				$this->view->charettePleine = false;
			}
		} else {
			$this->view->possedeCharrette = false;
		}

		$this->view->bosquetCourant = $bosquet;
	}

	function prepareFormulaire() {
		if ($this->view->assezDePa == false) {
			return;
		}
	}

	function prepareResultat() {
		// Verification des Pa
		if ($this->view->assezDePa == false) {
			throw new Zend_Exception(get_class($this)." Pas assez de PA : ".$this->view->user->pa_braldun);
		}

		// Verification abattre arbre
		if ($this->view->abattreArbreEnvironnementOk == false || $this->view->possedeCharrette == false) {
			throw new Zend_Exception(get_class($this)." Abattre un arbre interdit ");
		}

		if ($this->view->charettePleine == true) {
			throw new Zend_Exception(get_class($this)." Charette pleine !");
		}

		// calcul des jets
		$this->calculJets();

		if ($this->view->okJet1 === true) {
			$this->calculAbattreArbre();
		}

		$this->calculPx();
		$this->calculBalanceFaim();
		$this->majBraldun();
	}

	/*
	 * Uniquement utilisable en forêt.
	 * Le Braldun abat un arbre : il ramasse n rondins (directement dans la charrette).
	 * Le nombre de rondins ramassés est fonction de la VIGUEUR :
	 * de 0 à 4 : 1D3 + BM VIG/2
	 * de 5 à 9 : 2D3 + BM VIG/2
	 * de 10 à 14 :3D3 + BM VIG/2
	 * de 15 à 19 : 4D3 + BM VIG/2
	 * etc ...
	 */
	private function calculAbattreArbre() {
		Zend_Loader::loadClass("Charrette");
		Zend_Loader::loadClass('Bral_Util_Commun');
		Zend_Loader::loadClass('StatsRecolteurs');

		$this->view->effetRune = false;

		$nb = floor($this->view->user->vigueur_base_braldun / 5) + 1;
		$this->view->nbRondins = Bral_Util_De::getLanceDeSpecifique($nb, 1, 3);

		if (Bral_Util_Commun::isRunePortee($this->view->user->id_braldun, "VA")) { // s'il possède une rune VA
			$this->view->effetRune = true;
			$this->view->nbRondins = ceil($this->view->nbRondins * 1.5);
		}

		$this->view->nbRondins  = $this->view->nbRondins  + ($this->view->user->vigueur_bm_braldun + $this->view->user->vigueur_bbdf_braldun) / 2 ;
		$this->view->nbRondins  = intval($this->view->nbRondins);

		$tabPoidsCharrette = Bral_Util_Poids::calculPoidsCharrette($this->view->user->id_braldun);
		$nbPossibleDansCharretteMaximum = floor($tabPoidsCharrette["place_restante"] / Bral_Util_Poids::POIDS_RONDIN);

		if ($this->view->nbRondins <= 0) {
			$this->view->nbRondins  = 1;
		}

		if ($this->view->nbRondins > $nbPossibleDansCharretteMaximum) {
			$this->view->nbRondins = $nbPossibleDansCharretteMaximum;
		}

		$bosquetTable = new Bosquet();
		$where = "id_bosquet=".$this->view->bosquetCourant["id_bosquet"];
		// Destruction du bosquet s'il ne reste plus rien
		if ($this->view->bosquetCourant["quantite_restante_bosquet"] - $this->view->nbRondins  <= 0) {
			$this->view->nbRondins = $this->view->bosquetCourant["quantite_restante_bosquet"];
			$bosquetTable->delete($where);
			$bosquetDetruit = true;
		} else {
			$data = array(
				'quantite_restante_bosquet' => $this->view->bosquetCourant["quantite_restante_bosquet"] - $this->view->nbRondins,
			);
			$bosquetTable->update($data, $where);
			$bosquetDetruit = false;
		}

		$charretteTable = new Charrette();
		$data = array(
			'quantite_rondin_charrette' => $this->view->nbRondins,
			'id_fk_braldun_charrette' => $this->view->user->id_braldun,
		);
		$charretteTable->updateCharrette($data);
		unset($charretteTable);

		Bral_Util_Poids::calculPoidsCharrette($this->view->user->id_braldun, true);

		$statsRecolteurs = new StatsRecolteurs();
		$moisEnCours  = mktime(0, 0, 0, date("m"), 2, date("Y"));
		$dataRecolteurs["niveau_braldun_stats_recolteurs"] = $this->view->user->niveau_braldun;
		$dataRecolteurs["id_fk_braldun_stats_recolteurs"] = $this->view->user->id_braldun;
		$dataRecolteurs["mois_stats_recolteurs"] = date("Y-m-d", $moisEnCours);
		$dataRecolteurs["nb_bois_stats_recolteurs"] = $this->view->nbRondins;
		$statsRecolteurs->insertOrUpdate($dataRecolteurs);

		$this->view->estQueteEvenement = Bral_Util_Quete::etapeCollecter($this->view->user, $this->competence["id_fk_metier_competence"]);
		$this->view->bosquetDetruit = $bosquetDetruit;
	}

	function getListBoxRefresh() {
		return $this->constructListBoxRefresh(array("box_vue", "box_competences_metiers", "box_laban", "box_charrette"));
	}
}