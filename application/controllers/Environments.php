<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Environments extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_login();
        $this->load->model('Environment_model');
    }

    public function index() {
        $envs = $this->Environment_model->get_all($this->user['id']);
        $this->json($envs);
    }

    public function get($id) {
        $env  = $this->Environment_model->get($id, $this->user['id']);
        $vars = $this->Environment_model->get_vars($id);
        $this->json(['env' => $env, 'vars' => $vars]);
    }

    public function save() {
        $uid  = $this->user['id'];
        $id   = $this->input->post('id');
        $name = $this->input->post('name');
        $vars = json_decode($this->input->post('vars') ?? '[]', true);

        if ($id) {
            $this->Environment_model->update($id, $uid, ['name' => $name]);
        } else {
            $id = $this->Environment_model->create(['user_id' => $uid, 'name' => $name, 'workspace_id' => 1]);
        }

        // Save variables
        $this->Environment_model->delete_vars($id);
        foreach ($vars as $v) {
            if (!empty($v['key'])) {
                $this->Environment_model->add_var($id, $v['key'], $v['value'], $v['enabled'] ?? 1);
            }
        }

        $this->json(['success' => true, 'id' => $id]);
    }

    public function set_active($id) {
        $uid = $this->user['id'];
        $this->Environment_model->set_active($id, $uid);
        $vars = $this->Environment_model->get_vars($id);
        $this->json(['success' => true, 'vars' => $vars]);
    }

    public function delete($id) {
        $this->Environment_model->delete($id, $this->user['id']);
        $this->json(['success' => true]);
    }
}
