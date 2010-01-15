<?php
/*
 * @version $Id: HEADER 1 2009-09-21 14:58 Tsmr $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 
// ----------------------------------------------------------------------
// Original Author of file: NOUH Walid & Benjamin Fontan
// Purpose of file: plugin order v1.1.0 - GLPI 0.72
// ----------------------------------------------------------------------
 */

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["id"])) $_GET["id"] = "";
if(!isset($_GET["withtemplate"])) $_GET["withtemplate"] = "";

$PluginOrderReception = new PluginOrderReception();
$PluginOrderLink = new PluginOrderLink();
$PluginOrderOrder_Item = new PluginOrderOrder_Item;

$plugin = new Plugin;
if ($plugin->isActivated("genericobject"))
	usePlugin('genericobject');

if (isset ($_POST["generation"])) {

	if (isset ($_POST["item"])) {
	
		foreach ($_POST["item"] as $key => $val) {
			if ($val == 1) {
				$PluginOrderOrder_Item->getFromDB($_POST["id"][$key]);
				if ($PluginOrderOrder_Item->fields["states_id"] == ORDER_DEVICE_NOT_DELIVRED) {
					addMessageAfterRedirect($LANG['plugin_order'][45], true, ERROR);
					glpi_header($_SERVER["HTTP_REFERER"]);
				}
			}
		}
	}

	if (isset ($_POST["item"])) {
      
      commonHeader($LANG['plugin_order']['title'][1], $_SERVER["PHP_SELF"], "plugins", "order", "order");
      
		$PluginOrderLink->showItemGenerationForm($_SERVER["PHP_SELF"], $_POST);
		
		commonFooter();
		
	} else {
		addMessageAfterRedirect($LANG['plugin_order']['detail'][29], false, ERROR);
		glpi_header($_SERVER["HTTP_REFERER"]);
	}
}
/* genere le materiel */
else if (isset ($_POST["generate"])) {
	$PluginOrderLink->generateNewItem($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/plugins/order/front/order.form.php?id=" . $_POST["plugin_order_orders_id"] . "");
}
/* supprime un lien d'une ligne detail vers un materiel */
else if (isset ($_POST["deleteLinkWithItem"])) {
	foreach ($_POST["item"] as $key => $val) {
		if ($val == 1)
			$PluginOrderLink->deleteLinkWithItem($key, $_POST["itemtype"][$key],$_POST["plugin_order_orders_id"]);
	}
	glpi_header($CFG_GLPI["root_doc"] . "/plugins/order/front/order.form.php?id=" . $_POST["plugin_order_orders_id"] . "");
}
/* cree un lien d'une ligne detail vers un materiel */
else if (isset ($_POST["createLinkWithItem"])) {

   if ($_POST["item"]) {
      $i = 0;
      if (count($_POST["item"]) <= 1) {

         foreach ($_POST["item"] as $key => $val)
         {
            if ($val == 1)
            {
               $PluginOrderOrder_Item->getFromDB($_POST["id"][$key]);
               if ($PluginOrderOrder_Item->fields["states_id"] == ORDER_DEVICE_NOT_DELIVRED) {
                  addMessageAfterRedirect($LANG['plugin_order'][46], true, ERROR);
                  glpi_header($_SERVER["HTTP_REFERER"]);
               } else
               {

                  $PluginOrderLink->createLinkWithItem($key, $_POST["items_id"], $_POST["itemtype"], $_POST["plugin_order_orders_id"]);
                  
               }
            }
         }
      } else
         addMessageAfterRedirect($LANG['plugin_order'][42], true, ERROR);
   }
	glpi_header($CFG_GLPI["root_doc"] . "/plugins/order/front/order.form.php?id=" . $_POST["plugin_order_orders_id"] . "");
	
}

?>