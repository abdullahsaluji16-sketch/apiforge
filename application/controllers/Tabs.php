<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tabs extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_login();
        $this->load->model('Tab_model');
    }

    public function save() {
        $uid = $this->user['id'];
        $data = [
            'request_id' => $this->input->post('request_id') ?: null,
            'tab_name'   => $this->input->post('tab_name') ?: 'New Request',
            'method'     => $this->input->post('method') ?: 'GET',
            'url'        => $this->input->post('url'),
            'is_active'  => 1,
            'tab_order'  => $this->input->post('tab_order') ?: 0,
        ];
        $id = $this->Tab_model->save($uid, $data);
        $this->Tab_model->set_active($id, $uid);
        $this->json(['success' => true, 'id' => $id]);
    }

    public function load() {
        $tabs = $this->Tab_model->get_user_tabs($this->user['id']);
        $this->json($tabs);
    }

    public function close($id) {
        $this->Tab_model->close($id, $this->user['id']);
        $this->json(['success' => true]);
    }
}
