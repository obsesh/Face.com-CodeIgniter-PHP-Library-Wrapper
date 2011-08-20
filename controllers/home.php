<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {
	public function __construct()
	{
		parent::__construct();
	}

	public function process() 
	{
		$this->load->library('upload');
		$this->load->helper('url');
		$this->load->library('Face');

		if ( ! $this->upload->do_upload())
		{
			$error = array('error' => $this->upload->display_errors());
			print_r($error);
		}
		else
		{
			$data = array('upload_data' => $this->upload->data());
			$path = base_url() . "uploads/" . $data['upload_data']['file_name'];
			

			$auth = $this->face->__call('account_authenticate');
			$face = $this->face->__call('faces_detect', $path);
			print_r($face);
		}
	}

	public function index()
	{

	}
}

/* End of file home.php */
/* Location: ./application/controllers/home.php */