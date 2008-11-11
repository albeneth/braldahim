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
class Bral_Box_Bminerais extends Bral_Box_Boutique {
	
	public function getTitreOnglet() {
		return "Boutique Minerais";
	}
	
	public function setDisplay($display) {
		$this->view->display = $display;
	}
	
	public function render() {
		$this->preRender();
		$this->prepareArticles();
		return $this->view->render("interface/bminerais.phtml");
	}
	
	private function prepareArticles() {
		Zend_Loader::loadClass('Bral_Util_BoutiqueMinerais');
		$this->view->minerais = Bral_Util_BoutiqueMinerais::construireTabPrix(false);
	}
}
