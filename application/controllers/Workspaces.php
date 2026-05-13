<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Workspaces extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_login();
        $this->load->model('Workspace_model');
    }

    public function index() {
        $workspaces = $this->Workspace_model->get_all($this->user['id']);
        $this->json($workspaces);
    }

    public function store() {
        $uid  = $this->user['id'];
        $name = $this->input->post('name');
        $desc = $this->input->post('description');

        if (empty($name)) {
            $this->json(['error' => 'Name required'], 422);
            return;
        }

        $id = $this->Workspace_model->create([
            'user_id'     => $uid,
            'name'        => $name,
            'description' => $desc,
        ]);

        $this->json(['success' => true, 'id' => $id, 'name' => $name, 'description' => $desc]);
    }

    public function delete($id) {
        $this->Workspace_model->delete($id, $this->user['id']);
        $this->json(['success' => true]);
    }

    public function get_active() {
        $uid = $this->user['id'];
        $ws  = $this->Workspace_model->get_active($uid);
        $this->json($ws ?: ['id' => 1]);
    }

    public function set_active($id) {
        $this->Workspace_model->set_active($id, $this->user['id']);
        $this->json(['success' => true]);
    }
}
