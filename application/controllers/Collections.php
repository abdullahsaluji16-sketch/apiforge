<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Collections extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_login();
        $this->load->model('Collection_model');
    }

    public function index() {
        $data['collections'] = $this->Collection_model->get_with_requests($this->user['id']);
        $this->json($data['collections']);
    }

    public function store() {
        $uid = $this->user['id'];
        $name = $this->input->post('name');
        $icon = $this->input->post('icon') ?: '📦';

        if (empty($name)) {
            $this->json(['error' => 'Name required'], 422);
            return;
        }

        // Get user's actual workspace instead of hardcoded 1
        $this->load->model('Workspace_model');
        $ws = $this->Workspace_model->get_first($uid);
        $workspace_id = $ws ? $ws->id : 1;

        $id = $this->Collection_model->create([
            'user_id'      => $uid,
            'workspace_id' => $workspace_id,
            'name'         => $name,
            'icon'         => $icon,
        ]);

        $this->json(['success' => true, 'id' => $id, 'name' => $name, 'icon' => $icon]);
    }

    public function delete($id) {
        $this->Collection_model->delete($id, $this->user['id']);
        $this->json(['success' => true]);
    }
}
