<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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
**/
class Item_Rack extends CommonDBRelation {

   static public $itemtype_1 = 'Rack';
   static public $items_id_1 = 'racks_id';
   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';
   static public $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;
   static public $mustBeAttached_1      = false;
   static public $mustBeAttached_2      = false;

   static function getTypeName($nb = 0) {
      return _n('Item', 'Item', $nb);
   }

   /**
    * Count connection for an operating system
    *
    * @param Rack $rack Rack object instance
    *
    * @return integer
   **/
   static function countForRack(Rack $rack) {
      return countElementsInTable(self::getTable(),
                                  ['racks_id' => $rack->getID()]);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $nb = 0;
      switch ($item->getType()) {
         default:
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = countElementsInTable(
                  self::getTable(),
                  ['racks_id'  => $item->getID()]
               );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showItems($item, $withtemplate);
   }

   /**
    * Print racks items
    *
    * @return void
   **/
   static function showItems(Rack $rack) {
      global $DB, $CFG_GLPI;

      $ID = $rack->getID();
      $rand = mt_rand();

      if (!$rack->getFromDB($ID)
          || !$rack->can($ID, READ)) {
         return false;
      }
      $canedit = $rack->canEdit($ID);

      $items = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'racks_id' => $rack->getID()
         ]
      ]);
      $link = new self();

      Session::initNavigateListItems(
         self::getType(),
         //TRANS : %1$s is the itemtype name,
         //        %2$s is the name of the item (used for headings of a list)
         sprintf(
            __('%1$s = %2$s'),
            $rack->getTypeName(1),
            $rack->getName()
         )
      );

      echo "<div id='switchview'>";
      echo "<i id='sviewlist' class='pointer fa fa-list-alt' title='".__('View as list')."'></i>";
      echo "<i id='sviewgraph' class='pointer fa fa-th-large selected' title='".__('View graphical representation')."'></i>";
      echo "</div>";

      $items = iterator_to_array($items);
      echo "<div id='viewlist'>";

      /*$rack = new self();*/
      if (!count($items)) {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No item found')."</th></tr>";
         echo "</table>";
      } else {
         if ($canedit) {
            $massiveactionparams = [
               'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
               'container'       => 'mass'.__CLASS__.$rand
            ];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
         $header = "<tr>";
         if ($canedit) {
            $header .= "<th width='10'>";
            $header .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header .= "</th>";
         }
         $header .= "<th>".__('Item')."</th>";
         $header .= "<th>".__('Position')."</th>";
         $header .= "<th>".__('Orientation')."</th>";
         $header .= "</tr>";

         echo $header;
         foreach ($items as $row) {
            $item = new $row['itemtype'];
            $item->getFromDB($row['items_id']);
            echo "<tr lass='tab_bg_1'>";
            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
               echo "</td>";
            }
            echo "<td>" . $item->getLink() . "</td>";
            echo "<td>{$row['position']}</td>";
            echo "<td>{$row['orientation']}</td>";
            echo "</tr>";
         }
         echo $header;
         echo "</table>";

         if ($canedit && count($items)) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
         }
         if ($canedit) {
            Html::closeForm();
         }
      }
      echo "</div>";
      echo "<div id='viewgraph'>";

      $data = [];
      //all rows; empty
      for ($i = (int)$rack->fields['number_units']; $i > 0; --$i) {
         $data[Rack::FRONT][$i] = false;
         $data[Rack::REAR][$i] = false;
      }

      //fill rows
      $outbound = [];
      $full_items = [];
      foreach ($items as $row) {
         $rel  = new self;
         $rel->getFromDB($row['id']);
         $item = new $row['itemtype'];
         $item->getFromDB($row['items_id']);

         $position = $row['position'];

         $gs_item = [
            'id'        => $row['id'],
            'name'      => $item->getName(),
            'x'         => $row['hpos'] >= 2 ? 1 : 0,
            'y'         => $rack->fields['number_units'] - $row['position'],
            'height'    => 1,
            'width'     => 2,
            'bgcolor'   => $row['bgcolor'],
            'picture'   => null,
            'url'       => $item->getLinkURL(),
            'rel_url'   => $rel->getLinkURL(),
            'rear'      => false,
            'half_rack' => false,
         ];

         $model_class = $item->getType() . 'Model';
         $modelsfield = strtolower($item->getType()) . 'models_id';
         $model = new $model_class;
         if ($model->getFromDB($item->fields[$modelsfield])) {
            $item->model = $model;

            if ($item->model->fields['required_units'] > 1) {
               $gs_item['height'] = $item->model->fields['required_units'];
               $gs_item['y']      = $rack->fields['number_units'] + 1
                                    - $row['position']
                                    - $item->model->fields['required_units'];
            }

            if ($item->model->fields['is_half_rack'] == 1) {
               $gs_item['half_rack'] = true;
               $gs_item['width'] = 1;
               $row['position'].= "_".$gs_item['x'];
               if ($row['orientation'] == Rack::REAR) {
                  $gs_item['x'] = $row['hpos'] == 2 ? 0 : 1;
               }
            }
         } else {
            $item->model = null;
         }

         if (isset($data[$row['orientation']][$position])) {
            $data[$row['orientation']][$row['position']] = [
               'row'     => $row,
               'item'    => $item,
               'gs_item' => $gs_item
            ];

            //add to other side if needed
            if ($item->model == null
                || $item->model->fields['depth'] >= 1) {
               $gs_item['rear'] = true;
               $flip_orientation = (int) !((bool) $row['orientation']);
               if ($gs_item['half_rack']) {
                  $gs_item['x'] = (int) !((bool) $gs_item['x']);
                  //$row['position'] = substr($row['position'], 0, -2)."_".$gs_item['x'];
               }
               $data[$flip_orientation][$row['position']] = [
                  'row'     => $row,
                  'item'    => $item,
                  'gs_item' => $gs_item
               ];
            }
         } else {
            $outbound[] = ['row' => $row, 'item' => $item, 'gs_item' => $gs_item];
         }
      }

      if (count($outbound)) {
         echo "<table class='outbound'><thead><th>";
         echo __('Following elements are out of rack bounds');
         echo "</th></thead><tbody>";
         $count = 0;
         foreach ($outbound as $out) {
            echo "<tr><td>".self::getCell($out)."</td></tr>";
            ++$count;
         }
         echo "</tbody></table>";
      }

      echo '
      <div class="racks_row">
         <div class="racks_col">
            <h2>'.__('Front').'</h2>
            <ul class="indexes"></ul>
            <div class="grid-stack grid-stack-2 grid-rack" id="grid-front">
               <div class="racks_add"></div>';
      foreach ($data[Rack::FRONT] as $current_item) {
         echo self::getCell($current_item);
      }
      echo '   <div class="grid-stack-item lock-bottom"
                    data-gs-no-resize="true" data-gs-no-move="true"
                    data-gs-height="1" data-gs-width="2" data-gs-x="0" data-gs-y="'.$rack->fields['number_units'].'"></div>
            </div>
            <ul class="indexes"></ul>
         </div>
         <div class="racks_col">
            <h2>'.__('Rear').'</h2>
            <ul class="indexes"></ul>
            <div class="grid-stack grid-stack-2 grid-rack" id="grid2-rear">
               <div class="racks_add"></div>';
      foreach ($data[Rack::REAR] as $current_item) {
         echo self::getCell($current_item);
      }
      echo '   <div class="grid-stack-item lock-bottom"
                    data-gs-no-resize="true" data-gs-no-move="true"
                    data-gs-height="1" data-gs-width="2" data-gs-x="0" data-gs-y="'.$rack->fields['number_units'].'">
               </div>
            </div>
            <ul class="indexes"></ul>
         </div>
      </div>
      <div class="sep"></div>';
      echo "<div id='grid-dialog'></div>";
      echo "</div>";

      $rack_add_tip = __s('Insert an item here');
      $ajax_url     = $CFG_GLPI['root_doc']."/ajax/rack.php";

      $js = <<<JAVASCRIPT
      $(function(){
         $('#sviewlist').on('click', function(){
            $('#viewlist').show();
            $('#viewgraph').hide();
            $(this).addClass('selected');
            $('#sviewgraph').removeClass('selected');
         });
         $('#sviewgraph').on('click', function(){
            $('#viewlist').hide();
            $('#viewgraph').show();
            $(this).addClass('selected');
            $('#sviewlist').removeClass('selected');
         });

         $('.grid-stack').gridstack({
            width: 2,
            height: {$rack->fields['number_units']}+1,
            cellHeight: 20,
            verticalMargin: 1,
            float: true,
            disableOneColumnMode: true,
            animate: true,
            removeTimeout: 100,
            disableResize: true,
            draggable: {
              handle: '.grid-stack-item-content',
              appendTo: 'body',
              containment: '.grid-stack',
              cursor: 'move',
              scroll: true,
            }
         });

         for (var i = {$rack->fields['number_units']}; i >= 1; i--) {
            // add index number front of each rows
            $('.indexes').append('<li>' + i + '</li>');

            // append cells for adding new items
            $('.racks_add').append('<div class=\"cell_add\"><span class="tipcontent">{$rack_add_tip}</span></div>');
         }

         var lockAll = function() {
            // lock all item (prevent pushing down elements)
            $('.grid-stack').each(function (idx, gsEl) {
               $(gsEl).data('gridstack').locked('.grid-stack-item', true);
            });

            // add containment to items, this avoid bad collisions on the start of the grid
            $('.grid-stack .grid-stack-item').draggable('option', 'containment', 'parent');
         };
         lockAll(); // call it immediatly

         // grid events
         $('.cell_add').click(function() {
            var index = {$rack->fields['number_units']} - $(this).index();
            var parent_pos = $(this).parents('.racks_col').index()
            var parent = (parent_pos == 0
                           ? 0  // front
                           : 1); // rear
            var current_grid = $(this).parents('.grid-stack').data('gridstack');

            $.ajax({
                  url : "{$link->getFormURL()}",
                  data: {
                     racks_id: {$rack->getID()},
                     orientation: parent,
                     unit: index,
                     ajax: true,
                  },
                  success: function(data) {
                     $('#grid-dialog')
                        .html(data)
                        .dialog({
                           modal: true,
                           width: 'auto'
                        });
                  }
               });
         });

         var x_before_drag = 0;
         var y_before_drag = 0;
         var dirty = false;
         var getHpos = function(x, is_half_rack, is_rack_rear) {
            if (!is_half_rack) {
               return 0;
            } else if (x == 0 && !is_rack_rear) {
               return 1;
            } else if (x == 0 && is_rack_rear) {
               return 2;
            } else if (x == 1 && is_rack_rear) {
               return 1;
            } else if (x == 1 && !is_rack_rear) {
               return 2;
            }
         };

         // drag&drop scenario:
         // - we start by storing position before drag
         // - we send position to db by ajax after drag stop event
         // - if ajax answer return a fail, we restore item to the old position
         //   and we display a message explaning the failure
         // - else we move the other side of asset (if exists)
         $('.grid-stack')
            .on('change', function(event, items) {
               if (dirty) {
                  return;
               }
               var grid = $(event.target).data('gridstack');
               var is_rack_rear = $(grid.container).parents('.racks_col').index() != 0;
               $.each(items, function(index, item) {
                  var is_half_rack = item.el.hasClass('half_rack');
                  var is_el_rear   = item.el.hasClass('rear');
                  var new_pos      = {$rack->fields['number_units']}
                                     - item.y
                                     - item.height
                                     + 1;
                  $.post('{$ajax_url}', {
                     id: item.id,
                     action: 'move_item',
                     position: new_pos,
                     hpos: getHpos(item.x, is_half_rack, is_rack_rear),
                  }, function(answer) {
                     var answer = jQuery.parseJSON(answer);

                     // revert to old position
                     if (!answer.status) {
                        dirty = true;
                        grid.move(item.el, x_before_drag, y_before_drag);
                        dirty = false;
                        displayAjaxMessageAfterRedirect();
                     } else {
                        // move other side if needed
                        var other_side_cls = $(item.el).hasClass('rear')
                           ? "front"
                           : "rear";
                        var other_side_el = $('.grid-stack-item.'+other_side_cls+'[data-gs-id='+item.id+']')

                        if (other_side_el.length) {
                           var other_side_grid = $(other_side_el).parent().data('gridstack');
                           new_x = item.x;
                           new_y = item.y;
                           if (item.width == 1) {
                              new_x = (item.x == 0 ? 1 : 0);
                           }
                           dirty = true;
                           other_side_grid.move(other_side_el, new_x, new_y);
                           dirty = false;
                        }
                     }
                  });
               });
            })
            .on('dragstart', function(event, ui) {
               var grid    = this;
               var element = $(event.target);
               var node    = element.data('_gridstack_node')

               // store position before drag
               x_before_drag = Number(node.x);
               y_before_drag = Number(node.y);

               // disable qtip
               element.qtip('hide', true);
            });

         $('#viewgraph .cell_add, #viewgraph .grid-stack-item').each(function() {
            var tipcontent = $(this).find('.tipcontent');
            if (tipcontent.length) {
               $(this).qtip({
                  position: {
                     my: 'left center',
                     at: 'right center',
                  },
                  content: {
                     text: tipcontent
                  },
                  style: {
                     classes: 'qtip-shadow qtip-bootstrap rack_tipcontent'
                  }
               });
            }
         });
      });
