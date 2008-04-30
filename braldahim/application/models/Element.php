<?php

class Element extends Zend_Db_Table {
	protected $_name = 'element';
	protected $_primary = array('x_element', 'y_element');

	function selectVue($x_min, $y_min, $x_max, $y_max) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('element', '*')
		->where('x_element <= ?',$x_max)
		->where('x_element >= ?',$x_min)
		->where('y_element <= ?',$y_max)
		->where('y_element >= ?',$y_min);
		$sql = $select->__toString();

		return $db->fetchAll($sql);
	}

	function findByCase($x, $y) {
		return $this->selectVue($x, $y, $x, $y);
	}
	
	function insertOrUpdate($data) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('element', 'count(*) as nombre, 
		quantite_viande_element as quantiteViande, 
		quantite_peau_element as quantitePeau, 
		quantite_ration_element as quantiteRation, 
		quantite_viande_preparee_element as quantiteViandePreparee,
		quantite_cuir_element as quantiteCuir,
		quantite_fourrure_element as quantiteFourrure,
		quantite_planche_element as quantitePlanche')
		->where('id_fk_hobbit_element = ?',$data["id_fk_hobbit_element"])
		->group(array('quantitePeau', 'quantiteViande', 'quantiteRation', 'quantiteViandePreparee'));
		$sql = $select->__toString();
		$resultat = $db->fetchAll($sql);

		if (count($resultat) == 0) { // insert
			$this->insert($data);
		} else { // update
			$nombre = $resultat[0]["nombre"];
			$quantitePeau = $resultat[0]["quantitePeau"];
			$quantiteViande = $resultat[0]["quantiteViande"];
			$quantiteViandePreparee = $resultat[0]["quantiteViandePreparee"];
			$quantiteRation = $resultat[0]["quantiteRation"];
			$quantiteCuir = $resultat[0]["quantiteCuir"];
			$quantiteFourrure = $resultat[0]["quantiteFourrure"];
			$quantitePlanche = $resultat[0]["quantitePlanche"];
			
			if (isset($data["quantite_viande_element"])) {
				$dataUpdate['quantite_viande_element'] = $quantiteViande + $data["quantite_viande_element"];
			}
			if (isset($data["quantite_peau_element"])) {
				$dataUpdate['quantite_peau_element'] = $quantitePeau + $data["quantite_peau_element"];
			}
			if (isset($data['quantite_viande_preparee_element'])) {
				$dataUpdate['quantite_viande_preparee_element'] = $quantiteViandePreparee + $data["quantite_viande_preparee_element"];
			}
			if (isset($data['quantite_ration_element'])) {
				$dataUpdate['quantite_ration_element'] = $quantiteRation + $data["quantite_ration_element"];
			}
			if (isset($data['quantite_cuir_element'])) {
				$dataUpdate['quantite_cuir_element'] = $quantiteCuir + $data["quantite_cuir_element"];
			}
			if (isset($data['quantite_fourrure_element'])) {
				$dataUpdate['quantite_fourrure_element'] = $quantiteFourrure + $data["quantite_fourrure_element"];
			}
			if (isset($data['quantite_planche_element'])) {
				$dataUpdate['quantite_planche_element'] = $quantitePlanche + $data["quantite_planche_element"];
			}
			if (isset($dataUpdate)) {
				$where = 'id_fk_hobbit_element = '.$data["id_fk_hobbit_element"];
				$this->update($dataUpdate, $where);
			}
		}
	}
}
