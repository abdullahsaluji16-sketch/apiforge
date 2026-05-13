<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Request_model extends CI_Model {

    public function get($id, $user_id) {
        return $this->db->where(['id' => $id, 'user_id' => $user_id])->get('requests')->row();
    }

    public function create($data) {
        $this->db->insert('requests', $data);
        return $this->db->insert_id();
    }

    public function update($id, $user_id, $data) {
        $this->db->where(['id' => $id, 'user_id' => $user_id])->update('requests', $data);
    }

    public function delete($id, $user_id) {
        $this->db->where(['id' => $id, 'user_id' => $user_id])->delete('requests');
    }
}
