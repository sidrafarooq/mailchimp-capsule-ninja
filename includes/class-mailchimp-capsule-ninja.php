<?php

class MailChimp_Capsule_Ninja{

    private function log($message)  {
        if (true === WP_DEBUG) {
            if (is_array($message) || is_object($message)) {
                error_log(print_r($message, true));
            } else {
                error_log($message);
            }
        }
    }

    private function mailchimp_url($action, $params){

        $key = $params['apikey'];

        if(!empty($key)){

            $parts = explode('-',$key);
            
            $datacenter = end($parts);

            if ($datacenter){
                switch($action){
                    case 'subscribe':
                        $url = 'https://'. $datacenter . '.api.mailchimp.com/2.0/lists/subscribe.json';
                        break;
                    case 'unsubscribe':
                        $url = 'https://'. $datacenter . '.api.mailchimp.com/2.0/lists/unsubscribe.json';       
                        break;
                }
                return $url;
            }

        }

        return FALSE;

    }

    private function mailchimp_subscribe($email, $params){

        $url = $this->mailchimp_url('subscribe', $params);

        $data = json_encode($params);

        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45, 
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => TRUE,
            'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
            'body' => $data, 
        ));

        if(!is_wp_error($response)){
            
            $result = json_decode($response['body']);

            if ($result && $result->email == $email){
                return TRUE;
            }

        }

        return FALSE;

    }

    private function mailchimp_unsubscribe($email, $params){

        $url = $this->mailchimp_url('unsubscribe', $params);

        $data = json_encode($params);

        $response = wp_remote_post($url, array(
            'method' => 'POST', 
            'timeout' => 45, 
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => TRUE,            
            'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
            'body' => $data, 
        ));

        if(!is_wp_error($response)){
            
            $result = json_decode($response['body']);

            if ($result && $result->complete){
                return TRUE;
            }

        }

        return FALSE;
    }

    private function process_mailchimp($data){

        //Get option values
        $options = get_option('mailchimp_capsule_options_values');

        //Get the api request parameters
        $params = array(
            'apikey' => $options['mailchimp_key'],
            'id' => $options['mailchimp_listid'],
            'email' => array(
                'email' => $data['email'],
            ),
        );

        //Attempt to subscribe
        return $this->mailchimp_subscribe($data['email'], $params);

    }

    private function capsule_find_person_by_email($email){

        //Get option values
        $options = get_option('mailchimp_capsule_options_values');

        try{            
            $capsule = new Services_Capsule($options['capsule_user'], $options['capsule_token']);

            $response = $capsule->party->getListFilterEmail($email); 

            if ($response && isset($response->parties->person)){
                if (is_array($response->parties->person)){
                    return reset($response->parties->person);
                }else{
                    return $response->parties->person;
                }
            }

        }catch(Services_Capsule_Exception $ex){

            $this->log($ex);

        }

        return FALSE;

    }

    private function capsule_get_person_id($response){

        $location = explode('/',$response->http->getHeader('location'));
        
        if (is_numeric(end($location))){
            return end($location);
        }

        return FALSE;

    }

    private function capsule_tag_person($person, $tag){

        //Get option values
        $options = get_option('mailchimp_capsule_options_values');

        try{

            $capsule = new Services_Capsule($options['capsule_user'], $options['capsule_token']);  

            $response = $capsule->party->tag->add($person,$tag);

            if ($response && (isset($response->status) && $response->status)){
                return TRUE;
            }

        }catch (Services_Capsule_Exception $ex){

            $this->log($ex);

        }

        return FALSE;

    }

    private function capsule_add_person($data){

        try{

            //Get option values
            $options = get_option('mailchimp_capsule_options_values');

            //Create the capsule connection
            $capsule = new Services_Capsule($options['capsule_user'], $options['capsule_token']);  

            //Check if the data contains the name field and if the field has a space in it
            if (isset($data['name']) && (strpos($data['name'],' ') !== FALSE)){
                
                //Contains space so split into forename and surname values
                list($forename, $surname) = explode(' ',$data['name'],2);

            }else{

                //Doesnt contain space so just set the forename field
                $forename = $data['name'];
                $surname = NULL;

            }

            //Check if the forename is not empty and assign to params
            if (!empty($forename)){
                $params['firstName'] = $forename;
            }

            //Check if the surname is not empty and assign to params
            if (!empty($surname)){
                $params['lastName'] = $surname;
            }

            //Check if the company is not empty and assign to organisation name field
            if (isset($data['company']) && !empty($data['company'])){
                $params['organisationName'] = $data['company'];
            }

            //Check if the email is not empty and assign to contacts email
            if (isset($data['email']) && !empty($data['email'])){
                $params['contacts']['email'] = array(
                    'type' => 'Work',
                    'emailAddress' => $data['email'],
                );
            }

            //Check if the telephone field is not empty and assign to contacts phone
            if (isset($data['telephone']) && !empty($data['telephone'])){
                $params['contacts']['phone'] = array(
                    'type' => 'Work',
                    'phoneNumber' => $data['telephone'],
                );
            }

            //Check if the website address is provided and if so assign to contacts website
            if (isset($data['website']) && !empty($data['website'])){
                $params['contacts']['website'][] = array(
                    'type' => 'Work',
                    'webService' => 'URL',
                    'webAddress' => $data['website'],
                );              
            }

            //Try to add the personto capsule
            $response = $capsule->person->add($params);
            
            //If there was a response and it was positive
            if ($response && $response->status){
                
                //Check to see if there is a tag field 
                if (isset($data['tag']) && !empty($data['tag'])){

                    //Get the person just added from the response information
                    $pid = $this->capsule_get_person_id($response);

                    //If the person id was got
                    if ($pid){

                        //Tag the person with the tag provided
                        if ($this->capsule_tag_person($pid, $data['tag'])){
                            return $pid;
                        }else{
                            $this->log('Unable to tag person');                   
                        }

                    }else{
                        $this->log('Unable to get person id, or was not numeric');                    
                    }

                }else{
                    $this->log('Unable to tag person, no tag set');
                }

            }else{
                $this->log('Unable to create user in Capsule');
            }

        }catch (Services_Capsule_Exception $ex){

            $this->log($ex);

        }

        return FALSE;

    }

    private function capsule_add_task($pid, $data){

        //Get option values
        $options = get_option('mailchimp_capsule_options_values');

        $params['description'] = "check contact created by the drop box";
        $params['dueDate'] = date('Y-m-d'); 
        $params['detail'] = "Contact for " . $data['email'] . " added or ammended";

        try{

            $capsule = new Services_Capsule($options['capsule_user'], $options['capsule_token']);  

            $response = $capsule->party->task->add($pid, $params);

            //If there was a response and it was positive
            if ($response && $response->status){
                return TRUE;
            }

        }catch (Services_Capsule_Exception $ex){

            $this->log($ex);

        }

        return FALSE;            

    }

    private function process_capsule($data){
        return $this->capsule_add_person($data);
    }


    public function process_submission(){

        //Get the form processing object
        global $ninja_forms_processing;

        //Get option values
        $options = get_option('mailchimp_capsule_options_values');

        //Get all the user submitted values
        $fields = $ninja_forms_processing->get_all_fields();

        //If the fields is array
        if(is_array($fields)){

            //Create array of data
            $data = array();

            //Loop through each of our submitted values.
            foreach( $fields as $id => $value ){

                //If the value isnt empty
                if (!empty($value)){

                    //Get the field information
                    $settings = $ninja_forms_processing->get_field_settings($id);

                    //Get the label (name) of the field
                    $label = strtolower($settings['data']['label']);

                    //Create the data array
                    $data[$label] = $value;

                }

            }

			//Check if the service variable contains the string mailchimp, if so process
			if (stripos(strtolower($data['service']),'mailchimp') !== FALSE){

				//Process mail chimp subscription
				if($this->process_mailchimp($data)){
					$this->log('Subscribed address to mailchimp list');
				}else{
					$this->log('Unable to subscribe user to mailchimp list');
				}

			}
			
			//Check if the service variable contains the string capsule, if so process			
			if (stripos(strtolower($data['service']),'capsule') !== FALSE){
			
				//Check if the person already exists in capsule and get their info
				$person = $this->capsule_find_person_by_email($data['email']);

				//If the person doesn't exist
				if (!$person){

					//Process sending of data to capsule, add the new person
					if ($pid = $this->process_capsule($data)){
		
        				$this->log('Added person to capsule');
        
                        //Add a task to capsule
                        if ($this->capsule_add_task($pid, $data)){
                            $this->log('Added task to capsule for person');
                        }else{
                            $this->log('Failed adding task to capsule');
                        }

					}else{
						$this->log('Unable to add person to capsule');
					}

				}else{

                    //Tag an existing person
					if ($this->capsule_tag_person($person->id, $data['tag'])){
						$this->log('Existing person tagged');
					}else{
						$this->log('Unable to tag existing person');                    
					}

                    //Add a task to capsule
                    if ($this->capsule_add_task($person->id, $data)){
                        $this->log('Added task to capsule');
                    }else{
                        $this->log('Failed adding task to capsule');
                    }

				}

			}

        }

    }

    public function setup(){
        add_action('ninja_forms_process',array($this,'process_submission'));        
    }

}