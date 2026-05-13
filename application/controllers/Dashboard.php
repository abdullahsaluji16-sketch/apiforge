<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_login();
        $this->load->model(['Collection_model', 'Request_model', 'Environment_model', 'History_model', 'Tab_model', 'Workspace_model']);
    }

    public function index() {
        $uid = $this->user['id'];

        $data['collections']  = $this->Collection_model->get_with_requests($uid);
        $data['environments'] = $this->Environment_model->get_all($uid);
        $data['active_env']   = $this->Environment_model->get_active($uid);
        $data['env_vars']     = $this->Environment_model->get_active_vars($uid);
        $data['history']      = $this->History_model->get_recent($uid, 10);
        $data['tabs']         = $this->Tab_model->get_user_tabs($uid);
        $data['stats']        = [
            'total_requests' => $this->History_model->count_all($uid),
            'collections'    => $this->Collection_model->count($uid),
            'avg_time'       => $this->History_model->avg_response_time($uid),
            'success_rate'   => $this->History_model->success_rate($uid),
        ];
        $data['user'] = $this->session->userdata('user');
        $data['workspaces'] = $this->Workspace_model->get_all($uid);

        $this->load->view('layouts/header', $data);
        $this->load->view('dashboard/main', $data);
        $this->load->view('layouts/footer', $data);
    }
}
