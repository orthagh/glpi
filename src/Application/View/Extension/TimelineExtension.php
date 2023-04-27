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

namespace Glpi\Application\View\Extension;

use Config;
use Entity;
use Planning;
use Twig\TwigFilter;
use CommonITILObject;
use CommonITILValidation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class TimelineExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('item_position_class', [$this, 'getItemPositionClass']),
            new TwigFilter('state_class', [$this, 'getStateClass']),
            new TwigFilter('solution_class', [$this, 'getSolutionClass']),
        ];
    }


    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_timeline_reversed', [$this, 'isTimelineReversed']),
            new TwigFunction('is_anonym_user', [$this, 'isAnonymUser']),
            new TwigFunction('is_validation_answer', [$this, 'isValidationAnswer']),
            new TwigFunction('timeline_display_relative_date', [$this, 'isTimelineDisplayRelativeDate']),
            new TwigFunction('can_edit_timeline_entry', [$this, 'canEditTimelineEntry']),
        ];
    }



    public function getItemPositionClass(string $timeline_position = null): ?string
    {
        $position_class = match ($timeline_position) {
            default                             => "t-left",
            CommonITILObject::TIMELINE_LEFT     => "t-left",
            CommonITILObject::TIMELINE_MIDLEFT  => "t-left t-middle",
            CommonITILObject::TIMELINE_MIDRIGHT => "t-left t-middle",
            CommonITILObject::TIMELINE_RIGHT    => "t-right",
        };

        return $position_class;
    }

    /**
     * Returns class for given (Task) state.
     */
    public function getStateClass(string $item_state = null): ?string
    {
        $state_class = match ($item_state) {
            default        => "",
            Planning::INFO => "info",
            Planning::TODO => "todo",
            Planning::DONE => "success",
        };

        return $state_class;
    }


    /**
     * Returns class for given (Solution|Validation) status.
     */
    public function getSolutionClass(string $itiltype = null, string $status = null): ?string
    {
        $solution_class = "";

        if (in_array($itiltype, ['ITILSolution', 'ITILValidation']) && $status !== null) {
            if ($itiltype === 'ITILSolution') {
                $status = str_replace('status_', '', $status);
            }

            $solution_class = match ($status) {
                default                        => "",
                CommonITILValidation::WAITING  => "waiting",
                CommonITILValidation::ACCEPTED => "accepted",
                CommonITILValidation::REFUSED  => "refused",
            };
        }

        return $solution_class;
    }


    /**
     * Returns timeline order based on user preference or general config.
     */
    public function isTimelineReversed(): bool
    {
        global $CFG_GLPI;
        $timeline_order = $_SESSION['glpitimeline_order'] ?? $CFG_GLPI['timeline_order'] ?? null;
        return $timeline_order == CommonITILObject::TIMELINE_ORDER_REVERSE;
    }


    /**
     * is the given user anonymous?
     */
    public function isAnonymUser(int $users_id = 0): bool
    {
        $anonymize_support_agents = Entity::getUsedConfig(
            'anonymize_support_agents',
            $_SESSION['glpiactive_entity']
        );
        return ($_SESSION['glpiactiveprofile']['interface'] ?? null) === 'helpdesk'
            && $users_id != $_SESSION['glpiID']
            && $anonymize_support_agents != Entity::ANONYMIZE_DISABLED;
    }


    /**
     * is the given entry a validation answer?
     */
    public function isValidationAnswer(array $entry = null): bool
    {
        return $entry !== null
            && str_ends_with($entry['type'], 'Validation')
            && $entry['item_action'] === 'validation-answer';
    }


    /**
     * is the timeline should display dates as relatives?
     */
    public function isTimelineDisplayRelativeDate(): bool
    {
        global $CFG_GLPI;

        $timeline_display_date = $_SESSION['glpitimeline_display_date'] ?? $CFG_GLPI['timeline_display_date'] ?? null;

        return $timeline_display_date == Config::TIMELINE_RELATIVE_DATE;
    }


    /**
     * can the given entry (and the parent object) be edited?
     */
    public function canEditTimelineEntry(array $entry = null, CommonITILObject $parent = null): bool
    {
        return $entry !== null
            && $entry['item'] !== null
            && $entry['item']['can_edit']
            && $parent !== null
            && !in_array($parent->fields['status'], $parent->getClosedStatusArray())
            && !in_array($entry['type'], ['Document_Item', 'Assign']);
    }
}
