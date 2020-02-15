<?php
/*
 * Maps EBS leaflet fields to Drupal fields
 * Specifies which EBS fields should be 
 * used to search for existing nodes.
 * Specifies additional processing functions for certain fields
 * 
 */

class LeafletFieldMapping {
	
	public $fieldMapping;
	
	function __construct(){
		
		
		
		/*
		subject_area how does this relate to topic_topic_code
		*/
		$this->fieldMapping = array(

		//EBS fieldname => array(Drupal Field machine name, function to use, default value)
				
		'full_description' => array('title', 'nodeCoreTextField', ''),
		'leaflet_code' => array('field_leaflet_code', 'nodeTextField', ''),
		'ucas_code' => array('field_ucas_code', 'nodeTextField', ''),
		'kis_code' => array('field_kis_code', 'nodeTextField', ''),
		'sites_moa' => array('field_attendance', 'nodeTextField', ''),
		'awarding_body' => array('field_awarding_body', 'nodeTextField', ''),
		'location_tids' => array('field_locations', 'nodeTaxTermField', ''),
		'subject_area_tids' => array('field_subject_areas', 'nodeTaxTermField', ''),
		'passions' => array('field_passions',  'nodeTaxTermField', ''),
		'qualification_tids' => array('field_qualifications', 'nodeTaxTermField', ''),
		'level_tids' => array('field_course_levels',  'nodeTaxTermField', ''),
		'prosp_user_3' => array('field_keywords', 'nodeTextField', ''),
		'updated_date' => array('field_ebs_updated_date', 'nodeTextField', ''),
		//'prosp_user_1' => array('field_prosp_user_1', 'nodeTextField', ''),
		'prosp_user_2' => array('field_application_type', 'nodeTextField', ''),
		//'prosp_user_3' => array('field_prosp_user_3', 'nodeTextField', ''),
		'prosp_user_4' => array('field_places_available', 'nodeTextField', ''),
		'prosp_user_5' => array('field_overview', 'nodeTextField', ''),
		'prosp_user_6' => array('field_got_a_question', 'nodeTextField', ''),
		//'prosp_user_7' => array('field_prosp_user_7', 'nodeTextField', ''),
		'prosp_user_8' => array('field_entry_requirements', 'nodeTextField', ''),
		'prosp_user_9' => array('field_assessed', 'nodeTextField', ''),
		//'prosp_user_10' => array('field_prosp_user_10', 'nodeTextField', ''),
		'prosp_user_11' => array('field_progressions_pathway', 'nodeTextField', ''),
		'prosp_user_12' => array('field_fees_finance_funding', 'nodeTextField', ''),
		'fes_user_1' => array('field_segment', 'nodeTextField', '')
		);		
		
		

		
	}//end __construct
	
}