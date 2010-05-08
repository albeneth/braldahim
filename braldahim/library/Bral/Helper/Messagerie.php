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
class Bral_Helper_Messagerie {

	public static function destinatairesZoneJs($xMin, $yMin, $xMax, $yMax, $z, $idBraldun, $texte) {

		$braldunTable = new Braldun();
		$bralduns = $braldunTable->selectVue($xMin, $yMin, $xMax, $yMax, $z, $idBraldun);
		unset($braldunTable);
		
		$js = "";
		foreach($bralduns as $h) {
			$nom = $h['prenom_braldun']. " ". $h['nom_braldun'];
			$js .= "makeJsListeAvecSupprimer('valeur_2_dest', ";
			$js .= "'".addslashes($nom)."', ";
			$js .= "'".$h["id_braldun"]."', ";
			$js .= "'".$h["id_braldun"]."');";
		}
		
		$retour = "";
		if ($js != "") {
			$retour = '<label id="message_nb_label" class="alabel" style="text-decoration: underline;" onClick="'.$js.'">'.$texte.'</label>';
		}
		
		return $retour;
	}

}