JAVASCRIPT;
      echo Html::scriptBlock($js);
   }

   function showForm($ID, $options = []) {
      global $DB, $CFG_GLPI;

      $colspan = 4;

      echo "<div class='center'>";

      $this->initForm($ID, $this->fields);
      $this->showFormHeader();

      $rack = new Rack();
      $rack->getFromDB($this->fields['racks_id']);

      $rand = mt_rand();

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_itemtype$rand'>".__('Item type')."</label></td>";
      echo "<td>";
      Dropdown::showFromArray(
         'itemtype',
         array_combine($CFG_GLPI['rackable_types'], $CFG_GLPI['rackable_types']), [
            'display_emptychoice'   => true,
            'value'                 => $this->fields["itemtype"],
            'rand'                  => $rand
         ]
      );

      //get all used items
      $used = [];
      $iterator = $DB->request([
         'FROM'   => $this->getTable()
      ]);
      while ($row = $iterator->next()) {
         $used [$row['itemtype']][] = $row['items_id'];
      }

      Ajax::updateItemOnSelectEvent(
         "dropdown_itemtype$rand",
         "items_id",
         $CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php", [
            'idtable'   => '__VALUE__',
            'name'      => 'items_id',
            'value'     => $this->fields['items_id'],
            'rand'      => $rand,
            'used'      => $used
         ]
      );

      //TODO: update possible positions according to selected item number of units
      //TODO: update positions on rack selection
      //TODO: update hpos from item model info is_half_rack
      //TODO: update orientation according to item model depth

      echo "</td>";
      echo "<td><label for='dropdown_items_id$rand'>".__('Item')."</label></td>";
      echo "<td id='items_id'>";
      if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
         $itemtype = $this->fields['itemtype'];
         $itemtype = new $itemtype();
         $itemtype::dropdown([
            'name'   => "items_id",
            'value'  => $this->fields['items_id'],
            'rand'   => $rand
         ]);
      } else {
         Dropdown::showFromArray(
            'items_id',
            [], [
               'display_emptychoice'   => true,
               'rand'                  => $rand
            ]
         );
      }

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_racks_id$rand'>".__('Rack')."</label></td>";
      echo "<td>";
      Rack::dropdown(['value' => $this->fields["racks_id"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_position$rand'>".__('Position')."</label></td>";
      echo "<td >";
      Dropdown::showNumber(
         'position', [
            'value'  => $this->fields["position"],
            'min'    => 1,
            'max'    => $rack->fields['number_units'],
            'step'   => 1,
            'used'   => $rack->getFilled($this->fields['itemtype'], $this->fields['items_id']),
            'rand'   => $rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_orientation$rand'>".__('Orientation')."</label></td>";
      echo "<td >";
      Dropdown::showFromArray(
         'orientation', [
            Rack::FRONT => __('Front'),
            Rack::REAR  => __('Rear')
         ], [
            'value' => $this->fields["orientation"],
            'rand' => $rand
         ]
      );
      echo "</td>";
      echo "<td><label for='bgcolor$rand'>".__('Background color')."</label></td>";
      echo "<td>";
      Html::showColorField(
         'bgcolor', [
            'value'  => $this->fields['bgcolor'],
            'rand'   => $rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_hpos$rand'>".__('Horizontal position (from rack point of view)')."</label></td>";
      echo "<td>";
      Dropdown::showFromArray(
         'hpos',
         [
            Rack::POS_NONE    => __('None'),
            Rack::POS_LEFT    => __('Left'),
            Rack::POS_RIGHT   => __('Right')
         ], [
            'value'  => $this->fields['hpos'],
            'rand'   =>$rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }

   function post_getEmpty() {
      $this->fields['bgcolor'] = '#69CEBA';
   }

   /**
    * Get cell content
    *
    * @param mixed      cell       Rack cell (array or false)
    *
    * @return string
    */
   private static function getCell($cell) {
      if ($cell) {
         $item       = $cell['item'];
         $gs_item    = $cell['gs_item'];
         $rear       = $gs_item['rear'];
         $back_class = $rear
                         ? "rear"
                         : "front";
         $half_class = $gs_item['half_rack']
                         ? "half_rack"
                         : "";
         $bg_color   = $gs_item['bgcolor'];
         $fg_color   = Html::getInvertedColor($gs_item['bgcolor']);
         $fg_color_s = "style='color: $fg_color'";

         return "
         <div class='grid-stack-item $back_class $half_class'
               data-gs-width='{$gs_item['width']}' data-gs-height='{$gs_item['height']}'
               data-gs-x='{$gs_item['x']}' data-gs-y='{$gs_item['y']}'
               data-gs-id='{$gs_item['id']}'
               style='background-color: $bg_color; color: $fg_color'>
            <div class='grid-stack-item-content' $fg_color_s>".
               (!$rear
                  ? "<a href='{$gs_item['url']}' $fg_color_s>{$gs_item['name']}</a>
                     <a href='{$gs_item['rel_url']}'><i class='fa fa-link rel-link' $fg_color_s></i></a>"
                  : "{$gs_item['name']}")."
               <span class='tipcontent'>
                  <span>
                     <label>".
                     ($rear
                        ? __("asset rear side")
                        : __("asset front side"))."
                     </label>
                  </span>
                  <span>
                     <label>".__('name').":</label>".
                     $item->fields['name']."
                  </span>
                  <span>
                     <label>".__('serial').":</label>".
                     $item->fields['serial']."
                  </span>
                  <span>
                     <label>".__('Inventory number').":</label>".
                     $item->fields['otherserial']."
                  </span>
                  <span>
                     <label>".__('model').":</label>".
                     (is_object($item->model)
                      && isset($item->model->fields['name'])
                        ? $item->model->fields['name']
                        : '')."
                  </span>
               </span>
            </div>
         </div>";
      }

      return false;
   }

   function prepareInputForAdd($input) {
      return $this->prepareInput($input);
   }

   function prepareInputForUpdate($input) {
      return $this->prepareInput($input);
   }

   /**
    * Prepares input (for update and add)
    *
    * @param array $input Input data
    *
    * @return array
    */
   private function prepareInput($input) {
      $error_detected = [];

      $itemtype = $this->fields['itemtype'];
      $items_id = $this->fields['items_id'];
      $racks_id = $this->fields['racks_id'];
      $position = $this->fields['position'];
      $hpos = $this->fields['hpos'];
      $orientation = $this->fields['orientation'];

      //check for requirements
      if ($this->isNewItem()) {
         if (!isset($input['itemtype'])) {
            $error_detected[] = __('An item type is required');
         }

         if (!isset($input['items_id'])) {
            $error_detected[] = __('An item is required');
         }

         if (!isset($input['racks_id'])) {
            $error_detected[] = __('A rack is required');
         }

         if (!isset($input['position'])) {
            $error_detected[] = __('A position is required');
         }
      }

      if (isset($input['itemtype'])) {
         $itemtype = $input['itemtype'];
      }
      if (isset($input['items_id'])) {
         $items_id = $input['items_id'];
      }
      if (isset($input['racks_id'])) {
         $racks_id = $input['racks_id'];
      }
      if (isset($input['position'])) {
         $position = $input['position'];
      }
      if (isset($input['hpos'])) {
         $hpos = $input['hpos'];
      }
      if (isset($input['orientation'])) {
         $orientation = $input['orientation'];
      }

      if (!count($error_detected)) {
         //check if required U are available at position
         $rack = new Rack();
         $rack->getFromDB($racks_id);

         $filled = $rack->getFilled($itemtype, $items_id);

         $item = new $itemtype;
         $item->getFromDB($items_id);
         $model_class = $item->getType() . 'Model';
         $modelsfield = strtolower($item->getType()) . 'models_id';
         $model = new $model_class;
         if ($model->getFromDB($item->fields[$modelsfield])) {
            $item->model = $model;
         } else {
            $item->model = null;
         }

         $required_units = 1;
         $width          = 1;
         $depth          = 1;
         if ($item->model != null) {
            if ($item->model->fields['required_units'] > 1) {
               $required_units = $item->model->fields['required_units'];
            }
            if ($item->model->fields['is_half_rack'] == 1) {
               if ($this->isNewItem() && !isset($input['hpos']) || $input['hpos'] == 0) {
                  $error_detected[] = __('You must define an horizontal position for this item');
               }
               $width = 0.5;
            }
            if ($item->model->fields['depth'] != 1) {
               if ($this->isNewItem() && !isset($input['orientation'])) {
                  $error_detected[] = __('You must define an orientation for this item');
               }
               $depth = $item->model->fields['depth'];
            }
         }

         if ($position > $rack->fields['number_units'] ||
            $position + $required_units  > $rack->fields['number_units'] + 1
         ) {
            $error_detected[] = __('Item is out of rack bounds');
         } else if (!count($error_detected)) {
            $i = 0;
            while ($i < $required_units) {
               $current_position = $position + $i;
               if (isset($filled[$current_position])) {
                  $width_overflow = false;
                  $depth_overflow = false;
                  if ($filled[$current_position]['width'] + $width > 1) {
                     if ($depth > 0.5) {
                        $width_overflow = true;
                     }
                  } else if ($filled[$current_position]['width'] <= 0.5 && $hpos == $filled[$current_position]['hpos']) {
                     $error_detected[] = __('An item already exists at this horizontal position');
                  }
                  if ($filled[$current_position]['depth'] + $depth > 1) {
                     if ($width > 0.5) {
                        $depth_overflow = true;
                     }
                  } else if ($filled[$current_position]['depth'] <= 0.5 && $orientation == $filled[$current_position]['orientation']) {
                     $error_detected[] = __('An item already exists for this orientation');
                  }

                  if ($width_overflow || $depth_overflow) {
                     $error_detected[] = __('Not enougth space available to place item');
                  }
               }
               ++$i;
            }
         }
      }

      if (count($error_detected)) {
         foreach ($error_detected as $error) {
            Session::addMessageAfterRedirect(
               $error,
               true,
               ERROR
            );
         }
         return false;
      }

      return $input;
   }
}
