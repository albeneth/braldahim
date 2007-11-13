<?php

class Bral_Competences_Cueillir extends Bral_Competences_Competence {

	private $_tabPlantes = null;
	function prepareCommun() {
		Zend_Loader::loadClass('Plante');
		Zend_Loader::loadClass('TypePartieplante');
		$tabPlantes = null;
		$this->view->planteOk = false;

		$typePartiePlanteTable = new TypePartieplante();
		$typePartiePlanteRowset = $typePartiePlanteTable->fetchall();
		foreach($typePartiePlanteRowset as $p) {
			$tabPartiePlante[$p->id_type_partieplante]["id"] = $p->id_type_partieplante;
			$tabPartiePlante[$p->id_type_partieplante]["nom"] = $p->nom_type_partieplante;
			$tabPartiePlante[$p->id_type_partieplante]["nom_systeme"] = $p->nom_systeme_type_partieplante;
			$tabPartiePlante[$p->id_type_partieplante]["description"] = $p->description_type_partieplante;
		}

		$planteTable = new Plante();
		$plantes = $planteTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		if (count($plantes) > 0) {
			$this->view->planteOk = true;
		}


		foreach ($plantes as $p) {
			if ($p["partie_1_plante"] > 0) $nom_partie_1 = $tabPartiePlante[$p["id_fk_partieplante1_type_plante"]]["nom"]; else $nom_partie_1="";
			if ($p["partie_2_plante"] > 0) $nom_partie_2 = $tabPartiePlante[$p["id_fk_partieplante2_type_plante"]]["nom"]; else $nom_partie_2="";
			if ($p["partie_3_plante"] > 0) $nom_partie_3 = $tabPartiePlante[$p["id_fk_partieplante3_type_plante"]]["nom"]; else $nom_partie_3="";
			if ($p["partie_4_plante"] > 0) $nom_partie_4 = $tabPartiePlante[$p["id_fk_partieplante4_type_plante"]]["nom"]; else $nom_partie_4="";
			$this->_tabPlantes[] = array("id_plante" => $p["id_plante"],
			"nom_type" => $p["nom_type_plante"],
			"categorie" => $p["categorie_type_plante"],
			"id_fk_type_plante" => $p["id_fk_type_plante"],
			"partie_1_plante" => $p["partie_1_plante"],
			"partie_2_plante" => $p["partie_2_plante"],
			"partie_3_plante" => $p["partie_3_plante"],
			"partie_4_plante" => $p["partie_4_plante"],
			"id_fk_partie_1" => $p["id_fk_partieplante1_type_plante"],
			"id_fk_partie_2" => $p["id_fk_partieplante2_type_plante"],
			"id_fk_partie_3" => $p["id_fk_partieplante3_type_plante"],
			"id_fk_partie_4" => $p["id_fk_partieplante4_type_plante"],
			"nom_partie_1" => $nom_partie_1,
			"nom_partie_2" => $nom_partie_2,
			"nom_partie_3" => $nom_partie_3,
			"nom_partie_4" => $nom_partie_4
			);
		}
		$this->view->plantes = $this->_tabPlantes;
	}

	function prepareFormulaire() {
		if ($this->view->assezDePa == false) {
			return;
		}
	}

