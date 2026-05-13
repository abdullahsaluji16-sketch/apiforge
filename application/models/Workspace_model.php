<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Workspace_model extends CI_Model {
    public function create($data) {
        $this->db->insert('workspaces', $data);
        return $this->db->insert_id();
    }
    public function get_all($user_id) {
        return $this->db->where('user_id', $user_id)->order_by('created_at', 'ASC')->get('workspaces')->result();
    }
    public function get($id, $user_id) {
        return $this->db->where(['id' => $id, 'user_id' => $user_id])->get('workspaces')->row();
    }
    public function get_first($user_id) {
        return $this->db->where('user_id', $user_id)->order_by('id', 'ASC')->limit(1)->get('workspaces')->row();
    }
    public function get_active($user_id) {
        // Return first workspace as active (simple approach)
        return $this->get_first($user_id);
    }
    public function set_active($id, $user_id) {
        // For future: implement active workspace switching
        return true;
    }
    public function delete($id, $user_id) {
        $this->db->where(['id' => $id, 'user_id' => $user_id])->delete('workspaces');
    }
    public function update($id, $user_id, $data) {
        $this->db->where(['id' => $id, 'user_id' => $user_id])->update('workspaces', $data);
    }
}
