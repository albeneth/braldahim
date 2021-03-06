<?php
/**
 * This file is part of Braldahim, under Gnu Public Licence v3.
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 * Copyright: see http://www.braldahim.com/sources
 */
class Bral_Competences_Connaissancemonstres extends Bral_Competences_Competence {

	function prepareCommun() {
		Zend_Loader::loadClass("Bral_Util_Commun");
		Zend_Loader::loadClass("Monstre");

		/*
		 * Si le Braldûn n'a pas de PA, on ne fait aucun traitement
		 */
		$this->calculNbPa();
		if ($this->view->assezDePa == false) {
			return;
		}

		$vue_nb_cases = Bral_Util_Commun::getVueBase($this->view->user->x_braldun, $this->view->user->y_braldun, $this->view->user->z_braldun) + $this->view->user->vue_bm_braldun;
		$this->view->distance = $vue_nb_cases;
		
		if ($this->view->distance < 0) {
			$this->view->distance = 0;
		}

		$x_min = $this->view->user->x_braldun - $this->view->distance;
		$x_max = $this->view->user->x_braldun + $this->view->distance;
		$y_min = $this->view->user->y_braldun - $this->view->distance;
		$y_max = $this->view->user->y_braldun + $this->view->distance;
		
		// recuperation des monstres qui sont presents sur la vue
		$tabMonstres = null;
		$monstreTable = new Monstre();
		$monstres = $monstreTable->selectVue($x_min, $y_min, $x_max, $y_max, $this->view->user->z_braldun);
		foreach($monstres as $m) {
			if ($m["genre_type_monstre"] == 'feminin') {
				$m_taille = $m["nom_taille_f_monstre"];
			} else {
				$m_taille = $m["nom_taille_m_monstre"];
			}
			$tabMonstres[] = array(
				'id_monstre' => $m["id_monstre"], 
				'nom_monstre' => $m["nom_type_monstre"], 
				'taille_monstre' => $m_taille,
				'x_monstre' => $m["x_monstre"],
				'y_monstre' => $m["y_monstre"],
				'dist_monstre' => max(abs($m["x_monstre"] - $this->view->user->x_braldun), abs($m["y_monstre"]-$this->view->user->y_braldun))
			);
		}

		$this->view->tabMonstres = $tabMonstres;
		$this->view->nMonstres = count($tabMonstres);
	}

	function prepareFormulaire() {
		if ($this->view->assezDePa == false) {
			return;
		}
		if ($this->view->nMonstres > 0) {
			foreach ($this->view->tabMonstres as $key => $row) {
				$dist[$key] = $row['dist_monstre'];
			}
			array_multisort($dist, SORT_ASC, $this->view->tabMonstres);
		}
	}

	function prepareResultat() {

		// Verification des Pa
		if ($this->view->assezDePa == false) {
			throw new Zend_Exception(get_class($this)." Pas assez de PA : ".$this->view->user->pa_braldun);
		}

		if (((int)$this->request->get("valeur_1").""!=$this->request->get("valeur_1")."")) {
			throw new Zend_Exception(get_class($this)." Monstre invalide : ".$this->request->get("valeur_1"));
		} else {
			$idMonstre = (int)$this->request->get("valeur_1");
		}

		$cdmMonstre = false;
		if (isset($this->view->tabMonstres) && count($this->view->tabMonstres) > 0) {
			foreach ($this->view->tabMonstres as $m) {
				if ($m["id_monstre"] == $idMonstre) {
					$cdmMonstre = true;
					$dist = $m["dist_monstre"];
					$this->view->distance = $dist;
					break;
				}
			}
		}
		
		$this->view->monstreVisible = true;

		if ($cdmMonstre === false) {
			$this->view->monstreVisible = false;
			$this->setNbPaSurcharge(0);
		} else {

			$this->calculJets();
			if ($this->view->okJet1 === true) {
				$this->calculCDM($idMonstre, $dist);
			}
			$this->calculPx();
			$this->calculBalanceFaim();
			$this->majBraldun();
		}
	}

