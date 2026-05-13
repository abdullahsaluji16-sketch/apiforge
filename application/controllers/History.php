<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class History extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_login();
        $this->load->model('History_model');
    }

    public function index() {
        $history = $this->History_model->get_all($this->user['id']);
        $this->json($history);
    }

    public function load($id) {
        $item = $this->History_model->get($id, $this->user['id']);
        $this->json($item);
    }

    public function clear() {
        $this->History_model->clear_all($this->user['id']);
        $this->json(['success' => true]);
    }

    public function delete($id) {
        $this->History_model->delete($id, $this->user['id']);
        $this->json(['success' => true]);
    }
}
