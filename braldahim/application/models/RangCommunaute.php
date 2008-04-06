<?php

class RangCommunaute extends Zend_Db_Table {
	protected $_name = 'rang_communaute';
	protected $_primary = array('id_rang_communaute');
	
	function findByIdCommunaute($idCommunaute) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('rang_communaute')
		->where('id_fk_communaute_rang_communaute = ?', intval($idCommunaute))
		->order('ordre_rang_communaute');
		
		$sql = $select->__toString();
		return $db->fetchAll($sql);
	}
	
	function findRangCreateur($idCommunaute) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('rang_communaute', '*')
		->where('id_fk_communaute_rang_communaute = ?', intval($idCommunaute))
		->where('ordre_rang_communaute = 1');
		
		$sql = $select->__toString();
		return $db->fetchRow($sql);
	}
	
	function findRangSecond($idCommunaute) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('rang_communaute', '*')
		->where('id_fk_communaute_rang_communaute = ?', intval($idCommunaute))
		->where('ordre_rang_communaute = 2');
		
		$sql = $select->__toString();
		return $db->fetchRow($sql);
	}
	
	function findRangNouveau($idCommunaute) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('rang_communaute', '*')
		->where('id_fk_communaute_rang_communaute = ?', intval($idCommunaute))
		->where('ordre_rang_communaute = 20');
		
		$sql = $select->__toString();
		return $db->fetchRow($sql);
	}
}
