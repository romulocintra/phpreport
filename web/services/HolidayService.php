<?php
/*
 * Copyright (C) 2021 Igalia, S.L. <info@igalia.com>
 *
 * This file is part of PhpReport.
 *
 * PhpReport is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PhpReport is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PhpReport.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Phpreport\Web\services;

if (!defined('PHPREPORT_ROOT')) define('PHPREPORT_ROOT', __DIR__ . '/../../');

include_once(PHPREPORT_ROOT . '/web/services/WebServicesFunctions.php');
include_once(PHPREPORT_ROOT . '/model/facade/UsersFacade.php');
include_once(PHPREPORT_ROOT . '/model/vo/UserVO.php');
require_once(PHPREPORT_ROOT . '/util/LoginManager.php');

class HolidayService
{
    private \LoginManager $loginManager;

    public function __construct(
        \LoginManager $loginManager
    ) {
        $this->loginManager = $loginManager;
    }

    /** Group dates into date ranges
     *
     * It receives an array of dates in the ISO format YYYY-MM-DD
     * and group them into date ranges if they are close by 1 day.
     * 
     * Single dates are converted into a range with the same start
     * and end.
     * 
     * Examples:
     * 
     * 1. the array ['2021-01-01', '2021-01-02'] should return a
     * single range of dates that starts in 2021-01-01 and ends in
     * 2021-01-02: 
     * [
     *    [
     *        'start' => '2021-01-01',
     *        'end' => '2021-01-02'
     *    ]
     * ]
     * 
     * 2. ['2021-01-01'] will be converted in a range with the same
     * start and end date:
     * [
     *    [
     *        'start' => '2021-01-01',
     *        'end' => '2021-01-01'
     *    ]
     * ]
     */
    public function datesToRanges(array $vacations): array
    {
        if (count($vacations) == 0) {
            return [];
        }

        $start = $vacations[0];
        $last_date = $start;
        $ranges = array();
        for ($i = 0; $i < count($vacations); $i++) {
            $previousDate = date_create($last_date);
            $currentDate = date_create($vacations[$i]);
            $interval = date_diff($previousDate, $currentDate);

            if ($interval->days > 1) {
                $ranges[] = ['start' => $start, 'end' => $last_date];
                $start = $vacations[$i];
                // If it's the last date of the array, it is not part of the range
                // so it should also be added as a new range
                if ($i + 1 == count($vacations)) {
                    $ranges[] = ['start' => $vacations[$i], 'end' => $vacations[$i]];
                }
            } elseif ($i + 1 == count($vacations)) {
                // If it's the last element and the interval is 1 or 0, it means it's a single
                // element array, so we should create a range for it 
                $ranges[] = ['start' => $start, 'end' => $vacations[$i]];
            }
            $last_date = $vacations[$i];
        }
        return $ranges;
    }

    public function getUserVacationsRanges(string $init = NULL, string $end = NULL, $sid = NULL): array
    {
        if (!$this->loginManager::isLogged($sid)) {
            return ['error' => 'User not logged in'];
        }

        if (!$this->loginManager::isAllowed($sid)) {
            return ['error' => 'Forbidden service for this User'];
        }

        $dateFormat = "Y-m-d";

        if ($init != "") {
            $initParse = date_parse_from_format($dateFormat, $init);
            $init = "{$initParse['year']}-{$initParse['month']}-{$initParse['day']}";
        } else
            $init = "1900-01-01";

        $init = date_create($init);

        if ($end != "") {
            $endParse = date_parse_from_format($dateFormat, $end);
            $end = "{$endParse['year']}-{$endParse['month']}-{$endParse['day']}";
            $end = date_create($end);
        } else
            $end = date_create(date('Y') . "-12-31");

        $userVO = new \UserVO();
        $userVO->setLogin($_SESSION['user']->getLogin());

        $vacations = \UsersFacade::GetScheduledHolidays($init, $end, $userVO);

        return ['dates' => $vacations, 'ranges' => $this->datesToRanges($vacations)];
    }
}
