<?php

/**
 * webtrees: online genealogy application
 * Copyright (C) 2025 webtrees development team
 *                    <https://webtrees.net>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * A webtrees (https://webtrees.net) 2.1 and 2.2 custom module
 * to show historic events in the timeline of a person in the tree (using several languages).
 * Data is based on a Gramps gramplet: see https://github.com/kajmikkelsen/HistContext
 */

declare(strict_types=1);

namespace Hartenthaler\WebtreesModules\History\gramps_historical_facts;

use Hartenthaler\Webtrees\Helpers\Functions;
use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleHistoricEventsTrait;
use Fisharebest\Webtrees\Module\ModuleHistoricEventsInterface;
use Fisharebest\Webtrees\Registry;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseFactoryInterface;
use Fig\Http\Message\StatusCodeInterface;

use function substr;
use function file_exists;
use function preg_match_all;
use function strlen;

/**
 * tbd: allow admin to select some data files in Control Panel
 * tbd: read defined headers and show meta information for the admin in Control Panel
 */

class GrampsHistoricalFacts extends AbstractModule implements ModuleCustomInterface, ModuleHistoricEventsInterface {
    use ModuleCustomTrait;
    use ModuleHistoricEventsTrait;

    // title of custom module
    public const CUSTOM_TITLE      = 'Gramps Historical Facts';

    // name of custom module
    public const CUSTOM_MODULE     = 'gramps-historical-facts';

    // name of custom module author
    public const CUSTOM_AUTHOR     = 'Hermann Hartenthaler';

    // custom module version
    public const CUSTOM_VERSION    = '2.2.1.0';

    // GitHub user name
    public const GITHUB_USER       = 'hartenthaler';

    // GitHub repository
    public const GITHUB_REPO       = self::GITHUB_USER . '/' . self::CUSTOM_MODULE;

    // GitHub API URL to get the information about the latest releases
    public const GITHUB_API_LATEST_VERSION  = 'https://api.github.com/repos/'. self::GITHUB_REPO . '/releases/latest';
    public const GITHUB_API_TAG_NAME_PREFIX = '"tag_name":"v';

    // GitHub website as information for admins
    public const CUSTOM_WEBSITE    = 'https://github.com/' . self::GITHUB_REPO . '/';

    /**
     * Constructor.  The constructor is called on *all* modules, even ones that are disabled.
     * This is a good place to load business logic ("services").  Type-hint the parameters and
     * they will be injected automatically.
     */
    public function __construct()
    {
        // NOTE:  If your module is dependent on any of the business logic ("services"),
        // then you would type-hint them in the constructor and let webtrees inject them
        // for you.  However, we can't use dependency injection on anonymous classes like
        // this one. For an example of this, see the example-server-configuration module.

        // use helper function in order to work with webtrees versions 2.1 and 2.2
        $response_factory = Functions::getFromContainer(ResponseFactoryInterface::class);
    }

    /**
     * Bootstrap.  This function is called on *enabled* modules.
     * It is a good place to register routes and views.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * How should this module be identified in the control panel, etc.?
     *
     * @return string
     */
    public function title(): string
    {
        return self::CUSTOM_TITLE;
    }

    /**
     * A sentence describing what this module does.
     *
     * @return string
     */
    public function description(): string
    {
        return /* I18N: Description of this module */ I18N::translate('Historical facts (in several languages) - provided by Gramps');
    }

    /**
     * The person or organisation who created this module.
     *
     * @return string
     */
    public function customModuleAuthorName(): string
    {
        return self::CUSTOM_AUTHOR;
    }

