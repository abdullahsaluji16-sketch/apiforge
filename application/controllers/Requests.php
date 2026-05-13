<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Requests extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_login();
        $this->load->model('Request_model');
    }

    public function save() {
        $uid = $this->user['id'];
        $id  = $this->input->post('id');

        $data = [
            'user_id'           => $uid,
            'collection_id'     => $this->input->post('collection_id') ?: null,
            'name'              => $this->input->post('name') ?: 'Untitled Request',
            'method'            => $this->input->post('method') ?: 'GET',
            'url'               => $this->input->post('url'),
            'headers'           => $this->input->post('headers'),
            'params'            => $this->input->post('params'),
            'body_type'         => $this->input->post('body_type') ?: 'none',
            'body_content'      => $this->input->post('body_content'),
            'auth_type'         => $this->input->post('auth_type') ?: 'none',
            'auth_data'         => $this->input->post('auth_data'),
            'pre_request_script'=> $this->input->post('pre_request_script'),
            'test_script'       => $this->input->post('test_script'),
        ];

        if ($id) {
            $this->Request_model->update($id, $uid, $data);
            $this->json(['success' => true, 'id' => $id]);
        } else {
            $new_id = $this->Request_model->create($data);
            $this->json(['success' => true, 'id' => $new_id]);
        }
    }

    public function load($id) {
        $req = $this->Request_model->get($id, $this->user['id']);
        if (!$req) {
            $this->json(['error' => 'Not found'], 404);
            return;
        }
        $this->json($req);
    }

    public function delete($id) {
        $this->Request_model->delete($id, $this->user['id']);
        $this->json(['success' => true]);
    }

    public function duplicate($id) {
        $req = $this->Request_model->get($id, $this->user['id']);
        if (!$req) { $this->json(['error' => 'Not found'], 404); return; }
        unset($req->id);
        $req->name = $req->name . ' (Copy)';
        $new_id = $this->Request_model->create((array)$req);
        $this->json(['success' => true, 'id' => $new_id]);
    }
}
