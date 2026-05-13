<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_login();
        $this->load->model('User_model');
    }

    public function index() {
        $data['profile'] = $this->User_model->get($this->user['id']);
        $this->view('profile/index', $data);
    }

    public function update() {
        $uid  = $this->user['id'];
        $name = $this->input->post('name');
        $pass = $this->input->post('password');

        $update = ['name' => $name];
        if ($pass) $update['password'] = password_hash($pass, PASSWORD_DEFAULT);

        $this->User_model->update($uid, $update);

        // Update session
        $user = $this->session->userdata('user');
        $user['name'] = $name;
        $this->session->set_userdata('user', $user);

        $this->json(['success' => true]);
    }
}
