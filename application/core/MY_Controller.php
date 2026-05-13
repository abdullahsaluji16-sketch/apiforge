<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    protected $user;

    public function __construct() {
        parent::__construct();
        $this->user = $this->session->userdata('user');
    }

    protected function require_login() {
        if (!$this->user) {
            redirect('login');
        }
    }

    protected function json($data, $status = 200) {
        http_response_code($status);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    protected function view($view, $data = []) {
        $data['user'] = $this->user;
        $this->load->view('layouts/header', $data);
        $this->load->view($view, $data);
        $this->load->view('layouts/footer', $data);
    }
}
