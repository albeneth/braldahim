<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3.
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: Insererroute.php 2019 2009-09-19 10:32:13Z yvonnickesnault $
 * $Author: yvonnickesnault $
 * $LastChangedDate: 2009-09-19 12:32:13 +0200 (sam., 19 sept. 2009) $
 * $LastChangedRevision: 2019 $
 * $LastChangedBy: yvonnickesnault $
 */
class Bral_Administrationajax_Insererroute extends Bral_Administrationajax_Administrationajax {

	function getNomInterne() {
		return "box_action";
	}

	function getTitreAction() {
		return "Admin : insérer un route";
	}

	function prepareCommun() {
		Zend_Loader::loadClass("Route");

		$xyRoute = $this->request->get("xy_route");
		if ($xyRoute != null) {
			$xyRoute = $this->request->get("xy_route");
			list ($xRoute, $yRoute) = split("h", $xyRoute);
			Bral_Util_Controle::getValeurIntVerif($xRoute);
			Bral_Util_Controle::getValeurIntVerif($yRoute);
		}

		if ($xyRoute != null) {
			$this->view->xRoute = $xRoute;
			$this->view->yRoute = $yRoute;
		}

		$tabTypesRoute = null;
		$tabTypesRoute["route"]["type"] = "route";
		$tabTypesRoute["route"]["selected"] = "";
		$tabTypesRoute["ville"]["type"] = "ville";
		$tabTypesRoute["ville"]["selected"] = "selected";
		$tabTypesRoute["balise"]["type"] = "balise";
		$tabTypesRoute["balise"]["selected"] = "";

		$this->view->typeRoute = $tabTypesRoute;
	}

	function prepareFormulaire() {
		// rien ici
	}

	function prepareResultat() {
		$xRoute = Bral_Util_Controle::getValeurIntVerif($this->request->getPost("valeur_1"));
		$yRoute = Bral_Util_Controle::getValeurIntVerif($this->request->getPost("valeur_2"));
		$typeRoute = $this->request->getPost("valeur_3");

		$routeTable = new Route();

		$data = array(
			"x_route" => $xRoute,	 	 	 	 	 	 	
			"y_route" => $yRoute,	 	
			"z_route" => 0,		 	 	 	 	 	 	
			"id_fk_hobbit_route" => null,	 	 	 	 	 	
			"date_creation_route" => date("Y-m-d H:i:s"), 	 	 	 	 	 	
			"id_fk_type_qualite_route"  => null, 	 	 	 	 	 	
			"type_route" => $typeRoute,	
			"est_visible_route" => 'oui',
		);
		
		$where = "x_route = ".$xRoute. " AND y_route=".$yRoute;
		$routeTable->delete($where);
		$idRoute = $routeTable->insert($data);
		$this->view->dataRoute = $data;
		$this->view->dataRoute["id_route"] = $idRoute;
	}

	function getListBoxRefresh() {
		return array("box_vue");
	}
}