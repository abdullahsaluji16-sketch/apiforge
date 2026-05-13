<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tab_model extends CI_Model {

    public function get_user_tabs($user_id) {
        return $this->db->where('user_id', $user_id)->order_by('tab_order', 'ASC')->get('open_tabs')->result();
    }

    public function save($user_id, $data) {
        $exists = $this->db->where(['user_id' => $user_id, 'request_id' => $data['request_id'] ?? null])->get('open_tabs')->row();
        if ($exists) {
            $this->db->where('id', $exists->id)->update('open_tabs', $data);
            return $exists->id;
        }
        $data['user_id'] = $user_id;
        $this->db->insert('open_tabs', $data);
        return $this->db->insert_id();
    }

    public function set_active($id, $user_id) {
        $this->db->where('user_id', $user_id)->update('open_tabs', ['is_active' => 0]);
        $this->db->where(['id' => $id, 'user_id' => $user_id])->update('open_tabs', ['is_active' => 1]);
    }

    public function close($id, $user_id) {
        $this->db->where(['id' => $id, 'user_id' => $user_id])->delete('open_tabs');
    }
}
