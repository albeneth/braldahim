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
class Bral_Util_Messagerie {

	private function __construct() {}

	public static function setXmlResponseMessagerie(&$xml_response, $id_hobbit) {
		$messageTable = new Message();
		$nbNotRead = $messageTable->countByToIdNotRead($id_hobbit);
		$xml_entry = new Bral_Xml_Entry();
		$xml_entry->set_type("action");
		$xml_entry->set_valeur("messagerie");
		$xml_entry->set_data($nbNotRead);
		$xml_response->add_entry($xml_entry);
	}

	public static function constructTabHobbit($tabDestinataires, $valeur = "valeur_2", $sansIdHobbit = -1, $afficheSupprimer = true) {
		Zend_Loader::loadClass("Bral_Util_Lien");
		$hobbitTable = new Hobbit();
		$idDestinatairesTab = split(',', $tabDestinataires);

		$hobbits = $hobbitTable->findByIdList($idDestinatairesTab);

		if ($hobbits == null) {
			return null;
		}
			
		$destinataires = "";
		$aff_js_destinataires = "";
		$tabHobbits = null;

		if ($afficheSupprimer) {
			$afficheLien = false;
		} else {
			$afficheLien = true;
		}

		foreach($hobbits as $h) {

			if (in_array($h["id_hobbit"],$idDestinatairesTab) && ($sansIdHobbit == -1 || $sansIdHobbit != $h["id_hobbit"])) {
				if ($destinataires == "") {
					$destinataires = $h["id_hobbit"];
				} else {
					$destinataires = $destinataires.",".$h["id_hobbit"];
				}
				if ($afficheSupprimer) $aff_js_destinataires .= '<span id="m_'.$valeur.'_'.$h["id_hobbit"].'">';
				$aff_js_destinataires .= Bral_Util_Lien::getJsHobbit($h["id_hobbit"], $h["prenom_hobbit"].' '.$h["nom_hobbit"].' ('.$h["id_hobbit"].')', $afficheLien);
				if ($afficheSupprimer)  {
					$aff_js_destinataires .= ' <img src="/public/images/supprimer.gif" ';
					$aff_js_destinataires .= ' onClick="javascript:supprimerElement(\'aff_'.$valeur.'_dest\',\'m_'.$valeur.'_'.$h["id_hobbit"].'\', \''.$valeur.'_dest\', \''.$h["id_hobbit"].'\')" />';
					$aff_js_destinataires .= '</span>';
				} else {
					$aff_js_destinataires .= "<br>";
				}

				$tabHobbits[$h["id_hobbit"]] = $h;
			}
		}
		$tab = array(
			"hobbits" => $tabHobbits,
			"destinataires" => $destinataires,
			"aff_js_destinataires" => $aff_js_destinataires,
		);
		return $tab;
	}

