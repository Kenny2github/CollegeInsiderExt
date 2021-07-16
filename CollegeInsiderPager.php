<?php

class CollegeInsiderPager extends ReverseChronologicalPager {

	private $type;
	private $lang;

	public $mDefaultLimit = 9;

	public function __construct( $type, $lang ) {
		$this->type = $type;
		$this->lang = $lang;
		parent::__construct();
	}

	public function getQueryInfo() {
		return [
			'tables' => [
				'ci' => 'collegeinsider',
				'cl' => 'categorylinks'
			],
			'fields' => [
				'page_id',
				'thumbnail',
				'blurb'
			],
			'conds' => [
				'cl_to' => str_replace( ' ', '_', wfMessage(
					'collegeinsider-type-' . $this->type
				)->text() ) . 's',
				'lang' => $this->lang
			],
			'join_conds' => [
				'cl' => [ 'INNER JOIN', 'page_id=cl_from' ]
			]
		];
	}

	public function getIndexField() {
		return 'datestamp';
	}

	public function formatRow( $row ) {
		return CollegeInsiderHooks::renderRow( $row );
	}
}