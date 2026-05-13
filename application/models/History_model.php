<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class History_model extends CI_Model {

    public function save($data) {
        $this->db->insert('request_history', $data);
        return $this->db->insert_id();
    }

    public function get_recent($user_id, $limit = 20) {
        return $this->db->where('user_id', $user_id)->order_by('created_at', 'DESC')->limit($limit)->get('request_history')->result();
    }

    public function get_all($user_id) {
        return $this->db->where('user_id', $user_id)->order_by('created_at', 'DESC')->get('request_history')->result();
    }

    public function get($id, $user_id) {
        return $this->db->where(['id' => $id, 'user_id' => $user_id])->get('request_history')->row();
    }

    public function count_all($user_id) {
        return $this->db->where('user_id', $user_id)->count_all_results('request_history');
    }

    public function avg_response_time($user_id) {
        $r = $this->db->select_avg('response_time')->where('user_id', $user_id)->get('request_history')->row();
        return ($r && !is_null($r->response_time)) ? round((float)$r->response_time) : 0;
    }

    public function success_rate($user_id) {
        $total   = $this->count_all($user_id);
        if (!$total) return 100;
        $success = $this->db->where('user_id', $user_id)->where('response_status >=', 200)->where('response_status <', 300)->count_all_results('request_history');
        return round(($success / $total) * 100, 1);
    }

    public function clear_all($user_id) {
        $this->db->where('user_id', $user_id)->delete('request_history');
    }

    public function delete($id, $user_id) {
        $this->db->where(['id' => $id, 'user_id' => $user_id])->delete('request_history');
    }
}
