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
class TypeIngredient extends Zend_Db_Table {
	protected $_name = 'type_ingredient';
	protected $_primary = 'id_type_ingredient';
	
	const ID_TYPE_VIANDE_FRAICHE = 8;
}