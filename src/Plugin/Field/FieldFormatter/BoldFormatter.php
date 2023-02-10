<?php

namespace Drupal\user_dashboard\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Field\FieldItemListInterface;


/**
 * Plugin implementation for Bold format to all plain text.
 *
 * @FieldFormatter(
 *   id = "bold_text",
 *   label = @Translation("Bold text"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */

class BoldFormatter extends StringFormatter{

	/**
     * {@inheritdoc}
     */

	public function viewElements(FieldItemListInterface $items, $langcode){

		$elements = [];

		foreach ($items as $delta => $item) {

			$elements[$delta] = [
				'#markup' => '<b>'.$item->value.'</b>'
			];

		}

		return $elements;
	}
	
}