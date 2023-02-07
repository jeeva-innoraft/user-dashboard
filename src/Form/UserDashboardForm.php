<?php

namespace Drupal\user_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Batch\BatchBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserDashboardForm extends FormBase{

	public function getFormId(){
		return 'user_dashboard_form';
	}

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
		if($name == NULL && $gender  == NULL && $age == NULL){
			$query = $query;
			$reset = 0;
		}
		if(!empty($name)){
			$query = $query->condition('field_first_name',$name,'CONTAINS');
			$reset = 1;
		}if (!empty($gender)) {
			$reset = 1;
			$query = $query->condition('field_gender',$gender);
		}
		if(!empty($age)){
			$reset = 1;
			if($age == '0-25'){
				$query = $query->condition('field_age',[0,25],'BETWEEN');
			}elseif ($age == '25-40') {
				$query = $query->condition('field_age',[26,40],'BETWEEN');
			}
			elseif ($age == '40-60') {
				$query = $query->condition('field_age',[41,60],'BETWEEN');
			}
			else{
				$query = $query->condition('field_age',60,'>');
			}
		}
		$ids = $query->execute();

		$user_profiles = Node::loadMultiple($ids);
		
		foreach($user_profiles as $user_profile){
			$rows[] = ['first_name' => $user_profile->get('field_first_name')->value,'last_name' => $user_profile->get('field_last_name')->value,'Gender' => $user_profile->get('field_gender')->value, 'age'=>$user_profile->get('field_age')->value,'created'=>\Drupal::service('date.formatter')->format((int)$user_profile->getCreatedTime(),'html_date')];
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

		if($reset == 1){
			$form['reset'] = [
			'#type' => 'submit',
			'#value' => t('Reset'),
			'#submit' => [[get_called_class(),'Reset']]
			];
		}
		$form['user_table'] =[
			'#type' => 'table',
			'#cache' => ['max-age'=>0],
			'#header' => $header,
			'#rows' => $rows,
			'#empty' => 'No results found'
		];

		return $form;
	}

	public function validateForm(&$form, FormStateInterface $form_state){

	}

	public function submitForm(&$form, FormStateInterface $form_state){
		$name = $form_state->getValue('name');
		$gender = $form_state->getValue('gender');
		$age = $form_state->getValue('age');
		$form_state->set('name',$name);
		$form_state->set('gender',$gender);
		$form_state->set('age',$age);
		$form_state->setRebuild(TRUE);
		return $form;
	}
	public static function Reset(&$form, FormStateInterface $form_state){
		$url = Url::fromRoute('user_dashboard.page');
		$form_state->setRedirectUrl($url);
	}

	public static function Export(&$form, FormStateInterface $form_state){
		$query = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type','user_profile');
		$ids = $query->execute();
		$batch = new BatchBuilder();
		$batch->setTitle(t('Building export CSV...'))
        ->setInitMessage(t('Initializing.'));
        $batch->addOperation([get_called_class(),'processItems'], [$ids]);
		$batch->setProgressMessage(t('Completed @current of @total.'))->setErrorMessage(t('An error has occurred.'));
		$batch->setFile(\Drupal::service('extension.list.module')->getPath('user_dashboard') . '/src/Form/UserDashboardForm.php');
    	$batch->setFinishCallback([get_called_class(),'finished']);
    	batch_set($batch->toArray());
	}

	public static function processItems($ids, &$context){
		$user_profiles = Node::loadMultiple($ids);
		$profiles = [];
		$profiles[0] = ['First Name', 'Last Name', 'Gender', 'Age', 'Created'];
		foreach($user_profiles as $user_profile){
			$profiles[] = [$user_profile->get('field_first_name')->value,$user_profile->get('field_last_name')->value,$user_profile->get('field_gender')->value,$user_profile->get('field_age')->value,\Drupal::service('date.formatter')->format((int)$user_profile->getCreatedTime(),'html_date')]; 
		}
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=user_profile.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		$output = fopen("public://user_profile1.csv", "w+");
	    foreach ($profiles as $row) {
	        fputcsv($output, $row);
	    }
	    fclose($output);
	}

	public static function finished(){
  		$url = \Drupal::service('file_url_generator')->generateAbsoluteString('public://user_profile.csv');
        \Drupal::messenger()->addMessage(t('<strong><a href="@link">Download CSV file</a></strong>', ['@link' => $url])
        );
        $response = new RedirectResponse('/user-profile-dashboard');
  		$response->send();
	}


}