	public static function constructTabContacts($tabContacts, $idHobbit, $valeur = "valeur_4_contacts") {
		Zend_Loader::loadClass('MessagerieContacts');

		$tab = array("contacts" => "", "aff_js_contacts" => "", "userids" => "", "hobbits" => null);

		if ($tabContacts == null || $tabContacts == "") {
			return $tab;
		}

		$messagerieContactsTable = new MessagerieContacts();
		$idContactsTab = split(',', $tabContacts);

		$contactsTab = $messagerieContactsTable->findByIdsList($idContactsTab, $idHobbit);
		if ($contactsTab == null) {
			return $tab;
		}
			
		$contacts = "";
		$aff_js_contacts = "";
		$userIds = "";
		$hobbits = "";
		$tabHobbits = null;

		foreach($contactsTab as $c) {
			if (in_array($c["id"], $idContactsTab)) {
				if ($contacts == "") {
					$contacts = $c["id"];
				} else {
					$contacts = $contacts.",".$c["id"];
				}
				$aff_js_contacts .= '<span id="m_'.$valeur.'_'.$c["id"].'">';
				$aff_js_contacts .= $c["name"]. ' <img src="/public/images/supprimer.gif" ';
				$aff_js_contacts .= ' onClick="javascript:supprimerElement(\'aff_'.$valeur.'\',\'m_'.$valeur.'_'.$c["id"].'\', \''.$valeur.'\', '.$c["id"].')" />';
				$aff_js_contacts .= '</span>';
			}

			if ($userIds != "") {
				$userIds .= ",";
			}
			$userIds .= $c["userids"];
			$tab = split(',', $c["userids"]);
			foreach($tab as $t) {
				$tabHobbits[] = $t;
			}
		}

		$userIdsControlles = "";

		if ($tabHobbits != null) {
			$hobbitTable = new Hobbit();
			$hobbitsRowset = $hobbitTable->findByIdList($tabHobbits);
			foreach($hobbitsRowset as $h) {
				$hobbits[$h["id_hobbit"]] = $h;
				if ($userIdsControlles != "") {
					$userIdsControlles .= ",";
				}
				$userIdsControlles .= $h["id_hobbit"];
			}
		}

		$tab = array(
			"hobbits" => $hobbits,
			"aff_js_contacts" => $aff_js_contacts,
			"userids" => $userIdsControlles,
			"contacts" => $contacts,
		);
		return $tab;
	}

	public static function prepareListe($idHobbit, $prepareHobbits = false) {
		Zend_Loader::loadClass("MessagerieContacts");
		$messagerieContactsTable = new MessagerieContacts();
		$listesContacts = $messagerieContactsTable->findByUserId($idHobbit);

		$tabListes = null;
		if ($listesContacts != null && count($listesContacts) > 0) {
			$idsHobbit = null;
			foreach($listesContacts as $l) {
				$tab = array(
					'id' => $l["id"],
					'nom' => $l["name"],
					'description' => $l["description"],
					'userids' => $l["userids"],
				);

				if ($prepareHobbits) {
					$tab["hobbits"] = Bral_Util_Messagerie::constructTabHobbit($l["userids"], "listecontact", -1, false);
				}
				$tabListes[] = $tab;
			}
		}

		return $tabListes;
	}

	public static function prepareMessageAEnvoyer($idHobbitSource, $idHobbitDestinataire, $contenu, $idDestinatairesListe) {
		return array ('fromid' => $idHobbitSource,
					  'toid' => $idHobbitDestinataire,
						'toids' => $idDestinatairesListe,
						'message' => $contenu,
						'date_message' => date("Y-m-d H:i:s"),
						'toread' => 0,
						'totrash' => 0,
						'totrashoutbox' => 0,
						'archived' => 0,
		);
	}

	public static function envoiMessageAutomatique($idHobbitSource, $idHobbitDestinataire, $contenu, $view) {
		Zend_Loader::loadClass("Message");
		$data = Bral_Util_Messagerie::prepareMessageAEnvoyer($idHobbitSource, $idHobbitDestinataire, $contenu, $idHobbitDestinataire);
		$messageTable = new Message();
		$messageTable->insert($data);

		$envoiEmail = "non";
		$hobbitTable = new Hobbit();
		$hobbitRowset = $hobbitTable->findById($idHobbitDestinataire);
		if ($hobbitRowset != null) {
			$hobbit = $hobbitRowset->toArray();
			if ($hobbit["envoi_mail_evenement_hobbit"] == "oui") {
				$envoiEmail = "oui";
			}
		}

		if ($envoiEmail == "oui") {
			$config = Zend_Registry::get('config');
			Zend_Loader::loadClass("Bral_Util_Mail");
			Bral_Util_Mail::envoiMailAutomatique($hobbit, $config->mail->message->titre, $contenu, $view);
		}
	}

