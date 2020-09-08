<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Appliances Class
**/
class Appliance extends CommonDBTM {
   use Glpi\Features\Clonable;

   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname                   = 'appliance';
   protected $usenotepad               = true;

   public function getCloneRelations() :array {
      return [
         Appliance_Item::class,
         Contract_Item::class,
         Document_Item::class,
         Infocom::class,
         Notepad::class,
         KnowbaseItem_Item::class
      ];
   }

   static function getTypeName($nb = 0) {
      return _n('Appliance', 'Appliances', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('Appliance_Item', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function showForm($ID, $options = []) {
      $rand = mt_rand();

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      $rand = mt_rand();
      $tplmark = $this->getAutofillMark('name', $options);

      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td><label for='textfield_name$rand'>".sprintf(__('%1$s%2$s'), __('Name'), $tplmark) .
           "</label></td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ( $options['withtemplate']== 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField(
         $this,
         'name',
         [
            'value'     => $objectName,
            'rand'      => $rand
         ]
      );
      echo "</td>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_states_id$randDropdown'>".__('Status')."</label></td>";
      echo "<td>";
      State::dropdown([
         'value'     => $this->fields["states_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_visible_appliance' => 1],
         'rand'      => $randDropdown
      ]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_locations_id$randDropdown'>".__('Location')."</label></td>";
      echo "<td>";
      Location::dropdown(['value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"],
                               'rand' => $randDropdown]);
      echo "</td>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_appliancetypes_id$randDropdown'>".__('Type')."</label></td>";
      echo "<td>";
      ApplianceType::dropdown(['value' => $this->fields["appliancetypes_id"], 'rand' => $randDropdown]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_users_id_tech$randDropdown'>".__('Technician in charge of the appliance')."</label></td>";
      echo "<td>";
      User::dropdown(['name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"],
                           'rand'   => $randDropdown]);
      echo "</td>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_manufacturers_id$randDropdown'>".__('Manufacturer')."</label></td>";
      echo "<td>";
      Manufacturer::dropdown(['value' => $this->fields["manufacturers_id"], 'rand' => $randDropdown]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_groups_id_tech$randDropdown'>".__('Group in charge of the appliance')."</label></td>";
      echo "<td>";
      Group::dropdown([
         'name'      => 'groups_id_tech',
         'value'     => $this->fields['groups_id_tech'],
         'entity'    => $this->fields['entities_id'],
         'condition' => ['is_assign' => 1],
         'rand' => $randDropdown
      ]);

      echo "</td>";
      echo "<td><label for='dropdown_applianceenvironments_id$randDropdown'>".ApplianceEnvironment::getTypeName(1)."</label></td>";
      echo "<td>";
      ApplianceEnvironment::dropdown(['value' => $this->fields["applianceenvironments_id"], 'rand' => $randDropdown]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_serial$rand'>".__('Serial number')."</label></td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'serial', ['rand' => $rand]);
      echo "</td>";

      echo "<td><label for='textfield_otherserial$rand'>".sprintf(__('%1$s%2$s'), __('Inventory number'), $tplmark).
           "</label></td>";
      echo "<td>";

      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField(
         $this,
         'otherserial',
         [
            'value'     => $objectName,
            'rand'      => $rand
         ]
      );

      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_users_id$randDropdown'>".__('User')."</label></td>";
      echo "<td>";
      User::dropdown(['value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all',
                           'rand'   => $randDropdown]);
      echo "</td>";

      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_groups_id$randDropdown'>".__('Group')."</label></td>";
      echo "<td>";
      Group::dropdown([
         'value'     => $this->fields["groups_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_itemgroup' => 1],
         'rand'      => $randDropdown
      ]);

      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Associable to a ticket') . "</td><td>";
      Dropdown::showYesNo('is_helpdesk_visible', $this->fields['is_helpdesk_visible']);
      echo "</td></tr>\n";

      $this->showFormButtons($options);
      return true;
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'            => '4',
         'table'         => self::getTable(),
         'field'         =>  'comment',
         'name'          =>  __('Comments'),
         'datatype'      =>  'text'
      ];

      $tab[] = [
         'id'            => '5',
         'table'         =>  Appliance_Item::getTable(),
         'field'         => 'items_id',
         'name'               => _n('Associated item', 'Associated items', 2),
         'nosearch'           => true,
         'massiveaction' => false,
         'forcegroupby'  =>  true,
         'additionalfields'   => ['itemtype'],
         'joinparams'    => ['jointype' => 'child']
      ];

      $tab[] = [
         'id'            => '6',
         'table'         => User::getTable(),
         'field'         => 'name',
         'name'          => __('User'),
         'datatype'      => 'dropdown'
      ];

      $tab[] = [
         'id'            => '8',
         'table'         => Group::getTable(),
         'field'         => 'completename',
         'name'          => __('Group'),
         'condition'     => ['is_itemgroup' => 1],
         'datatype'      => 'dropdown'
      ];

      $tab[] = [
         'id'            => '24',
         'table'         => User::getTable(),
         'field'         => 'name',
         'linkfield'     => 'users_id_tech',
         'name'          => __('Technician in charge'),
         'datatype'      => 'dropdown',
         'right'         => 'own_ticket'
      ];

      $tab[] = [
         'id'            => '49',
         'table'         => Group::getTable(),
         'field'         => 'completename',
         'linkfield'     => 'groups_id_tech',
         'name'          => __('Group in charge'),
         'condition'     => ['is_assign' => 1],
         'datatype'      => 'dropdown'
      ];

      $tab[] = [
         'id'            => '9',
         'table'         => self::getTable(),
         'field'         => 'date_mod',
         'name'          => __('Last update'),
         'massiveaction' => false,
         'datatype'      => 'datetime'
      ];

      $tab[] = [
         'id'            => '10',
         'table'         => ApplianceEnvironment::getTable(),
         'field'         => 'name',
         'name'          => __('Environment'),
         'datatype'      => 'dropdown'
      ];

      $tab[] = [
         'id'            => '12',
         'table'         => self::getTable(),
         'field'         => 'serial',
         'name'          => __('Serial number'),
         'autocomplete'  => true
      ];

      $tab[] = [
         'id'            => '13',
         'table'         => self::getTable(),
         'field'         => 'otherserial',
         'name'          => __('Inventory number'),
         'autocomplete'  => true
      ];

      $tab[] = [
         'id'            => '31',
         'table'         => self::getTable(),
         'field'         => 'id',
         'name'          => __('ID'),
         'datatype'      => 'number',
         'massiveaction' => false
      ];

      $tab[] = [
         'id'            => '80',
         'table'         => 'glpi_entities',
         'field'         => 'completename',
         'name'          => __('Entity'),
         'datatype'      => 'dropdown'
      ];

      $tab[] = [
         'id'            => '7',
         'table'         => self::getTable(),
         'field'         => 'is_recursive',
         'name'          => __('Child entities'),
         'massiveaction' => false,
         'datatype'      => 'bool'
      ];

      $tab[] = [
         'id'            => '81',
         'table'         => Entity::getTable(),
         'field'         => 'entities_id',
         'name'          => sprintf('%s-%s', __('Entity'), __('ID'))
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => $this->getTable(),
         'field'              => 'is_helpdesk_visible',
         'name'               => __('Associable to a ticket'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '32',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => ['is_visible_appliance' => 1]
      ];

      return $tab;
   }

   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Appliance_Item::class,
         ]
      );
   }


   static function getIcon() {
      return "fas fa-cubes";
   }

   /**
    * Get item types that can be linked to an appliance
    *
    * @param boolean $all Get all possible types or only allowed ones
    *
    * @return array
    */
   public static function getTypes($all = false): array {
      global $CFG_GLPI;

      $types = $CFG_GLPI['appliance_types'];

      foreach ($types as $key => $type) {
         if (!class_exists($type)) {
            continue;
         }

         if ($all === false && !$type::canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }

   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $prefix                    = 'Appliance_Item'.MassiveAction::CLASS_ACTION_SEPARATOR;
         $actions[$prefix.'add']    = _x('button', 'Add an item');
         $actions[$prefix.'remove'] = _x('button', 'Remove an item');
      }

      return $actions;
   }

   static function getMassiveActionsForItemtype(
      array &$actions,
      $itemtype,
      $is_deleted = 0,
      CommonDBTM $checkitem = null
   ) {
      if (in_array($itemtype, self::getTypes())) {
         if (self::canUpdate()) {
            $action_prefix                    = 'Appliance_Item'.MassiveAction::CLASS_ACTION_SEPARATOR;
            $actions[$action_prefix.'add']    = "<i class='ma-icon fas fa-file-contract'></i>".
                                                _x('button', 'Add to an appliance');
            $actions[$action_prefix.'remove'] = _x('button', 'Remove from an appliance');
         }
      }
   }

}
