<?php
namespace mad\tools;

require __dir__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MadExcel implements IteratorAggregate {
	private $file;

	private $rows = 0;

	private $header = [];
	private $list = [];

	function __construct( $file = '' ) {
		$this->file = $file;
		if( is_file( $file ) ) {
			$this->load($file);
		}
	}

	function load($file, $header=[]) {
		$this->file = $file;
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->file);
		$worksheet = $spreadsheet->getActiveSheet();

		$rownum = 0;
		foreach ($worksheet->getRowIterator() as $row) {
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,

			$row = [];
			foreach ($cellIterator as $cell) {
				$value = $cell->getValue();
				if( is_null($value) ) {
					$value = '';
				}
				$row[] = $value;
			}

			if( $rownum == 0 ) {
				$rownum = 1;
				$this->header = empty($header) ? $row : $header;
				continue;
			}

			$this->list[] = array_combine($this->header, $row);
		}
		return $this;
	}

	function getIterator(): \Traversable {
		return new ArrayIterator( $this->list );
	}

	function setHeader( $header ) {
		$this->header = $header;
		return $this;
	}
	function setList( $list ) {
		$this->list = $list;
		return $this;
	}

	function list() {
		return $this->list;
	}

	function makeSheet() {
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		$rv = [$this->header];
		foreach( $this->list as &$row ) {
			$rv[] = (array)$row;
		}
		$this->rows = count($rv);
		$sheet->fromArray($rv); 
		return $spreadsheet;
	}

	function save( $file ) {
		$spreadsheet = $this->makeSheet();

		$writer = new Xlsx($spreadsheet);
		$writer->save($file);
		return $this->rows;
	}

	function output() {
		$spreadsheet = $this->makeSheet();

		$writer = new Xlsx($spreadsheet);
		MadRouter::getInstance()->download($this->file, 'excel');
		return $writer->save('php://output');
	}
}
