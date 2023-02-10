<?php

namespace Drupal\user_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

/**
 * Display user profile data.
 */

class UserDashboardForm extends FormBase{

	// Implements getFormId() to get form ID.

	public function getFormId(){
		return 'user_dashboard_form';
	}

	// Implements buildForm() to build the form.

	public function buildForm(array $form, FormStateInterface $form_state){
		
		$form['export'] = [
			'#type' => 'submit',
			'#value' => t('Export'),
			'#submit' => [[get_called_class(),'Export']],
		];

		$query = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type','user_profile');

		$name = $form_state->getValue('name');
		$gender = $form_state->getValue('gender');
		$age = $form_state->getValue('age');
		$header = ['First Name','Last Name','Gender','Age','Created'];
		$rows = [];
		$reset;

		// Filter condition to get the results

		if ($name == NULL && $gender == NULL && $age == NULL) {

			$query = $query;
			$reset = 0;

		}

		if (!empty($name)) {

			$query = $query->condition('field_first_name',$name,'CONTAINS');
			$reset = 1;

		}

		if (!empty($gender)) {

			$reset = 1;
			$query = $query->condition('field_gender',$gender);

		}

		if (!empty($age)) {

			$reset = 1;

			if( $age == '0-25') {

				$query = $query->condition('field_age',[0,25],'BETWEEN');

			}

			elseif ($age == '25-40') {

				$query = $query->condition('field_age',[26,40],'BETWEEN');

			}

			elseif ($age == '40-60') {

				$query = $query->condition('field_age',[41,60],'BETWEEN');
			}

			else{

				$query = $query->condition('field_age',60,'>');

			}
		}

		$ids = $query->pager(10)->execute();

		$user_profiles = Node::loadMultiple($ids);

		// Fetch the User profile data.
		
		foreach ($user_profiles as $user_profile) {

			$rows[] = [
				'first_name' => $user_profile->get('field_first_name')->value,
				'last_name' => $user_profile->get('field_last_name')->value,
				'Gender' => $user_profile->get('field_gender')->value,
				'age' => $user_profile->get('field_age')->value,
				'created' => \Drupal::service('date.formatter')->format((int)$user_profile->getCreatedTime(),'html_date')
			];

		}

		$form['name'] = [
			'#type' => 'textfield',
			'#title' => t('Name')
		];

		$form['gender'] = [
			'#type' => 'select',
			'#title' => t('Gender'),
			'#options' => [
				NULL => t('Any'), 
				'male' => t('Male'),
				'female' => t('Female'),
				'other' => t('Other')
			]
		];

		$form['age'] = [
			'#type' => 'select',
			'#title' => t('Age'),
			'#options' => [
				NULL => t('Any'), 
				'0-25' => t('0-25'),
				'25-40' => t('25-40'),
				'40-60' => t('40-60'),
				'above 60' => t('above 60')
			]
		];

		$form['search'] = [
			'#type' => 'submit',
			'#value' => t('Apply'),
		];

		if ($reset == 1) {

			$form['reset'] = [
			'#type' => 'submit',
			'#value' => t('Reset'),
			'#submit' => [[get_called_class(),'Reset']]
			];

		}

		// Implements Table View

		$form['user_table'] =[
			'#type' => 'table',
			'#cache' => ['max-age'=>0],
			'#header' => $header,
			'#rows' => $rows,
			'#empty' => 'No results found'
		];
		
		// Implements Pager

		$form['pager'] = [
			'#type' => 'pager'
		];

		return $form;

	}

	// Implements validateForm() to validate the form.

	public function validateForm(&$form, FormStateInterface $form_state){

	}

	// Implements submitForm() to execute the submit function.

	public function submitForm(&$form, FormStateInterface $form_state){

		// Set the values on current form state.

		$name = $form_state->getValue('name');
		$gender = $form_state->getValue('gender');
		$age = $form_state->getValue('age');
		$form_state->set('name',$name);
		$form_state->set('gender',$gender);
		$form_state->set('age',$age);
		$form_state->setRebuild(TRUE);
		return $form;

	}

	// Implements Reset() to reset the filters.

	public static function Reset(&$form, FormStateInterface $form_state){

		$url = Url::fromRoute('user_dashboard.page');
		$form_state->setRedirectUrl($url);

	}

	// Implements Export() to export the data.

	public static function Export(&$form, FormStateInterface $form_state){

		$filename = 'user_demo1.csv';

		// Implements the Custom export created from the custom module.

		\Drupal::service('user_dashboard.export')->export('user_profile',$filename);
        
        // file_url_generator service used to get the URL to download CSV file.

		$download_url = \Drupal::service('file_url_generator')->generateAbsoluteString('public://'.$filename);

	    \Drupal::messenger()->addMessage(t('<strong><a href="@link">Download CSV file</a></strong>', ['@link' => $download_url])
	    );

	}

}