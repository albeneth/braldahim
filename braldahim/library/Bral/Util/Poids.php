<?php

class Bral_Util_Poids {

	 const POIDS_CASTARS = 0.008;
	 const POIDS_PEAU = 0.5;
	 const POIDS_VIANDE = 0.8;
	 const POIDS_VIANDE_PREPAREE = 0.6;
	 const POIDS_RATION = 0.4;
	 const POIDS_CUIR = 0.4;
	 const POIDS_FOURRURE = 0.4;
	 const POIDS_PLANCHE = 5;
	 const POIDS_RUNE = 0.05;
	 const POIDS_POTION = 0.3;
	 const POIDS_MINERAI = 0.4;
	 const POIDS_LINGOT = 0.5;
	 const POIDS_PARTIE_PLANTE_BRUTE = 0.002;
	 const POIDS_PARTIE_PLANTE_PREPAREE = 0.003;
	
	function __construct() {
	}
	
	public static function calculPoidsTransportable($niveauForce) {
		return (2 * $niveauForce) + 3;
	}
	
	// $idHobbit => -1 pour un nouvel hobbit
	public static function calculPoidsTransporte($idHobbit, $castars) {
		$retour = 0;
		
		$retour = self::ajoute($retour, $castars, self::POIDS_CASTARS);
		
		if ($idHobbit > 0) {
			$retour = $retour + self::calculPoidsTransporteLaban($idHobbit);
			$retour = $retour + self::calculPoidsTransporteLabanMinerais($idHobbit);
			$retour = $retour + self::calculPoidsTransporteLabanPartiesPlantes($idHobbit);
			$retour = $retour + self::calculPoidsTransporteLabanEquipement($idHobbit);
			$retour = $retour + self::calculPoidsTransporteLabanPotion($idHobbit);
			$retour = $retour + self::calculPoidsTransporteLabanRune($idHobbit);
			
			$retour = $retour + self::calculPoidsTransporteEquipement($idHobbit);
		}
		return $retour;
	}
	
	private static function ajoute($poids, $e, $poidsUnitaire) {
		$ajout = 0;
		if ($e > 0) {
			$ajout = $e * $poidsUnitaire;
		}
		return $poids + $ajout;
	}
	
	private static function calculPoidsTransporteLaban($idHobbit) {
		$poids = 0;
		Zend_Loader::loadClass("Laban");
		$labanTable = new Laban();
		$laban = $labanTable->findByIdHobbit($idHobbit);
		
		foreach ($laban as $p) {
			$poids = self::ajoute($poids, $p["quantite_peau_laban"], self::POIDS_PEAU);
			$poids = self::ajoute($poids, $p["quantite_viande_laban"], self::POIDS_VIANDE);
			$poids = self::ajoute($poids, $p["quantite_viande_preparee_laban"], self::POIDS_VIANDE_PREPAREE);
			$poids = self::ajoute($poids, $p["quantite_ration_laban"], self::POIDS_RATION);
			$poids = self::ajoute($poids, $p["quantite_cuir_laban"], self::POIDS_CUIR);
			$poids = self::ajoute($poids, $p["quantite_fourrure_laban"], self::POIDS_FOURRURE);
			$poids = self::ajoute($poids, $p["quantite_planche_laban"], self::POIDS_PLANCHE);
		}
		unset($labanTable);
		unset($laban);
		
		return $poids;
	}
	
	private static function calculPoidsTransporteLabanMinerais($idHobbit) {
		$poids = 0;
		Zend_Loader::loadClass("LabanMinerai");
		$labanMineraiTable = new LabanMinerai();
		$minerais = $labanMineraiTable->findByIdHobbit($idHobbit);
		
		foreach ($minerais as $m) {
			$poids = self::ajoute($poids, $m["quantite_brut_laban_minerai"], self::POIDS_MINERAI);
			$poids = self::ajoute($poids, $m["quantite_lingots_laban_minerai"], self::POIDS_LINGOT);
		}

		unset($labanMineraiTable);
		unset($minerais);
		
		return $poids;
	}
	
