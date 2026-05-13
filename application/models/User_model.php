<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {
    public function get_by_email($email) {
        return $this->db->where('email', $email)->get('users')->row();
    }
    public function get($id) {
        return $this->db->where('id', $id)->get('users')->row();
    }
    public function create($data) {
        $this->db->insert('users', $data);
        return $this->db->insert_id();
    }
    public function update($id, $data) {
        $this->db->where('id', $id)->update('users', $data);
    }
}