    /**
     * The version of this module.
     *
     * @return string
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * Where to get support for this module. Perhaps a GitHub repository?
     *
     * @return string
     */
    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_WEBSITE;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \ModuleCustomInterface::customModuleLatestVersion
     */
    public function customModuleLatestVersion(): string
    {
        // No update URL provided.
        if (self::GITHUB_API_LATEST_VERSION === '') {
            return $this->customModuleVersion();
        }
        return Registry::cache()->file()->remember(
            $this->name() . '-latest-version',
            function (): string {
                try {
                    $client = new Client(
                        [
                            'timeout' => 3,
                        ]
                    );

                    $response = $client->get(self::GITHUB_API_LATEST_VERSION);

                    if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                        $content = $response->getBody()->getContents();
                        preg_match_all('/' . self::GITHUB_API_TAG_NAME_PREFIX . '\d+\.\d+\.\d+/', $content, $matches, PREG_OFFSET_CAPTURE);

                        if(!empty($matches[0]))
                        {
                            $version = $matches[0][0][0];
                            $version = substr($version, strlen(self::GITHUB_API_TAG_NAME_PREFIX));
                        }
                        else
                        {
                            $version = $this->customModuleVersion();
                        }
                        return $version;
                    }
                } catch (GuzzleException $ex) {
                    // Can't connect to the server?
                }

                return $this->customModuleVersion();
            },
            86400
        );
    }

    /**
     * Should this module be enabled when it is first installed?
     *
     * @return bool
     */
    public function isEnabledByDefault(): bool
    {
        return true;
    }

    /**
     * Where does this module store its resources
     *
     * @return string
     */
    public function resourcesFolder(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
    }

    /**
     * Where are the data files stored
     *
     * @return string
     */
    public function dataFolder(): string
    {
        return $this->resourcesFolder() . 'data' . DIRECTORY_SEPARATOR;
    }

    /**
     * Additional/updated translations.
     *
     * @param string $language
     *
     * @return string[]
     */
    public function customTranslations(string $language): array
    {
        $lang_dir   = $this->resourcesFolder() . 'lang/';
        $file       = $lang_dir . $language . '.mo';
        if (file_exists($file)) {
            return (new Translation($file))->asArray();
        } else {
            return [];
        }
    }

    /**
     * Load list of historic events from a csv file
     *
     * There is no heading line as comment.
     * Each line has the same structure (columns separated by semicolon)
     * - from date
     * - to date
     * - event (text)
     * - link to event (URL)
     */
    public function loadCsvFile(string $folderPath, string $fileName, string $delimiter): Collection
    {
        // path to CSV file
        $filePath = $folderPath . $fileName;

        $collection = new Collection();

        // open file and read lines
        if (($handle = fopen($filePath, 'r')) !== false) {
            // read CSV lines and process them
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                // add row to Collection
                $result = $this->processCsvLine($row);
                if (!empty($result)) $collection->push($result);
            }
            fclose($handle);
        }

        return $collection;
    }

    /**
     * Process a CSV row and transform it into a structured array.
     * Ignore a comment line starting with "#" (return empty array)
     *
     * @param array $row The CSV line as an array of values.
     * @return array Processed data from the CSV row.
     */
    function processCsvLine(array $row): array
    {
        // check if first character in first element of $row is "#" (comment line)
        if (isset($row[0]) && str_starts_with($row[0], '#')) {
            return [];
        }

        // Extract values from the row
        list($fromDate, $toDate, $event, $link) = $row;
        $toDate = str_replace('Today', '', $toDate);

        // Return the processed row as structured data
        return [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'event' => $event,
            'link' => $link,
        ];
    }

    /**
     * Structure of events provided by this module:
     * 
     * Each line is a GEDCOM style record to describe an event (EVEN), including newline chars (\n);
     *      1 EVEN <event>
     *      2 TYPE Historic event
     *      2 DATE <date period>
     *      2 NOTE [link](<link> )
     *
     * Markdown is used for NOTE:
     * Markdown should be enabled for your tree (see Control panel / Manage family trees / Preferences
     * and then scroll down to "Text" and mark the option "markdown");
     * if Markdown is disabled the links are still working (blank at the end is necessary), but the formatting isn't so nice;
     *
     * @param string $language_tag
     */
    public function historicEventsAll(string $language_tag = "en"): Collection
    {
        $eventType = I18N::translate('Historic event');
        $gedcomList = new Collection();

        // load data from all csv files
        $fileList = $this->listCsvFiles($this->dataFolder());
        foreach ($fileList as $file) {
            $eventsList = $this->loadCsvFile($this->dataFolder(), $file, ";");

            // generate GEDCOM records for events based on the data from the csv file
            foreach ($eventsList as $event) {
                $gedcomList->push(
                    "1 EVEN " . $event['event'] .
                    "\n2 TYPE " . $eventType .
                    "\n2 DATE " . "FROM " . $event['fromDate'] . " TO " . $event['toDate'] .
                    "\n2 NOTE " . "[link](" . $event['link'] . ") "
                );
            }
        }
        return $gedcomList;
    }

    /**
     * List all .csv files in a given folder.
     *
     * @param string $folderPath The path to the folder.
     * @return array An array of .csv file names in the folder or an empty array if none found.
     */
    function listCsvFiles(string $folderPath): array
    {
        // Überprüfen, ob der Ordner existiert
        if (!is_dir($folderPath)) {
            return []; // Leeres Array zurückgeben, wenn der Ordner nicht existiert
        }

        // Dateien im Ordner durchsuchen und nur .csv-Dateien ermitteln
        $csvFiles = [];
        foreach (scandir($folderPath) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                $csvFiles[] = $file; // Nur .csv-Dateinamen hinzufügen
            }
        }

        return $csvFiles;
    }
}
