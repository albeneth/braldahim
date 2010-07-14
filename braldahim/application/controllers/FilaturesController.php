<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3.
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: QueteController.php 1734 2009-06-13 06:07:00Z yvonnickesnault $
 * $Author: yvonnickesnault $
 * $LastChangedDate: 2009-06-13 08:07:00 +0200 (Sam, 13 jui 2009) $
 * $LastChangedRevision: 1734 $
 * $LastChangedBy: yvonnickesnault $
 */
class FilaturesController extends Bral_Controller_Action {

	public function doactionAction() {
		Zend_Loader :: loadClass("Bral_Filatures_Factory");
		$this->doBralAction("Bral_Filatures_Factory");
	}
}