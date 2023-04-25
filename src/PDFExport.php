<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Dompdf\Dompdf;
use Dompdf\Options;
use Glpi\Application\View\TemplateRenderer;

class PDFExport
{
    public static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = 0, CommonDBTM $checkitem = null)
    {
        $prefix = 'PDFExport' . MassiveAction::CLASS_ACTION_SEPARATOR;
        $actions[$prefix . 'generate'] = '<i class="fa-fw ti ti-file-download"></i>' . _x('button', 'Generate PDF');

        return $actions;
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case "generate":
                echo Html::hidden('no_html_header', ['value' => 1]);
                echo Html::submit(_x('button', 'Generate'), ['name' => 'massiveaction']) . "</span>";
                return true;
        }

        return false;
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case "generate":
                $item_html = "";
                foreach ($ids as $id) {
                    $item->getFromDB($id);
                    $item_html .= $item->showExportHtml();
                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                }
                self::generate($item_html);

                // exit the execution as we generate a pdf and stream it to the browser
                exit;
        }
    }



    public static function generate(string $body, bool $stream = true)
    {
        $head = TemplateRenderer::getInstance()->render('export/parts/header.html.twig', [
            'title'       => 'GLPI PDF Export',
            'css_content' => Html::compileScss([
                'file' => '/css/pdf_export',
            ]),
        ]);
        $footer = TemplateRenderer::getInstance()->render('export/parts/footer.html.twig');
        $html = $head . $body . $footer;
        echo $html;
        exit;
    }
}
