<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3. 
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: EchoppePotionMinerai.php 595 2008-11-09 11:21:27Z yvonnickesnault $
 * $Author: yvonnickesnault $
 * $LastChangedDate: 2008-11-09 12:21:27 +0100 (Sun, 09 Nov 2008) $
 * $LastChangedRevision: 595 $
 * $LastChangedBy: yvonnickesnault $
 */
class EchoppePotionMinerai extends Zend_Db_Table {
	protected $_name = 'echoppe_potion_minerai';
	protected $_primary = array("id_fk_type_echoppe_potion_minerai","id_fk_echoppe_potion_minerai");
	
   function findByIdsPotion($tabId) {
    	$where = "";
    	if ($tabId == null || count($tabId) == 0) {
    		return null;
    	}
    	
    	foreach($tabId as $id) {
			if ($where == "") {
				$or = "";
			} else {
				$or = " OR ";
			}
			$where .= " $or id_fk_echoppe_potion_minerai =".(int)$id;
    	}
    	
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('echoppe_potion_minerai', '*')
		->from('type_minerai', '*')
		->where($where)
		->where('echoppe_potion_minerai.id_fk_type_echoppe_potion_minerai = type_minerai.id_type_minerai');
		$sql = $select->__toString();
		
		return $db->fetchAll($sql);
    }
}