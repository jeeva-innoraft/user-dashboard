<?php

namespace Drupal\user_dashboard;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Core\Batch\BatchBuilder;

/**
 * Provides Export service with Batch operation.
 */

class ExportService{


	// Implements export() to get the data from content type and save data with custom filename.

	public function export($content_type, $filename){

		$query = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type',$content_type);
		
		$ids = $query->execute();

		// Implements BatchBuilder to make export data as batch operation.

		$batch = new BatchBuilder();
		$batch->setTitle(t('Building export CSV...'))
        ->setInitMessage(t('Initializing.'));
        $batch->addOperation([get_called_class(),'processItems'], [$ids, $content_type, $filename]);
		$batch->setProgressMessage(t('Completed @current of @total.'))->setErrorMessage(t('An error has occurred.'));
		$batch->setFile(\Drupal::service('extension.list.module')->getPath('user_dashboard') . '/src/Form/UserDashboardForm.php');
    	batch_set($batch->toArray());
		
	}

	// Implements processItems() to make the csv file to data from nodes.

	public static function processItems($ids, $content_type, $filename, &$context){

		// entity_field.manager service to get fields from content type.

		$definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $content_type);
		$fields = [];
		$contents = [];
		$labels = [];

		// Fetching field labels.

		foreach (array_keys($definitions) as $k => &$field_name) {

			$new = str_replace('_',' ', $field_name);
			
			if (strpos($new,'field')!==false) {

				$labels[] = ucwords(str_replace('field ','',$new));

			}
		}

		$contents[0] = $labels;
		$nodes = Node::loadMultiple($ids);

		// Fetching field datas

		foreach ($nodes as $node){

			$fields = [];

			foreach (array_keys($definitions) as &$field_name) {

				$new = str_replace('_',' ', $field_name);
				if (strpos($new,'field')!==false) {

					$fields[] = $node->get($field_name)->value;

				}
			}

			$contents[] = $fields;

		}

		// Save data as CSV

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=".$filename);
		header("Pragma: no-cache");
		header("Expires: 0");

		$output = fopen("public://".$filename, "w+");

	    foreach ($contents as $row) {

	        fputcsv($output, $row);

	    }

	    fclose($output);

	}
}