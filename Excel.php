<?php

use Maatwebsite\Excel\Facades\Excel as _Excel;

/**
 * @class Excel
 * @version 1.0.0
 * @author LuminEe
 * @date 2019-11-07
 */
trait Excel
{
    public function import($file, $title = false)
    {
        _Excel::import($import = new Import($title), $file, 'import');
        return $import->rows;
    }

    public function export($name, $record, $ext = '.xls')
    {
        return _Excel::download(new Export($record), $name . $ext);
    }

    public function sheetsExport($name, $record, $ext = '.xls')
    {
        return _Excel::download(new SheetsExport($record), $name . $ext);
    }

    public function store($file, $data, $ext = '.xls')
    {
        _Excel::store(new Export($data), $file . $ext, $disk = 'export');
        return $this->getDiskPath($disk) . '/' . $file . $ext;
    }

    public function sheetsStore($file, $data, $ext = '.xls')
    {
        _Excel::store(new SheetsExport($data), $file . $ext, $disk = 'export');
        return $this->getDiskPath($disk) . '/' . $file . $ext;
    }

    protected function getDiskPath($disk = 'local')
    {
        return config("filesystems.disks.{$disk}.root");
    }
}

use Maatwebsite\Excel\Concerns\ToArray;

class Import implements ToArray
{
    public $title;

    public $rows;

    public function __construct($with_title = false)
    {
        $this->title = $with_title;
    }

    public function array(array $rows)
    {
        if (count($rows) == 0) return [];
        if ($this->title) {
            $title = array_shift($rows);
            foreach ($rows as &$row) {
                $items = [];
                foreach ($row as $key => $item) {
                    $items[$title[$key]] = $item;
                }
                $row = $items;
            }
        }
        $this->rows = $rows;
        return true;
    }
}

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class Export implements FromCollection, WithTitle
{
    protected $data;
    protected $title;

    public function __construct($data, $title = '')
    {
        $this->data = collect($data);
        $this->title = $title;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }
}

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SheetsExport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    public function __construct($sheets)
    {
        $this->sheets = $sheets;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->sheets as $title => $data) {
            $sheets[] = new Export($data, $title);
        }

        return $sheets;
    }
}
