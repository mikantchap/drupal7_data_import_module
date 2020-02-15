<?php
/*
 * Updates and creates Drupal leaflets
 */

 class DrupalLeaflets  {
 	
 	public $goodEbsLeaflets;//incoming EBS values
 	public $fieldmapping;//what is says
 	
 	//The fields to use when deciding which (if any) Drupal leaflet
 	//should be updated by an EBS leaflet. Derivative of $fieldmapping 
 	//public $comparisonFields;
 	
 	//Each array has the Drupal fields and values to use when
 	//searching for existing nodes
 	//public $searchValues;
 	
 	public $log = array();
 	
 	//Whether we actually need to to update; all the values might be the same.
 	public $nodeNeedsUpdate = array();
 	
 	function __construct($goodEbsLeaflets, $fieldMapping){
 		$this->log[] = "";
 		
 		$this->goodEbsLeaflets = $goodEbsLeaflets;
 		$this->fieldMapping = $fieldMapping;
 		
 		$this->possibleTidValues = array();
 		
 		$this->getPossibleTidValues();

 	}
 	
 	/*
 	 * get possible tid values for the tax term fields
 	 */
 	function getPossibleTidValues(){
 		foreach ($this->fieldMapping as $ebsFieldName => $myArray):
 		
	 		if ($myArray[1] == 'nodeTaxTermField'):
	 		
	 			//Get possible tids for this field
				$info = field_info_field($myArray[0]);
				$vocab_machine_name = $info['settings']['allowed_values'][0]['vocabulary'];
				$vocab = taxonomy_vocabulary_machine_name_load($vocab_machine_name);
				$tree = taxonomy_get_tree($vocab->vid);
						
				$thisFieldsPossibleTids = array();
				foreach ($tree as $key => $branch):
					$thisFieldsPossibleTids[] = $branch->tid;
				endforeach;
	
				$this->taxInfo['possibleTidValues'][$myArray[0]] = $thisFieldsPossibleTids;
				$this->taxInfo['vocab_machine_name'][$myArray[0]] = $vocab_machine_name;
				$this->taxInfo['vocab_human_name'][$myArray[0]] = $vocab->name;
	 		endif;
 		
 		endforeach; 
 	}
 	
 	/*
 	 * Determines whether the incoming EBS leaflet value already exists in Drupal
 	 *  (do an update) or not (create a new leaflet node) 
 	 */
 	function updateCreate(){
 		
 		//dsm($this->goodEbsLeaflets);
 		foreach($this->goodEbsLeaflets as $leafletCode => $goodEbsLeaflet):
 		
 			//dsm($leafletCode);	
 		
	 		$query = new EntityFieldQuery();
	 		$query->entityCondition('entity_type', 'node')
	 		->entityCondition('bundle', 'leaflet')
	 		->fieldCondition('field_leaflet_code', 'value', $leafletCode, '=');
	 		
	 		$result = $query->execute();
	 		
	 		
	 		if (count($result) == 0):
	 			//dsm("Didn't find an existing node - its a create");
	 				
	 			$node = new stdClass();
	 			$this->setNodeValues($node, $this->goodEbsLeaflets[$leafletCode]);
	 				
	 			// Prepare node for saving. Does creation date and node->uid
	 			$node = node_submit($node);
	 			//dsm($node);
	 			node_save($node);
	 				
	 			$this->log[] = get_class($this).": Created node id ".$node->nid." ".$leafletCode." ".$node->title;
	 				 		
	 		elseif (count($result) == 1):
	 			//dsm('Its an update for '.$leafletCode. 'with nid = '.$leaflet_nid[0]);
	 			$leaflet_nid = array_keys($result['node']);
	 			$node = node_load($leaflet_nid[0]);
	 		
	 			//$link = l("node id $leaflet_nid[0]", "node/$leaflet_nid[0]", array('attributes' => array('target'=>'_blank')));
	 		
	 			//Start with the assumption that no update is required
	 			//Individual functions that populate a field may flag
	 			//that an update IS required for this node
	 			$this->nodeNeedsUpdate[$leaflet_nid[0]] = false;
	 				
	 			$oldStatus = $node->status;
	 			
	 			$this->setNodeValues($node, $this->goodEbsLeaflets[$leafletCode]);
	 			
	 			//republishing a node
	 			if ($node->status != $oldStatus):
	 				$this->nodeNeedsUpdate[$leaflet_nid[0]] = true;
	 			endif;
	 			
	 			/*
	 			if ($node->nid == '14196'){
	 				dsm($node);
	 			}*/
	 			
	 			//if set to manual, don't update
	 			if ($node->field_source['und'][0]['value'] == 'manual'):
	 				$this->nodeNeedsUpdate[$leaflet_nid[0]] = false;
	 				$this->log[] = get_class($this).": Didn't update ('manual' set) node id ".$node->nid." ".$leafletCode." ".$node->title;
	 			endif;
	 			
	 			
	 			//dsm($node);
	 			// Prepare node for saving
	 			//Does creation date and node->uid
	 			
	 			$node = node_submit($node);
	 				
	 			if ($this->nodeNeedsUpdate[$leaflet_nid[0]] == true){
	 				node_save($node);
	 				$this->log[] = get_class($this).": Updated node id ".$node->nid." ".$leafletCode." ".$node->title;
	 			} else {
	 				$this->log[] = get_class($this).": No changes for node id ".$node->nid." ".$leafletCode." ".$node->title;
	 			}
	 				
	 		elseif (count($result) > 1):
	 			//dsm('duplicates leaflets. Report, do nothing.');
	 		endif;
 		
 		
 		endforeach; 		
 	}
 	
 
 	
 	/*
 	 * Sets the node value with the appropriate function 
 	 * from the mapping
 	 */
 	function setNodeValues(&$node, $ebsLeafletValues){
 		
 		global $user;
 			
 		//create only
 		$node->type = "leaflet";
 		node_object_prepare($node); // Sets some defaults. Invokes hook_prepare() and hook_node_prepare().
 		$node->language = LANGUAGE_NONE; // Or e.g. 'en' if locale is enabled
 		$node->uid = $user->uid; //set to 1?
 		$node->promote = 0; //(1 or 0): promoted to front page
 		$node->comment = 0; // 0 = comments disabled, 1 = read only, 2 = read/write
 		$node->sticky = 0;  // (1 or 0): sticky at top of lists or not
 		
 		//Static values, not from EBS
 		$node->status = 1;
 		$node->field_source[$node->language][0]['value'] = "automated";
 		$node->path['pathauto'] = true;
 		
 		/*
 		 * Loop through the field mapping, retrieving:
 		 * 
 		 * $myArray[0] is Drupal field name
 		 * $myArray[1] is function name to use
 		 * $myArray[2] is default value to use
 		 * $ebsLeafletValues[$ebsFieldName] is the value of the field
 		 * contained in $this->goodEbsLeaflets
 		 *  field_weeks_per_year
 		 */
 		//dsm($this->fieldMapping);
 		//dsm($ebsLeafletValues);
 		 foreach ($this->fieldMapping as $ebsFieldName => $myArray):
 		 	//dsm($ebsLeafletValues->$ebsFieldName." ".$myArray[0]);
 		 
 		 	$valueToUse = $ebsLeafletValues->$ebsFieldName;
 		 	
 		 	if (empty($valueToUse)){
 		 		$valueToUse = $myArray[2];//subsitute default value from mapping
 		 	}

 		 	//Set node->field value, or delete existing value
 		 	$this->$myArray[1]($node, $myArray[0], $valueToUse);
	 	
 		 endforeach; 

 	}
 	
 	/*
 	 * Handles text, long text, integer, select type fields
 	*/
 	function nodeTextField(&$node, $field, $value){
 		if (!empty($node->nid)){
 			//its an existing node...
 	
 			if (!isset($node->{$field}[$node->language][0]['value']) && strlen($value)> 0){
 				$this->nodeNeedsUpdate[$node->nid] = true;
 				//dsm(__FUNCTION__." flagging ".$node->nid);
 				//dsm($node->{$field}[$node->language][0]['value']." ".$value." ".$field);
 			}
 	
 			/*
 			 * Don't need this. If its an existing node, the set value != a blank incoming value
 			* This is always true
 			if (isset($node->{$field}[$node->language][0]['value']) && strlen($value)== 0){
 			$this->nodeNeedsUpdate[$node->nid] = true;
 			dsm(__FUNCTION__." flagging ".$node->nid);
 			dsm($node->{$field}[$node->language][0]['value']." ".$value." ".$field);
 			}*/
 	
 	
 			if ((isset($node->{$field}[$node->language][0]['value']) &&	$node->{$field}[$node->language][0]['value'] != $value)){
 				//...with a new incoming value
 				$this->nodeNeedsUpdate[$node->nid] = true;
 				//dsm(__FUNCTION__." flagging ".$node->nid);
 				//dsm($node->{$field}[$node->language][0]['value']." ".$value." ".$field);
 			}
 		}
 			
 		if ($value == ""){
 			unset($node->{$field}[$node->language]);
 		}else{
 			$node->{$field}[$node->language][0]['value'] = $value;
 		}
 	}
 	
 	/*
 	 * Handles text fields in core eg title
 	*/
 	function nodeCoreTextField(&$node, $field, $value){
 		if (!empty($node->nid)){
 			//its an existing node...
 			if (!isset($node->{$field}) || $node->{$field} != $value){
 				//...with a new incoming value
 				$this->nodeNeedsUpdate[$node->nid] = true;
 				//dsm(__FUNCTION__." flagging ".$node->nid);
 			}
 		}
 			
 		$node->{$field} = $value;
 	}
 	
 	/*
 	 * 2 values
 	 $node->field_passions['und'][0]['tid'] = 125
 	 $node->field_passions['und'][1]['tid'] = 125
 	 
 	 0 values
 	 $node->field_passions  
 	 */
 	
 	/*
 	 * Handles the taxonomy term reference fields.
 	 */
 	function nodeTaxTermField(&$node, $field, $value){
		
 		//split piped value in array
 		$values = explode('|', $value);
 		sort($values);


 		$thisFieldsPossibleTids = $this->taxInfo['possibleTidValues'][$field];
 		$vocab_machine_name = $this->taxInfo['vocab_machine_name'][$field];
 		$vocab_human_name = $this->taxInfo['vocab_human_name'][$field];
 		
 		//passions is a special case. The ebs_leaflets_final table
 		//'passions' field contains piped names (not tids)
 		if ($field == 'field_passions'):
 		//dsm($values);
 			//convert to tids
 			$valuesTids = array();
 		
 			foreach($values as $key => $value):
 			
	 			if (strlen($value) > 0)://adjacent pipes or no values at all
		 			$terms = taxonomy_get_term_by_name($value, $vocab_machine_name);
		 			$term = array_shift($terms);
	 			
	 				if (isset($term) && is_numeric($term->tid)):  
	 					$valuesTids[] = $term->tid;
	 				else:
		 				$this->log[] = get_class($this).": Couldn't find \"".$value."\" in ".$vocab_human_name." taxonomy for ".$node->field_leaflet_code['und'][0]['value']." ".$node->title;
	 				endif;			
				endif;
				
 			endforeach;
 			 			 			
 			$values = $valuesTids;

 		endif;

 		//Remove duff values
 		foreach ($values as $key => $value):
 			$badValue = false;
 			if (strlen($value) > 0 && !in_array($value, $thisFieldsPossibleTids)):

	 			$badValue = true;
 			$this->log[] = get_class($this).": Couldn't find \"".$value."\" in ".$vocab_human_name." taxonomy for ".$node->field_leaflet_code['und'][0]['value']." ".$node->title;
	 		endif;
	 		
	 		if (strlen($value) == 0):
	 			$badValue = true;

	 		endif;
	 		
	 		if ($badValue == true):
	 			unset($values[$key]);
	 		endif;

	 		
 		endforeach;
 		
 		
 		
 	 		 				
 		if (!empty($node->nid)){
 			//its an existing node...
 			
 			if (!isset($node->{$field}[$node->language][0]['tid']) && count($values)> 0){
 				//...but for some reason the existing node had no values set
 				$this->nodeNeedsUpdate[$node->nid] = true;
 				//dsm(__FUNCTION__." flagging ".$node->nid);
 				//dsm($node->{$field}[$node->language][0]['tid']." ".$value." ".$field);
 			}
 			
 			if ((isset($node->{$field}[$node->language][0]['tid']) &&	(!$this->sameTaxTermValues($node, $field, $values))) ){
 				//...with amended incoming values to existing values
 				$this->nodeNeedsUpdate[$node->nid] = true;
 				//dsm(__FUNCTION__." flagging ".$node->nid);
 				//dsm($node->{$field}[$node->language][0]['tid']." ".$value." ".$field);
 			}
 		}
 		
 		unset($node->{$field}[$node->language]);

 		if (count($values) > 0){
 			foreach ($values as $key => $value):
 				//if (strlen($value) > 0):
 				$node->{$field}[$node->language][$key]['tid'] = $value;
 				//endif;
 			endforeach;
 		}
 	}
 	
 	/*
 	 * Compare existing tids for a node with incoming new values from EBS
 	 * If they are the same returns true, else false
 	 */
 	function sameTaxTermValues($node, $field, $newValues){
 		//$node->field_leaflet_location['und'][0]['tid']
 		//$node->field_leaflet_location['und'][1]['tid']
 		$existingValues = array();
 		foreach ($node->{$field}[$node->language] as $key => $existingValue):
 			$existingValues[] = $existingValue['tid']; 		
 		endforeach;
 		sort($existingValues);
 		sort($newValues);
 		
 		if ($existingValues == $newValues){
 			return true;
 		}else{
 			return false;
 		}
 	}
 	
 	
 }