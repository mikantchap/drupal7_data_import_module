<?php
/*
 * For current Drupal leaflets, find if they exist in
 * the update. If not unpublish or delete node.
 */

class DeleteLeaflets {

	public $removeAction;//whether to delete or unpublish
	//public $leafletFieldMapping;//EBS field => Drupal field mapping
	//public $nidCompositeFieldValues;//Array [node ids] vs composite values
	public $allExistingLeaflets;//Collection of nodes
	public $goodEbsLeaflets;//Array of incoming leaflets from EBS
	public $log = array();
	
	function __construct($settings, $goodEbsLeaflets){
		$this->log[] = "";
		
		$this->removeAction = $settings['removeAction'];
		if ($this->removeAction != 'unpublish' &&  $this->removeAction != 'delete'){
			$this->removeAction = 'unpublish';
		}
		$this->goodEbsLeaflets = $goodEbsLeaflets;
		$this->allExistingLeaflets = array();
		$this->getAllLeaflets();
		$this->removeDrupalLeaflets();

	}
	
	/*
	 * Get all published leaflets
	*/
	function getAllLeaflets(){

		$queryLeaflets = new EntityFieldQuery();
	
		$queryLeaflets->entityCondition('entity_type', 'node')
		->entityCondition('bundle', 'leaflet')
		->propertyCondition('status', 1);
		//->fieldCondition('field_source', 'value', 'automated', '!=');
		//->fieldCondition('field_source', 'value', 'manual', '!=');
		//and where leaflet code not blank


		$results = $queryLeaflets->execute();
	

		if (isset($results['node'])) {
			
			foreach ($results['node'] as $nid => $object):
			
			$node = node_load($nid);
			$this->allExistingLeaflets[$node->field_leaflet_code['und'][0]['safe_value']] = $node;
			endforeach;

		}

		
	}

	/*
	 * Compare Drupal leaflets with incoming EBS leaflets
	 * If the Drupal value is absent from the incoming leaflet
	 * data, delete/unpublish the node in Drupal. 
	 */
	function removeDrupalLeaflets(){
		
		$this->leafletsToRemove = array_diff_key($this->allExistingLeaflets, $this->goodEbsLeaflets);
		
		//dsm($this->leafletsToRemove);
		foreach ($this->leafletsToRemove as $leafletCode => $object):
		//dsm("testing for node id ".$nid." with value ".$value);
				$nid = $object->nid;
					
		
				if ($this->removeAction == 'delete'){
					//delete node
				    $this->log[] = get_class($this).": Deleting node id ".$nid." ".$this->leafletsToRemove[$leafletCode]->title." (no longer in ".EXTERNAL_TABLE.")";
					node_delete($nid);
				}else if ($this->removeAction == 'unpublish'){
					//unpublish node here
					
					//release the url alias
					$path = path_load(array('source' => 'node/'.$nid));					
					path_delete($path['pid']);
										
					$nodeToRemove = $this->leafletsToRemove[$leafletCode];
					$nodeToRemove->path['pathauto'] = FALSE;
					$nodeToRemove->status = 0;
					node_save($nodeToRemove);
					
					$this->log[] = get_class($this).": Unpublished node id ".$nid." ".$nodeToRemove->title." (no longer in ".EXTERNAL_TABLE.")";
				}
		endforeach;

	}
	

}