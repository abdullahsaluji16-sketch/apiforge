<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper(['form', 'url']);
        $this->load->library('session');
        $this->config->load('google_oauth');
    }

    // ─── Normal Login ────────────────────────────────────────────
    public function login() {
        if ($this->session->userdata('user')) redirect('dashboard');
        $this->load->view('auth/login');
    }

    public function do_login() {
		echo "in";exit;
        $email    = $this->input->post('email');
        $password = $this->input->post('password');
        $user     = $this->User_model->get_by_email($email);

        if ($user && password_verify($password, $user->password)) {
            $this->_set_session($user);
            redirect('dashboard');
        } else {
            $this->session->set_flashdata('error', 'Invalid email or password');
            redirect('login');
        }
    }

    // ─── Normal Register ─────────────────────────────────────────
    public function register() {
        if ($this->session->userdata('user')) redirect('dashboard');
        $this->load->view('auth/register');
    }

    public function do_register() {
        $name     = $this->input->post('name');
        $email    = $this->input->post('email');
        $password = $this->input->post('password');

        if ($this->User_model->get_by_email($email)) {
            $this->session->set_flashdata('error', 'Email already exists');
            redirect('register');
            return;
        }

        $avatar = strtoupper(substr($name, 0, 1)) .
                  strtoupper(substr(explode(' ', $name)[1] ?? 'U', 0, 1));

        $id = $this->User_model->create([
            'name'     => $name,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'avatar'   => $avatar,
        ]);

        $this->load->model('Workspace_model');
        $this->Workspace_model->create(['user_id' => $id, 'name' => 'My Workspace']);

        $this->session->set_flashdata('success', 'Account created! Please login.');
        redirect('login');
    }

    // ─── Google OAuth ────────────────────────────────────────────
    public function google_login() {
        $client_id    = $this->config->item('google_client_id');
        $redirect_uri = $this->config->item('google_redirect_uri');
        $scopes       = $this->config->item('google_scopes');

        // Generate state token to prevent CSRF
        $state = bin2hex(random_bytes(16));
        $this->session->set_userdata('oauth_state', $state);

        $params = http_build_query([
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => $scopes,
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    public function google_callback() {
        $code  = $this->input->get('code');
        $state = $this->input->get('state');

        // CSRF state check
        if (!$code || $state !== $this->session->userdata('oauth_state')) {
            $this->session->set_flashdata('error', 'Google login failed. Please try again.');
            redirect('login');
            return;
        }

        $this->session->unset_userdata('oauth_state');

        // Exchange code for access token
        $token_data = $this->_get_google_token($code);

        if (!$token_data || empty($token_data['access_token'])) {
            $this->session->set_flashdata('error', 'Could not get token from Google.');
            redirect('login');
            return;
        }

        // Get user info from Google
        $google_user = $this->_get_google_user($token_data['access_token']);

        if (!$google_user || empty($google_user['email'])) {
            $this->session->set_flashdata('error', 'Could not get user info from Google.');
            redirect('login');
            return;
        }

        // Check if user already exists
        $user = $this->User_model->get_by_email($google_user['email']);

        if (!$user) {
            // New user — auto register
            $name   = $google_user['name'] ?? $google_user['email'];
            $parts  = explode(' ', $name);
            $avatar = strtoupper(substr($parts[0], 0, 1)) .
                      strtoupper(substr($parts[1] ?? 'U', 0, 1));

            $id = $this->User_model->create([
                'name'     => $name,
                'email'    => $google_user['email'],
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'avatar'   => $avatar,
            ]);

            $this->load->model('Workspace_model');
            $this->Workspace_model->create(['user_id' => $id, 'name' => 'My Workspace']);

            $user = $this->User_model->get($id);
        }

        $this->_set_session($user);
        redirect('dashboard');
    }

    // ─── Logout ──────────────────────────────────────────────────
    public function logout() {
        $this->session->sess_destroy();
        redirect('login');
    }

    // ─── Private Helpers ─────────────────────────────────────────
    private function _set_session($user) {
        $this->session->set_userdata('user', [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'avatar' => $user->avatar,
        ]);
    }

    private function _get_google_token($code) {
        $url  = 'https://oauth2.googleapis.com/token';
        $data = http_build_query([
            'code'          => $code,
            'client_id'     => $this->config->item('google_client_id'),
            'client_secret' => $this->config->item('google_client_secret'),
            'redirect_uri'  => $this->config->item('google_redirect_uri'),
            'grant_type'    => 'authorization_code',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => FALSE,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, TRUE);
    }

    private function _get_google_user($access_token) {
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token],
            CURLOPT_SSL_VERIFYPEER => FALSE,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, TRUE);
    }
}