	private function calculCDM($idMonstre, $dist_monstre) {
		Zend_Loader::loadClass("Bral_Util_Connaissance");
		Zend_Loader::loadClass("BraldunsCdm");
		Zend_Loader::loadClass("BraldunsCompetences");

		$monstreTable = new Monstre();
		$monstreRowset = $monstreTable->findById($idMonstre);
		$monstre = $monstreRowset;
		$tabCDM["id_monstre"] = $monstre["id_monstre"];
		$tabCDM["nom_monstre"] = $monstre["nom_type_monstre"];
		$tabCDM["id_taille_monstre"] = $monstre["id_fk_taille_monstre"];

		if ($monstre["genre_type_monstre"] == "feminin") {
			$tabCDM["taille_monstre"] = $monstre["nom_taille_f_monstre"];
			$article = "une";
		} else {
			$tabCDM["taille_monstre"] = $monstre["nom_taille_m_monstre"];
			$article = "un";
		}

		/* Calculs suivant la distance :
		 *
		 * Pour les carac : FOR, AGI, SAG, VIG, REG, ARM (ARM nat + portée dans le cas des braldun) on applique le schéma suivant :
		 * Si distance = 0 : +/- nD3-1
		 * Si distance = 1 : +/- nD3
		 * Si distance = 2 : +/- nD3+1
		 * Si distance = 3 : +/- nD3+2
		 * Si distance = 4 ou +  : +/- nD3+3
		 *
		 * Ensuite on a n qui varie suivant le level du monstre
		 * cible niveau 1-9 : n=1
		 * cible niveau 10-19 : n=2
		 * cible niveau 20-29 : n=3
		 * cible niveau 21-39 : n=4
		 * etc ..
		 *
		 * Au minimum on borne à 0 (pas de négatif).
		 *
		 * Ensuite pour la DLA, les PV actuels et max on fait un % tout simple (et on affiche en HH:MM pour la DLA) :
		 * Si distance = 0 : +/- [0;6]%
		 * Si distance = 1 : +/- [0;9]%
		 * Si distance = 2 : +/- [0;12]%
		 * Si distance = 3 : +/- [0;15]%
		 * Si distance = 4 ou +  : +/- [0;18]%
		 *
		 * Attention pour les PV : il faut que cela reste cohérent : pas de PV actuels max supérieur au PV min.
		 * Genre :
		 * PV actuel : de 25 à 36
		 * PV max : de 30 à 42
		 *
		 * et pour terminer le niveau :
		 * Si distance = 0 : +/- 1D2
		 * Si distance = 1 : +/- 1D2+1
		 * Si distance = 2 : +/- 1D2+2
		 * Si distance = 3 : +/- 1D2+3
		 * Si distance = 4 ou +  : +/- 1D2+4
		 *
		 */

		$n = intval ($monstre["niveau_monstre"]/10) + 1;

		$dist = $dist_monstre;

		if ($dist > 4) {
			$dist=4;
		}

		$tabCDM["min_niveau_monstre"] = $monstre["niveau_monstre"] - (Bral_Util_De::get_1d2() + $dist);
		$tabCDM["max_niveau_monstre"] = $monstre["niveau_monstre"] + (Bral_Util_De::get_1d2() + $dist);
		if ( $tabCDM["min_niveau_monstre"] < 0 ){
			$tabCDM["min_niveau_monstre"] = 0;
		}

		$tabCDM["min_vue_monstre"] = Bral_Util_Connaissance::calculConnaissanceMin ($monstre["vue_monstre"], $n, $dist);
		$tabCDM["max_vue_monstre"] = Bral_Util_Connaissance::calculConnaissanceMax ($monstre["vue_monstre"], $n, $dist);

		$tabCDM["min_for_monstre"] = Bral_Util_Connaissance::calculConnaissanceMin ($monstre["force_base_monstre"], $n, $dist) + $this->view->config->game->base_force;
		$tabCDM["max_for_monstre"] = Bral_Util_Connaissance::calculConnaissanceMax ($monstre["force_base_monstre"], $n, $dist) + $this->view->config->game->base_force;

		$tabCDM["min_agi_monstre"] = Bral_Util_Connaissance::calculConnaissanceMin ($monstre["agilite_base_monstre"], $n, $dist) + $this->view->config->game->base_agilite;
		$tabCDM["max_agi_monstre"] = Bral_Util_Connaissance::calculConnaissanceMax ($monstre["agilite_base_monstre"], $n, $dist) + $this->view->config->game->base_agilite;

		$tabCDM["min_sag_monstre"] = Bral_Util_Connaissance::calculConnaissanceMin ($monstre["sagesse_base_monstre"], $n, $dist) + $this->view->config->game->base_sagesse;
		$tabCDM["max_sag_monstre"] = Bral_Util_Connaissance::calculConnaissanceMax ($monstre["sagesse_base_monstre"], $n, $dist) + $this->view->config->game->base_sagesse;

		$tabCDM["min_vig_monstre"] = Bral_Util_Connaissance::calculConnaissanceMin ($monstre["vigueur_base_monstre"], $n, $dist) + $this->view->config->game->base_vigueur;
		$tabCDM["max_vig_monstre"] = Bral_Util_Connaissance::calculConnaissanceMax ($monstre["vigueur_base_monstre"], $n, $dist) + $this->view->config->game->base_vigueur;

		$tabCDM["min_reg_monstre"] = Bral_Util_Connaissance::calculConnaissanceMin ($monstre["regeneration_monstre"], $n, $dist);
		$tabCDM["max_reg_monstre"] = Bral_Util_Connaissance::calculConnaissanceMax ($monstre["regeneration_monstre"], $n, $dist);

		$tabCDM["min_arm_monstre"] = Bral_Util_Connaissance::calculConnaissanceMin ($monstre["armure_naturelle_monstre"], $n, $dist);
		$tabCDM["max_arm_monstre"] = Bral_Util_Connaissance::calculConnaissanceMax ($monstre["armure_naturelle_monstre"], $n, $dist);

		$tabCDM["min_pvmax_monstre"] = floor($monstre["pv_max_monstre"] - $monstre["pv_max_monstre"] * (Bral_Util_De::getLanceDeSpecifique(1,0,$dist*3 + 6))/100);
		$tabCDM["max_pvmax_monstre"] = ceil($monstre["pv_max_monstre"] + $monstre["pv_max_monstre"] * (Bral_Util_De::getLanceDeSpecifique(1,0,$dist*3 + 6))/100);

		$tabCDM["min_pvact_monstre"] = floor($monstre["pv_restant_monstre"] - $monstre["pv_restant_monstre"] * (Bral_Util_De::getLanceDeSpecifique(1,0,$dist*3 + 6))/100);
		$tabCDM["max_pvact_monstre"] = ceil($monstre["pv_restant_monstre"] + $monstre["pv_restant_monstre"] * (Bral_Util_De::getLanceDeSpecifique(1,0,$dist*3 + 6))/100);
		if ($tabCDM["max_pvact_monstre"] > $tabCDM["max_pvmax_monstre"]) {
			$tabCDM["max_pvact_monstre"] = $tabCDM["max_pvmax_monstre"];
		}
		if ($tabCDM["min_pvact_monstre"] > $tabCDM["min_pvmax_monstre"]) {
			$tabCDM["min_pvact_monstre"] = $tabCDM["min_pvmax_monstre"];
		}

		$duree_base_tour_minute = Bral_Util_ConvertDate::getMinuteFromHeure($monstre["duree_base_tour_monstre"]);
		$tabCDM["min_dla_monstre"] = Bral_Util_ConvertDate::getHeureFromMinute($duree_base_tour_minute - floor($duree_base_tour_minute * (Bral_Util_De::getLanceDeSpecifique(1,0,$dist*3 + 6))/100));
		$tabCDM["max_dla_monstre"] = Bral_Util_ConvertDate::getHeureFromMinute($duree_base_tour_minute + ceil($duree_base_tour_minute * (Bral_Util_De::getLanceDeSpecifique(1,0,$dist*3 + 6))/100));

		$this->view->tabCDM = $tabCDM;

		$id_type = $this->view->config->game->evenements->type->competence;
		$details = "[b".$this->view->user->id_braldun."] a réussi l'utilisation d'une compétence sur ".$article." [m".$monstre["id_monstre"]."]";
		$this->setDetailsEvenement($details, $id_type);
		$this->setDetailsEvenementCible($monstre["id_monstre"], "monstre", $monstre["niveau_monstre"]);

		$data = array(
			'id_fk_braldun_hcdm' => $this->view->user->id_braldun,
			'id_fk_monstre_hcdm'  => $idMonstre,
			'id_fk_type_monstre_hcdm'  => $monstre["id_type_monstre"],
			'id_fk_taille_monstre_hcdm'  => $monstre["id_taille_monstre"],
		);

		$braldunCdmTable = new BraldunsCdm();
		$braldunCdmTable->insertOrUpdate($data);

		Zend_Loader::loadClass("TailleMonstre");

		$pister = null;
		if ($tabCDM["id_taille_monstre"] != TailleMonstre::ID_TAILLE_BOSS) {
			$pister = $braldunCdmTable->findByIdBraldunAndIdTypeMonstre($this->view->user->id_braldun,$monstre["id_type_monstre"]);
		}
		$braldunCompetence = new BraldunsCompetences();
		$braldunPister = $braldunCompetence->findByIdBraldunAndNomSysteme($this->view->user->id_braldun,'pister');

		$this->view->pister = $pister;
		$this->view->possedePister = (count($braldunPister) == 1);
	}

	function getListBoxRefresh() {
		return $this->constructListBoxRefresh(array("box_competences_communes", "box_laban"));
	}
}