	function prepareResultat() {
		Zend_Loader::loadClass("Bral_Util_De");
		Zend_Loader::loadClass('LabanPartieplante');
		Zend_Loader::loadClass('Hobbit');

		$idPlante = intval($this->request->get("valeur_1"));

		// Verification des Pa
		if ($this->view->assezDePa == false) {
			throw new Zend_Exception(get_class($this)." Pas assez de PA : ".$this->view->user->pa_hobbit);
		}

		// Verification de la plante
		$planteOk = false;
		if ($this->_tabPlantes != null) {
			foreach ($this->_tabPlantes as $p) {
				if ($p["id_plante"] == $idPlante) {
					$planteOk = true;
					$plante = $p;
					break;
				}
			}
		}

		if ($planteOk === false) {
			throw new Zend_Exception(get_class($this)." Plante invalide : ".$idPlante);
		}

		// calcul des jets
		$this->calculJets();
		$quantiteExtraite = $this->calculQuantiteAExtraire();

		for ($i=1; $i<=4; $i++) {
			$tab[$i]["estVide"] = true;
			$tab[$i]["quantite"] = 0;
			$tab[$i]["id_fk"] = -1;
			$cueillette[$i]["quantite"] = 0;
			$cueillette[$i]["id_fk"] = -1;
			if ($i == 1 && $plante["partie_1_plante"] > 0) {
				$tab[$i]["id_fk"] = $plante["id_fk_partie_1"];
				$tab[$i]["quantite"] = $plante["partie_1_plante"];
				$tab[$i]["estVide"] = false;
				$cueillette[$i]["id_fk"] = $plante["id_fk_partie_1"];
				$cueillette[$i]["nom_partie"] = $plante["nom_partie_1"];
			}
			if ($i == 2 && $plante["partie_2_plante"] > 0) {
				$tab[$i]["id_fk"] = $plante["id_fk_partie_2"];
				$tab[$i]["quantite"] = $plante["partie_2_plante"];
				$tab[$i]["estVide"] = false;
				$cueillette[$i]["id_fk"] = $plante["id_fk_partie_1"];
				$cueillette[$i]["nom_partie"] = $plante["nom_partie_2"];
			}
			if ($i == 3 && $plante["partie_3_plante"] > 0) {
				$tab[$i]["id_fk"] = $plante["id_fk_partie_3"];
				$tab[$i]["quantite"] = $plante["partie_3_plante"];
				$tab[$i]["estVide"] = false;
				$cueillette[$i]["id_fk"] = $plante["id_fk_partie_1"];
				$cueillette[$i]["nom_partie"] = $plante["nom_partie_3"];
			}
			if ($i == 4 && $plante["partie_4_plante"] > 0) {
				$tab[$i]["id_fk"] = $plante["id_fk_partie_4"];
				$tab[$i]["quantite"] = $plante["partie_4_plante"];
				$tab[$i]["estVide"] = false;
				$cueillette[$i]["id_fk"] = $plante["id_fk_partie_1"];
				$cueillette[$i]["nom_partie"] = $plante["nom_partie_4"];
			}
		}

		$planteADetruire = false;
		for ($i=1; $i<=$quantiteExtraite; $i++) {
			$idx = Bral_Util_De::get_de_specifique(1, 4);
			if ($tab[$idx]["quantite"] > 0 && $tab[$idx]["estVide"] === false) {
				$cueillette[$idx]["quantite"] = $cueillette[$idx]["quantite"] + 1;
				$tab[$idx]["quantite"] = $tab[$idx]["quantite"] - 1;
				if ($tab[$idx]["quantite"] < 1) {
					$tab[$idx]["estVide"] = true;
					if ($tab[1]["estVide"] === true && $tab[2]["estVide"] === true  &&
						$tab[3]["estVide"] === true && $tab[4]["estVide"] === true ) {
						$planteADetruire = true;
						break; // si la plante est vide, on sort
					}
				}
			} else {
				$tab[$idx]["estVide"] = true;
				if ($tab[1]["estVide"] === true && $tab[2]["estVide"] === true  &&
				$tab[3]["estVide"] === true && $tab[4]["estVide"] === true ) {
					$planteADetruire = true;
					break; // si la plante est vide, on sort
				} else {
					$i--;
				}
			}
		}
		
		// reussite, on met dans le laban
		if ($this->view->okJet1 === true) {
			$labanPartiePlanteTable = new LabanPartieplante();
	
			for ($i=1; $i<=4; $i++) {
				if ($cueillette[$i]["quantite"] > 0) {
					$data = array(
					'id_fk_type_laban_partieplante' => $cueillette[$i]["id_fk"],
					'id_hobbit_laban_partieplante' => $this->view->user->id_hobbit,
					'quantite_laban_partieplante' => $cueillette[$i]["quantite"],
					);
					$labanPartiePlanteTable->insertOrUpdate($data);
				}
			}
		}
		
		// s'il n'y a plus rien sur la plante, il faut la supprimer
		if ($planteADetruire === true) {
			$planteTable = new Plante();
			$where = "id_plante=".$idPlante;
			$planteTable->delete($where);
		} else { // sinon, il faut la mettre � jour
			$data = array(
			"partie_1_plante" => $p["partie_1_plante"] - $cueillette[1]["quantite"],
			"partie_2_plante" => $p["partie_2_plante"] - $cueillette[2]["quantite"],
			"partie_3_plante" => $p["partie_3_plante"] - $cueillette[3]["quantite"],
			"partie_4_plante" => $p["partie_4_plante"] - $cueillette[4]["quantite"],
			);
			$planteTable = new Plante();
			$where = "id_plante=".$idPlante;
			$planteTable->update($data, $where);
		}

		$this->view->cueillette = $cueillette;
		$this->view->nbCueillette = count($cueillette);
		$this->view->planteDetruite = $planteADetruire;
		$this->view->plante = $plante;
			
		$this->majEvenementsStandard();

		$this->calculPx();
		$this->calculBalanceFaim();
		$this->majHobbit();
	}

	function getListBoxRefresh() {
		return array("box_profil", "box_vue", "box_competences_metiers", "box_laban", "box_evenements");
	}

	/*
	 * La quantit� extraite est fonction de la quantit� disponible � cet endroit.
	 * (Directement dans le sac � dos)
	 *  Quantit� maximum ramass�e est fonction du niveau de sagesse du Hobbit :
	 *  SAG : QUANTITE
	 *  0-4 : 1D3
	 *  5-9 : 1D3+1
	 *  10-14 : 1D3+2
	 *  15-19 : 1D3+3
	 *  20-24 : 1D3+4
	 */
	private function calculQuantiteAExtraire() {
		$n = Bral_Util_De::get_1d3();
		$n = $n + floor($this->view->user->sagesse_base_hobbit / 5);
		return $n;
	}
}