	public static function prepareMessages($idHobbit, &$paginator, $filtre, $page, $nbMax, $toread = null) {
		Zend_Loader::loadClass("Bral_Util_Lien");

		$messageTable = new Message();
		$config = Zend_Registry::get('config');

		if ($filtre == $config->messagerie->message->type->envoye) {
			$select = $messageTable->getSelectByFromId($idHobbit);
		} else if ($filtre == $config->messagerie->message->type->supprime) {
			$select = $messageTable->getSelectByToOrFromIdSupprime($idHobbit);
		} else if ($filtre == $config->messagerie->message->type->archive) {
			$select = $messageTable->getSelectByToIdArchive($idHobbit);
		} else { // reception
			$select = $messageTable->getSelectByToId($idHobbit, $toread);
		}

		Zend_Loader::loadClass('Zend_Paginator');
		$paginator = Zend_Paginator::factory($select);
		$paginator->setPageRange(2);
		$paginator->setCurrentPageNumber($page);
		$paginator->setItemCountPerPage($nbMax);

		$idsHobbit = "";
		$tabHobbits = null;
		$tabMessages = null;

		if (count($paginator) > 0) {
			foreach ($paginator as $m) {
				$idsHobbit[$m["toid"]] = $m["toid"];
				$idsHobbit[$m["fromid"]] = $m["fromid"];
			}

			if ($idsHobbit != null) {
				$hobbitTable = new Hobbit();
				$hobbits = $hobbitTable->findByIdList($idsHobbit);
				if ($hobbits != null) {
					foreach($hobbits as $h) {
						$tabHobbits[$h["id_hobbit"]] = $h;
					}
				}
			}

			foreach ($paginator as $m) {
				$expediteur = "";
				$destinataire = "";
				if ($tabHobbits != null) {
					if (array_key_exists($m["toid"], $tabHobbits)) {
						$destinataire = Bral_Util_Lien::getJsHobbit($tabHobbits[$m["toid"]]["id_hobbit"], $tabHobbits[$m["toid"]]["prenom_hobbit"] . " ". $tabHobbits[$m["toid"]]["nom_hobbit"]. " (".$tabHobbits[$m["toid"]]["id_hobbit"].")");
					} else {
						$destinataire = " Erreur ".$m["toid"];
					}

					if (array_key_exists($m["fromid"], $tabHobbits)) {
						$expediteur = Bral_Util_Lien::getJsHobbit($tabHobbits[$m["fromid"]]["id_hobbit"], $tabHobbits[$m["fromid"]]["prenom_hobbit"] . " ". $tabHobbits[$m["fromid"]]["nom_hobbit"]. " (".$tabHobbits[$m["fromid"]]["id_hobbit"].")");
					} else {
						$expediteur = " Erreur ".$m["fromid"];
					}
				}
				if ($expediteur == "") {
					$expediteur = " Erreur inconnue";
				}
				if ($destinataire == "") {
					$destinataire = " Erreur inconnue";
				}

				$tabMessages[] = array(
					"id_message" => $m["id"],
					"titre" => $m["message"],
					"date" => $m["date_message"],
					"expediteur" => $expediteur,
					"destinataire" => $destinataire,
					"toread" => $m["toread"],
				);
			}
		}
		return $tabMessages;
	}

	public static function preparePage($request, &$view) {
		$view->page = 1;

		if ($request->get("valeur_4") != "") {
			$view->filtre =  Bral_Util_Controle::getValeurIntVerif($request->get("valeur_4"));
		} else {
			$view->filtre = $view->config->messagerie->message->type->reception;
		}

		switch($request->get("valeur_1")) {
			case "envoi" :
			case "nouveau" :
			case "repondre" :
			case "repondretous" :
				$view->page = 1;
				break;
			case "transferer" :
			case "archiver" :
			case "supprimer" :
			case "supprimerselection" :
			case "archiverselection" :
			case "marquerlueselection" :
			case "message" :
			case "page" :
				$view->page =  Bral_Util_Controle::getValeurIntVerif($request->get("valeur_3"));
				break;
			default:
				$view->page = 1;
				break;
		}

		if ($view->page < 1) {
			$view->page = 1;
		}
	}
}