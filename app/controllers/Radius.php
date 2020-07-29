<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Don't forget include/define REST_Controller path
use chriskacerguis\RestServer\RestController;

/**
 *
 * Controller Radius
 *
 * This controller for ...
 *
 * @package   CodeIgniter
 * @category  Controller REST
 * @author    Setiawan Jodi <jodisetiawan@fisip-untirta.ac.id>
 * @author    Raul Guerrero <r.g.c@me.com>
 * @link      https://github.com/setdjod/myci-extension/
 * @param     ...
 * @return    ...
 *
 */

class Radius extends RESTController
{
    
	public function __construct()
	{
		parent::__construct();
		
		$this->load->model('radius_model');
	}

	public function index()
	{
		// 
	}

	public function index_get()
	{
		$location = $this->input->get('locationid', TRUE);
		$server	  = $this->input->get('server', TRUE);
		$limit	  = $this->input->get('limit', TRUE);

		$data = $this->radius_model->get_radius_client($location, $server, $limit);

		$this->response($data, 200);
		
    }

	public function index_post()
	{
		if ($this->_venue_form_check()==TRUE) {
			
			//Gen Shared Secret Key
			$sharedKey = 'P@ssw0rd';//random_string('alnum', 12);
			
			$data = array(
					'nasname' 	  => $this->input->post('nasname'),
					'shortname'   => $this->input->post('shortname'),
					'secret'	  => $sharedKey,
					'server' 	  => $this->input->post('server'),
					'community'   => $sharedKey,
					'description' => $this->input->post('description'),
					'lat' 		  => $this->input->post('lat'),
					'lng' 		  => $this->input->post('lng'),
					);
			
			$radresult = $this->radius_model->add_radius_client($data);
			
			if ($radresult['status']) {
			
				$response['success'][] = $radresult['output'].' Shared Secret '.$data['secret'];
				$response['success'][] = $this->radius_model->radiusd_reload();
				$this->response($response, 201);
				exit;
				
			} else {
				
				$this->db->delete('nas', array('nasname' => $this->input->post('nas')));			
				$response['error'][] = 'Add client Failed '.$radresult;
				$this->response($response, 404);
				exit;
		
			} 
			
		} else {

			$response['error'] = $this->form_validation->error_array();
			$this->response($response, 404);
			exit;      
		}
	}

	function index_put()
	{
		if ($this->_venue_form_check(TRUE)) { //is_update == TRUE

			$data = array(
					'id'		  => $this->input->get('id'),
					'nasname' 	  => $this->input->get('nasname'),
					'shortname'   => $this->input->get('shortname'),
					'server' 	  => $this->input->get('server'),
					'description' => $this->input->get('description'),
					'lat' 		  => $this->input->get('lat'),
					'lng' 		  => $this->input->get('lng'),
					);
			
			$radresult = $this->radius_model->upd_radius_client($data);
			
			if($radresult['status'])
			{			
				$response['success'][] = 'Updating Venue/NAS '.$data['shortname'];
				$response['success'][] = $radresult['output'];
				$response['success'][] = $this->radius_model->radiusd_reload();
				$response['success'][] = $this->radius_model->radiusd_status();
				$this->response($response, 200);
				exit;
				
			} else {
				
				$response['error'][] = $radresult['output'];
				$this->response($response, 404);
				exit;
			}
		}
		else
		{
			$response['error'] = $this->form_validation->error_array();
			$this->response($response, 404);
			exit;	
		}
	}

	function index_delete()
	{
		$nasid 	 = $this->input->post_get('id');
		$nasname = $this->input->post_get('nas');
		
		if($nasid==NULL)
		{
			$response['error'] = 'With no expected NAS ID ';
			$this->response($response, 404);
			exit;
		}
		
		$radresult = $this->radius_model->del_radius_client($nasid);

		if($radresult['status'])
		{
			$response['success'] = 'NAS '.$nasname.' has been deleted';
			$response['reload']  = $this->radius_model->radiusd_reload();
			$response['radiusd'] = $this->radius_model->radiusd_status();
			$this->response($response, 200); 
			exit;
		}
		else
		{
			$response['error'] = $radresult['output'];
			$this->response($response, 404); 
			exit;
		}	
  	}
  
  	function _venue_form_check($is_update = FALSE)
	{
    	if ($is_update) {
			$this->form_validation->set_data($this->input->get());
			$this->form_validation->set_rules('id', 'Unique ID', 'trim|required');
		} else {
			$this->form_validation->set_rules('nasname', 'WAN IP address', 'trim|required|is_unique[nas.nasname]|valid_ip[ipv4]');
			
    	}
    	$this->form_validation->set_rules('shortname', 'Name', 'trim|required');
		$this->form_validation->set_rules('server', 'Server', 'trim|required');
		$this->form_validation->set_rules('description', 'Full Address', 'trim|required');
		$this->form_validation->set_rules('lat', 'Latitude', 'trim|required|numeric');
		$this->form_validation->set_rules('lng', 'Longitude', 'trim|required|numeric');
		
		return $this->form_validation->run();
	}

}


/* End of file Radius.php */
/* Location: ./application/controllers/Radius.php */