	private static function calculPoidsTransporteLabanPartiesPlantes($idHobbit) {
		$poids = 0;
		Zend_Loader::loadClass("LabanPartieplante");
		
		$labanPartiePlanteTable = new LabanPartieplante();
		$partiePlantes = $labanPartiePlanteTable->findByIdHobbit($idHobbit);
		
		foreach ($partiePlantes as $p) {
			$poids = self::ajoute($poids, $p["quantite_laban_partieplante"], self::POIDS_PARTIE_PLANTE_BRUTE);
			$poids = self::ajoute($poids, $p["quantite_preparee_laban_partieplante"], self::POIDS_PARTIE_PLANTE_PREPAREE);
		}
		
		unset($labanPartiePlanteTable);
		unset($partiePlantes);
		
		return $poids;
	}
	
	private static function calculPoidsTransporteLabanEquipement($idHobbit) {
		$poids = 0;
		Zend_Loader::loadClass("LabanEquipement");
		$labanEquipementTable = new LabanEquipement();
		$equipements = $labanEquipementTable->findByIdHobbit($idHobbit);
		
		$tabWhere = null;
		foreach ($equipements as $e) {
			$poids = self::ajoute($poids, 1, $e["poids_recette_equipement"]);
			$tabWhere[] = $e["id_laban_equipement"];
		}
		
		if ($tabWhere != null) {
			Zend_Loader::loadClass("EquipementRune");
			$equipementRuneTable = new EquipementRune();
			$equipementRunes = $equipementRuneTable->findByIdsEquipement($tabWhere);
			
			if (count($equipementRunes) > 0) {
				$poids = self::ajoute($poids, count($equipementRunes), self::POIDS_RUNE);
			}
			
			unset($equipementRuneTable);
			unset($equipementRunes);
		}
		
		unset($labanEquipementTable);
		unset($equipements);
		unset($tabWhere);
		
		return $poids;
	}
	
	private static function calculPoidsTransporteLabanPotion($idHobbit) {
		Zend_Loader::loadClass("LabanPotion");
		$labanPotionTable = new LabanPotion();
		$nbPotions = $labanPotionTable->countByIdHobbit($idHobbit);
		unset($labanPotionTable);
		return self::ajoute(0, $nbPotions, self::POIDS_POTION);
	}
	
	private static function calculPoidsTransporteLabanRune($idHobbit) {
		Zend_Loader::loadClass("LabanRune");
		$labanRuneTable = new LabanRune();
		$nbRunes = $labanRuneTable->countByIdHobbit($idHobbit);
		unset($labanRuneTable);
		return self::ajoute(0, $nbRunes, self::POIDS_RUNE);
	}
	
	private static function calculPoidsTransporteEquipement($idHobbit) {
		$poids = 0;
		Zend_Loader::loadClass("HobbitEquipement");
		$hobbitEquipementTable = new HobbitEquipement();
		$equipements = $hobbitEquipementTable->findByIdHobbit($idHobbit);
		
		$tabWhere = null;
		foreach ($equipements as $e) {
			$poids = self::ajoute($poids, 1, $e["poids_recette_equipement"]);
			$tabWhere[] = $e["id_equipement_hequipement"];
		}
		
		if ($tabWhere != null) {
			Zend_Loader::loadClass("EquipementRune");
			$equipementRuneTable = new EquipementRune();
			$equipementRunes = $equipementRuneTable->findByIdsEquipement($tabWhere);
			
			if (count($equipementRunes) > 0) {
				$poids = self::ajoute($poids, count($equipementRunes), self::POIDS_RUNE);
			}
			
			unset($equipementRuneTable);
			unset($equipementRunes);
		}
		
		unset($hobbitEquipementTable);
		unset($equipements);
		unset($tabWhere);
		
		return $poids;
	}
}