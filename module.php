<?php

/**
 * webtrees: online genealogy application
 * Copyright (C) 2025 webtrees development team
 *                    <https://webtrees.net>
 *
 * GrampsHistoricalFacts (webtrees custom module):
 * Copyright (C) 2025 Hermann Hartenthaler
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 *
 * A webtrees (https://webtrees.net) 2.1 and 2.2 custom module
 * to show historic events in the timeline of a person in the tree.
 * Data is based on a Gramps gramplet: see https://github.com/kajmikkelsen/HistContext
 * 
 */
 

declare(strict_types=1);

namespace Hartenthaler\WebtreesModules\History\gramps_historical_facts;

use Composer\Autoload\ClassLoader;

$loader = new ClassLoader();

// this webtrees custom module
$loader->addPsr4('Hartenthaler\\WebtreesModules\\History\\gramps_historical_facts\\', __DIR__);

// my helper functions for webtrees custom modules
$loader->addPsr4('Hartenthaler\\Webtrees\\Helpers\\', __DIR__ . "/vendor/Hartenthaler/Webtrees/Helpers");

$loader->register();

return new GrampsHistoricalFacts();
