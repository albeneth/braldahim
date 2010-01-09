<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3. 
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: TypePlante.php 612 2008-11-10 22:16:47Z yvonnickesnault $
 * $Author: yvonnickesnault $
 * $LastChangedDate: 2008-11-10 23:16:47 +0100 (lun., 10 nov. 2008) $
 * $LastChangedRevision: 612 $
 * $LastChangedBy: yvonnickesnault $
 */
class TypePlante extends Zend_Db_Table {
	protected $_name = 'type_plante';
	protected $_primary = 'id_type_plante';
	
	public function fetchAllAvecEnvironnement() {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('type_plante', '*')
		->from('environnement', '*')
		->where('type_plante.id_fk_environnement_type_plante = environnement.id_environnement');
		$sql = $select->__toString();

		return $db->fetchAll($sql);
	}
	
	public function findAll() {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('type_plante', '*')
		->order(array("categorie_type_plante", "nom_type_plante"));
		$sql = $select->__toString();

		return $db->fetchAll($sql);
	}
	
	public function findById($id){
		$where = $this->getAdapter()->quoteInto('id_type_plante = ?',(int)$id);
		return $this->fetchRow($where);
	}
	
}