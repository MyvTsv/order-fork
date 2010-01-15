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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

class PluginOrderReception extends CommonDBChild {

	public $dohistory=true;
	public $table="glpi_plugin_order_orders_items";
   
   public $itemtype = 'PluginOrderOrder';
   public $items_id = 'plugin_order_orders_id';
   
   static function getTypeName() {
      global $LANG;

      return $LANG['plugin_order'][6];
   }
   
   function canCreate() {
      return plugin_order_haveRight('order', 'w');
   }

   function canView() {
      return plugin_order_haveRight('order', 'r');
   }
   
   function getFromDBByOrder($plugin_order_orders_id) {
		global $DB;
		
		$query = "SELECT * FROM `".$this->table."`
					WHERE `plugin_order_orders_id` = '" . $plugin_order_orders_id . "' ";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result) != 1) {
				return false;
			}
			$this->fields = $DB->fetch_assoc($result);
			if (is_array($this->fields) && count($this->fields)) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}
	
	function checkThisItemStatus($detailID, $states_id) {
      global $DB;
      
      $query = "SELECT `states_id` 
               FROM `glpi_plugin_order_orders_items` 
               WHERE `id` = '$detailID' ";
      $result = $DB->query($query);
      if ($DB->result($result, 0, "states_id") == $states_id)
         return true;
      else
         return false;
   }
   
   function checkItemStatus($plugin_order_orders_id, $plugin_order_references_id, $states_id) {
      global $DB;
      
      $query = "SELECT COUNT(*) AS cpt 
               FROM `glpi_plugin_order_orders_items` 
               WHERE `plugin_order_orders_id` = '$plugin_order_orders_id' 
               AND `plugin_order_references_id` = '$plugin_order_references_id' 
               AND `states_id` = '".$states_id."' ";
      $result = $DB->query($query);
      if ($DB->result($result, 0, "cpt") > 0)
         return ($DB->result($result, 0, 'cpt'));
      else
         return false;
   }
	
	function defineTabs($ID, $withtemplate) {
		global $LANG;

		$ong[1] = $LANG['title'][26];

		return $ong;
	}

	function showForm($target, $ID) {
		global $LANG;
      
      if (!plugin_order_haveRight("order", "r"))
			return false;
			
		if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w',$input);
      }
      $this->showTabs($ID);

      $this->showFormHeader($target,$ID,'',1);
      
      $PluginOrderOrder = new PluginOrderOrder();
      $PluginOrderOrder->getFromDB($this->fields["plugin_order_orders_id"]);

      $PluginOrderReference = new PluginOrderReference();
      $PluginOrderReference->getFromDB($this->fields["plugin_order_references_id"]);
      
      $canedit = $PluginOrderOrder->can($this->fields["plugin_order_orders_id"], 'w') && !$PluginOrderOrder->canUpdateOrder($this->fields["plugin_order_orders_id"]) && $PluginOrderOrder->fields["states_id"] != ORDER_STATUS_CANCELED;
      
      echo "<tr class='tab_bg_2'><td>" . $LANG['plugin_order']['detail'][6] . ": </td>";
      echo "<td>";
      $data = array();
      $data["id"] = $this->fields["plugin_order_references_id"];
      $data["name"]= $PluginOrderReference->fields["name"];
      echo $PluginOrderReference->getReceptionReferenceLink($data);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_2'><td>" . $LANG['plugin_order']['detail'][21] . ": </td>";
      echo "<td>";
      if ($canedit)
         showDateFormItem("delivery_date",$this->fields["delivery_date"],true,1);
      else
         echo convDate($this->fields["delivery_date"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_2'><td>" . $LANG['financial'][19] . ": </td>";
      echo "<td>";
      if ($canedit)
         autocompletionTextField($this,"delivery_number");
      else
         echo $this->fields["delivery_number"];
      echo "</td></tr>";
         
      $this->showFormButtons($ID,'',1,false);
      
      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;
	}
	
	function showOrderReception($plugin_order_orders_id) {
      global $DB, $CFG_GLPI, $LANG;

      $PluginOrderOrder = new PluginOrderOrder();
      $PluginOrderOrder->getFromDB($plugin_order_orders_id);
      $PluginOrderOrder_Item = new PluginOrderOrder_Item();
      $PluginOrderReference = new PluginOrderReference();
      
      initNavigateListItems($this->getType(),$LANG['plugin_order'][7] ." = ". $PluginOrderOrder->fields["name"]);
      
      $canedit = $PluginOrderOrder->can($plugin_order_orders_id, 'w') && !$PluginOrderOrder->canUpdateOrder($plugin_order_orders_id) && $PluginOrderOrder->fields["states_id"] != ORDER_STATUS_CANCELED;
      
      $query_ref = "SELECT `glpi_plugin_order_orders_items`.`id` AS IDD, `glpi_plugin_order_orders_items`.`plugin_order_references_id` AS id, `glpi_plugin_order_references`.`name`, `glpi_plugin_order_references`.`itemtype`, `glpi_plugin_order_references`.`manufacturers_id` " .
      "FROM `glpi_plugin_order_orders_items`, `glpi_plugin_order_references` " .
      "WHERE `plugin_order_orders_id` = '$plugin_order_orders_id' " .
      "AND `glpi_plugin_order_orders_items`.`plugin_order_references_id` = `glpi_plugin_order_references`.`id`  " .
      "GROUP BY `glpi_plugin_order_orders_items`.`plugin_order_references_id` " .
      "ORDER BY `glpi_plugin_order_orders_items`.`id`";
      $result_ref = $DB->query($query_ref);
      $numref = $DB->numrows($result_ref);

      while ($data_ref=$DB->fetch_array($result_ref)){
         
         addToNavigateListItems($this->getType(),$data_ref['IDD']);
         
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         if (!$numref)
            echo "<tr><th>" . $LANG['plugin_order']['detail'][20] . "</th></tr></table></div>";
         else {
            
            $plugin_order_references_id = $data_ref["id"];
            $typeRef = $data_ref["itemtype"];		
            $item = new $typeRef();
            $rand = mt_rand();
            echo "<tr><th><ul><li>";
            echo "<a href=\"javascript:showHideDiv('reception$rand','reception$rand','" . $CFG_GLPI["root_doc"] . "/pics/plus.png','" . $CFG_GLPI["root_doc"] . "/pics/moins.png');\">";
            echo "<img alt='' name='reception$rand' src=\"" . $CFG_GLPI["root_doc"] . "/pics/plus.png\">";
            echo "</a></li></ul></th>";
            echo "<th>" . $LANG['plugin_order']['detail'][6] . "</th>";
            echo "<th>" . $LANG['common'][5] . "</th>";
            echo "<th>" . $LANG['plugin_order']['reference'][1] . "</th>";
            echo "<th>" . $LANG['plugin_order']['delivery'][5] . "</th>";
            echo "</tr>";
            echo "<tr class='tab_bg_1 center'>";
            echo "<td></td>";
            echo "<td align='center'>" . $item->getTypeName() . "</td>";
            echo "<td align='center'>" . Dropdown::getDropdownName("glpi_manufacturers", $data_ref["manufacturers_id"]) . "</td>";
            echo "<td>" . $PluginOrderReference->getReceptionReferenceLink($data_ref) . "</td>";
            echo "<td>" . $PluginOrderOrder_Item->getDeliveredQuantity($plugin_order_orders_id, $plugin_order_references_id) . " / " . $PluginOrderOrder_Item->getTotalQuantityByRef($plugin_order_orders_id,$plugin_order_references_id) . "</td>";
            echo "</tr></table>";

            echo "<div class='center' id='reception$rand' style='display:none'>";
            echo "<form method='post' name='order_reception_form$rand' id='order_reception_form$rand'  action=\"" . $CFG_GLPI["root_doc"] . "/plugins/order/front/reception.form.php\">";
            echo "<table class='tab_cadre_fixe'>";

            echo "<tr>";
            echo "<th width='15'></th>";
            echo "<th>" . $LANG['common'][2] . "</th>";
            echo "<th>" . $LANG['plugin_order']['detail'][2] . "</th>";
            echo "<th>" . $LANG['plugin_order']['detail'][19] . "</th>";
            echo "<th>" . $LANG['plugin_order']['detail'][21] . "</th>";
            echo "<th>" . $LANG['financial'][19] . "</th>";
            echo "</tr>";
            
            $query = "SELECT `glpi_plugin_order_orders_items`.`id` AS IDD, `glpi_plugin_order_references`.`id` AS id,`glpi_plugin_order_references`.`templates_id`, `glpi_plugin_order_orders_items`.`states_id`, `glpi_plugin_order_orders_items`.`delivery_date`,`glpi_plugin_order_orders_items`.`delivery_number`, `glpi_plugin_order_references`.`name`, `glpi_plugin_order_references`.`itemtype`, `glpi_plugin_order_orders_items`.`items_id`
                    FROM `glpi_plugin_order_orders_items`, `glpi_plugin_order_references`
                    WHERE `plugin_order_orders_id` = '$plugin_order_orders_id'
                    AND `glpi_plugin_order_orders_items`.`plugin_order_references_id` = '".$plugin_order_references_id."'
                    AND `glpi_plugin_order_orders_items`.`plugin_order_references_id` = `glpi_plugin_order_references`.`id`
                    ORDER BY `glpi_plugin_order_orders_items`.`id`";
            $result = $DB->query($query);
            $num = $DB->numrows($result);
            
            while ($data=$DB->fetch_array($result)){
               $random = mt_rand();
               
               $detailID = $data["IDD"];

               echo "<tr class='tab_bg_2'>";
               if ($canedit && $this->checkThisItemStatus($detailID, ORDER_DEVICE_NOT_DELIVRED)) {
                  echo "<td width='15' align='left'>";
                  $sel = "";
                  if (isset ($_GET["select"]) && $_GET["select"] == "all")
                     $sel = "checked";
                  
                  echo "<input type='checkbox' name='item[" . $detailID . "]' value='1' $sel>";
                  echo "</td>";
               } else {
                  echo "<td width='15' align='left'></td>";
               }
               
               echo "<td align='center'>" . $data["IDD"] . "</td>";
               echo "<td align='center'>" . $PluginOrderReference->getReceptionReferenceLink($data) . "</td>";
               echo "<td align='center'>";
               $link=getItemTypeFormURL($this->getType());
               if ($canedit && $data["states_id"]==ORDER_DEVICE_DELIVRED)
                  echo "<a href=\"" . $link . "?id=".$data["IDD"]."\">";
               echo $this->getReceptionStatus($detailID);
               if ($canedit && $data["states_id"]==ORDER_DEVICE_DELIVRED)
                  echo "</a>";
               echo "</td>";
               echo "<td align='center'>" . convDate($data["delivery_date"]) . "</td>";
               echo "<td align='center'>" . $data["delivery_number"] . "</td>";

               echo "<input type='hidden' name='id[$detailID]' value='$detailID'>";
               echo "<input type='hidden' name='name[$detailID]' value='" . $data["name"] . "'>";
               echo "<input type='hidden' name='itemtype[$detailID]' value='" . $data["itemtype"] . "'>";
               echo "<input type='hidden' name='templates_id[$detailID]' value='" . $data["templates_id"] . "'>";
               echo "<input type='hidden' name='states_id[$detailID]' value='" . $data["states_id"] . "'>";

            }
            echo "</table>";
            if ($canedit && $this->checkItemStatus($plugin_order_orders_id, $plugin_order_references_id, ORDER_DEVICE_NOT_DELIVRED)) {
               
               echo "<div class='center'>";
               echo "<table width='950px' class='tab_glpi'>";
               echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markCheckboxes('order_reception_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?id=$plugin_order_orders_id&amp;select=all'>".$LANG['buttons'][18]."</a></td>";

               echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkCheckboxes('order_reception_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?id=$plugin_order_orders_id&amp;select=none'>".$LANG['buttons'][19]."</a>";
               echo "</td><td align='left' width='80%'>";
               echo "<input type='hidden' name='plugin_order_orders_id' value='$plugin_order_orders_id'>";
               $this->dropdownReceptionActions($typeRef, $plugin_order_references_id, $plugin_order_orders_id);
               echo "</td>";
               echo "</table>";
               echo "</div>";
               
               $rand = mt_rand();
               
               echo "<div id='massreception" . $plugin_order_orders_id . "$rand'></div>\n";
               
               echo "<script type='text/javascript' >\n";
               echo "function viewmassreception" . $plugin_order_orders_id . "$rand(){\n";
               $params = array ('plugin_order_orders_id' => $plugin_order_orders_id,
                                'plugin_order_references_id' => $plugin_order_references_id);
               ajaxUpdateItemJsCode("massreception" . $plugin_order_orders_id . "$rand",
                                    $CFG_GLPI["root_doc"]."/plugins/order/ajax/massreception.php", $params, false);
               echo "};";
               echo "</script>\n";
               echo "<p><a href='javascript:viewmassreception".$plugin_order_orders_id."$rand();'>";
               echo $LANG['plugin_order']['delivery'][4]."</a></p><br>\n";
            }
            echo "</form></div>";
         }
         echo "<br>";
      }
   }
   
   function dropdownReceptionActions($itemtype,$plugin_order_references_id,$plugin_order_orders_id) {
      global $LANG,$CFG_GLPI;
      
      $rand = mt_rand();

      echo "<select name='receptionActions$rand' id='receptionActions$rand'>";
      echo "<option value='0' selected>-----</option>";
      echo "<option value='reception'>" . $LANG['plugin_order']['delivery'][2] . "</option>";
      echo "</select>";
      $params = array (
         'action' => '__VALUE__',
         'itemtype' => $itemtype,
         'plugin_order_references_id'=>$plugin_order_references_id,
         'plugin_order_orders_id'=>$plugin_order_orders_id
      );
      ajaxUpdateItemOnSelectEvent("receptionActions$rand", "show_receptionActions$rand", $CFG_GLPI["root_doc"] . "/plugins/order/ajax/receptionactions.php", $params);
      echo "<span id='show_receptionActions$rand'>&nbsp;</span>";
   }
   
   function getReceptionStatus($ID) {
      global $DB, $LANG;

      $detail = new PluginOrderOrder_Item;
      $detail->getFromDB($ID);

      switch ($detail->fields["states_id"]) {
         case ORDER_DEVICE_NOT_DELIVRED :
            return $LANG['plugin_order']['status'][11];
         case ORDER_DEVICE_DELIVRED :
            return $LANG['plugin_order']['status'][8];
         default :
            return "";
      }
   }

   function updateBulkReceptionStatus($params) {
      global $LANG, $DB;
      
      $query = "SELECT `id` 
               FROM `glpi_plugin_order_orders_items` 
               WHERE `plugin_order_orders_id` = '" . $params["plugin_order_orders_id"] ."' 
               AND `plugin_order_references_id` = '" . $params["plugin_order_references_id"] ."' 
               AND `states_id` = 0 ";
      $result = $DB->query($query);
      $nb = $DB->numrows($result);
      if ($nb < $params['number_reception'])
         addMessageAfterRedirect($LANG['plugin_order']['detail'][37], true, ERROR);
      else {
         for ($i = 0; $i < $params['number_reception']; $i++) {
            $this->receptionOneItem($DB->result($result, $i, 0), $params['plugin_order_orders_id'], $params["delivery_date"], $params["delivery_number"]);
         }
         $detail = new PluginOrderOrder_Item;
         $detail->updateDelivryStatus($params['plugin_order_orders_id']);
      }
   }
   
   function receptionOneItem($detailID, $plugin_order_orders_id, $delivery_date, $delivery_number) {
      global $LANG;
      
      $detail = new PluginOrderOrder_Item;
      $input["id"] = $detailID;
      $input["delivery_date"] = $delivery_date;
      $input["states_id"] = ORDER_DEVICE_DELIVRED;
      $input["delivery_number"] = $delivery_number;
      $detail->update($input);
      addMessageAfterRedirect($LANG['plugin_order']['detail'][31], true);
   }
   
   function updateReceptionStatus($params) {
      global $LANG;
      
      $detail = new PluginOrderOrder_Item;
      $plugin_order_orders_id = 0;
      if (isset ($params["item"])) {
         foreach ($params["item"] as $key => $val)
            if ($val == 1) {
               if ($detail->getFromDB($key)) {
                  if (!$plugin_order_orders_id)
                     $plugin_order_orders_id = $detail->fields["plugin_order_orders_id"];

                  if ($detail->fields["states_id"] == ORDER_DEVICE_NOT_DELIVRED) {
                     $this->receptionOneItem($key, $plugin_order_orders_id, $params["delivery_date"], $params["delivery_number"]);
                  } else
                     addMessageAfterRedirect($LANG['plugin_order']['detail'][32], true, ERROR);
               }
            }

         $detail->updateDelivryStatus($plugin_order_orders_id);
      } else
         addMessageAfterRedirect($LANG['plugin_order']['detail'][29], false, ERROR);
   }
}

?>