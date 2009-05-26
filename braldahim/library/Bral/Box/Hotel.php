<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3.
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: $
 * $Author: $
 * $LastChangedDate: $
 * $LastChangedRevision: $
 * $LastChangedBy: $
 */
class Bral_Box_Hotel extends Bral_Box_Box {

	function getTitreOnglet() {
		return "&Eacute;choppe";
	}

	function getNomInterne() {
		return "box_lieu";
	}

	function setDisplay($display) {
		$this->view->display = $display;
	}

	function render() {
		
		return $this->view->render("interface/hotel.phtml");
	}
}