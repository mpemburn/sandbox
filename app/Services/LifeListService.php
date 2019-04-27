<?php
/**
 * Created by PhpStorm.
 * User: mpemburn
 * Date: 4/26/19
 * Time: 9:24 AM
 */

namespace App\Services;

/**
 * Class LifeListService
 * @package App\Services
 */
class LifeListService
{
    protected $dataPath;
    protected $aouList;
    protected $currentList;
    protected $newList;
    protected $resultList = [];
    protected $debug;

    /**
     * LifeListService constructor.
     * @param $currentList
     * @param $newList
     */
    public function __construct($currentList, $newList, $debug = false)
    {
        $this->debug = $debug;

        $this->dataPath = storage_path() . '/Data/';
        $this->aouList = $this->getAouList();
        $this->currentList = $this->getList($currentList);
        $this->newList = $this->getList($newList);

        $this->parseLists();
        $this->writeResults();
    }

    protected function parseLists()
    {
        $removed = $this->currentList;
        $count = 0;
        $newCount = 0;
        foreach ($this->aouList as &$species) {
            $commonName = $species->common_name;
            $key = trim(strtoupper($commonName));
            if (array_key_exists($key, $this->currentList)) {
                $this->currentList[$key]->Species = $commonName;
                $this->resultList[] = $this->currentList[$key];
            } else if (array_key_exists($key, $this->newList)) {
                $this->newList[$key]->Species = $commonName;
                $this->resultList[] = $this->newList[$key];
                $newCount++;
            }
            if ($this->debug && array_key_exists($key, $this->currentList)) {
                unset($removed[$key]);
                $count++;
            }
        }
        if ($this->debug) {
            echo $count . ' valid species names.<br>';
            echo $newCount . ' Life Birds.<br>';
            var_dump($removed);
        }
    }

    protected function writeResults()
    {
        $path = $this->dataPath . 'Life List ' . date('Y-m-d', time()) . '.csv';
        $fp = fopen($path, 'w');

        $header = 'Sort,Species,Date,Location,Notes' . PHP_EOL;
        fwrite($fp, $header);

        $count = 1;
        foreach ($this->resultList as $species) {
            $row = $count++ . ',';
            $row .= trim($species->Species) . ',';
            $row .= $species->Date . ',';
            $row .= '"' . $this->csvify($species->Location) . '",';
            $row .= '"' . $this->csvify($species->Notes) . '"' . PHP_EOL;

            fwrite($fp, $row);
        }

        fclose($fp);
    }

    /**
     * @return array|null
     */
    protected function getAouList()
    {
        $path = $this->dataPath . 'AOU_List.csv';
        $csvData = $this->csvToArray($path);

        return $csvData;
    }

    /**
     * @return array|null
     */
    protected function getList($listName)
    {
        $path = $this->dataPath . $listName . '.csv';
        $csvData = $this->csvToArray($path);

        return $this->makeKeyArray($csvData);
    }

    /**
     * @param string $filename
     * @param string $delimiter
     * @return array|bool
     */
    function csvToArray($filename='', $delimiter=',')
    {
        if(!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            $hasSort = false;
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
            {
                if(!$header) {
                    $header = $row;
                    if (in_array('Sort', $header)) {
                        array_shift($header);
                        $hasSort = true;
                    }
                } else {
                    if ($hasSort) {
                        array_shift($row);
                    }
                    try {
                        $data[] = (object)array_combine($header, $row);
                    } catch (\Exception $e) {
                        var_dump($header);
                        var_dump($row);
                    }
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * @param $csvData
     * @return array
     */
    protected function makeKeyArray($csvData)
    {
        $keyArray = [];
        foreach ($csvData as $row) {
            $key = trim($row->Species);
            $key = strtoupper(str_replace("’", "'", $key));
            $keyArray[$key] = $row;
        }

        return $keyArray;
    }

    protected function csvify($text)
    {
        return str_replace('"', '""', trim($text));
    }
}