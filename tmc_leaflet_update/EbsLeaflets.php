<?php

/*
 * Get new leaflets as array from MySQL database
 * Sort leaflets into good and bad
 */

class EbsLeaflets {

	public $settings;
	//public $information;
	public $leaflets = array();
	public $goodLeaflets;
	public $badLeaflets;
	//public $fieldsToCheck;
	public $log = array();
	

	function __construct($settings){
		$this->rowsmin = $settings['ROWSMIN'];
		//$this->information = "";
		$this->getAllLeaflets();
		
		$this->fieldsToCheck = array();			
		$this->fieldsToCheck[] = 'full_description';
		$this->fieldsToCheck[] = 'leaflet_code';
		$this->fieldsToCheck[] = 'sites_moa';//attendance
		//$this->fieldsToCheck[] = 'awarding_body';
		//$this->fieldsToCheck[] = 'location_tids';
		$this->fieldsToCheck[] = 'subject_area_tids';
		$this->fieldsToCheck[] = 'passions';
		$this->fieldsToCheck[] = 'qualification_tids';
		$this->fieldsToCheck[] = 'level_tids';	
		$this->fieldsToCheck[] = 'prosp_user_2';
		$this->fieldsToCheck[] = 'prosp_user_5';
		$this->fieldsToCheck[] = 'prosp_user_6';
		$this->fieldsToCheck[] = 'prosp_user_8';
		$this->fieldsToCheck[] = 'prosp_user_9';
		$this->fieldsToCheck[] = 'prosp_user_11';
		$this->fieldsToCheck[] = 'prosp_user_12';
		
		
		
		$this->goodLeaflets = array();
		$this->badLeaflets = array();
		$this->sortLeaflets();
		//dsm($this->leaflets);
		}
		
		//SELECT *  FROM `ebs_leaflets_final` WHERE `passions` != ''
	
	function getAllLeaflets(){
		//$queryEbsLeaflets = db_select('ebs_leaflets_final_static', 'elf')
		$queryEbsLeaflets = db_select('ebs_leaflets_final', 'elf')
		->fields('elf');
		//->condition('leaflet_code', 'L16302');//Plastering
		//->range(0,10);
		$results = $queryEbsLeaflets->execute();
		//dsm($results->rowCount());
		
		foreach ($results as $row) :
			$this->leaflets[$row->leaflet_code] = $row;
		endforeach;
		
	} 
	
	function sortLeaflets(){
		
		foreach($this->leaflets as $leaflet):
				$badFields = false;
				
			//if any of the important fields are empty, flag it and break 
			foreach ($this->fieldsToCheck as $key => $fieldname):
				
				if (empty($leaflet->$fieldname)){
					//dsm($leaflet->leaflet_code.": ".$fieldname);
					$badFields = true;
					break;
				}	
			endforeach; 
			
			if ($badFields){
				$this->badLeaflets[] = $leaflet;
			}else{
				$this->goodLeaflets[] = $leaflet;   	
			}

		endforeach;
		
	}

}