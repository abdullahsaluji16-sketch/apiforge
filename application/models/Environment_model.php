<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Environment_model extends CI_Model {

    public function get_all($user_id) {
        return $this->db->where('user_id', $user_id)->get('environments')->result();
    }

    public function get($id, $user_id) {
        return $this->db->where(['id' => $id, 'user_id' => $user_id])->get('environments')->row();
    }

    public function get_active($user_id) {
        return $this->db->where(['user_id' => $user_id, 'is_active' => 1])->get('environments')->row();
    }

    public function get_vars($env_id) {
        return $this->db->where('environment_id', $env_id)->get('env_variables')->result();
    }

    public function get_active_vars($user_id) {
        $env = $this->get_active($user_id);
        if (!$env) return [];
        return $this->get_vars($env->id);
    }

    public function create($data) {
        $this->db->insert('environments', $data);
        return $this->db->insert_id();
    }

    public function update($id, $user_id, $data) {
        $this->db->where(['id' => $id, 'user_id' => $user_id])->update('environments', $data);
    }

    public function set_active($id, $user_id) {
        $this->db->where('user_id', $user_id)->update('environments', ['is_active' => 0]);
        $this->db->where(['id' => $id, 'user_id' => $user_id])->update('environments', ['is_active' => 1]);
    }

    public function delete($id, $user_id) {
        $this->db->where(['id' => $id, 'user_id' => $user_id])->delete('environments');
    }

    public function add_var($env_id, $key, $value, $enabled = 1) {
        $this->db->insert('env_variables', ['environment_id' => $env_id, 'var_key' => $key, 'var_value' => $value, 'is_enabled' => $enabled]);
    }

    public function delete_vars($env_id) {
        $this->db->where('environment_id', $env_id)->delete('env_variables');
    }
}
