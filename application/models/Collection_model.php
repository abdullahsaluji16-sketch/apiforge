<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Collection_model extends CI_Model {

    public function get_with_requests($user_id) {
        $collections = $this->db->where('user_id', $user_id)->order_by('created_at', 'DESC')->get('collections')->result();
        foreach ($collections as &$col) {
            $col->requests = $this->db->where('collection_id', $col->id)->order_by('created_at', 'ASC')->get('requests')->result();
        }
        return $collections;
    }

    public function create($data) {
        $this->db->insert('collections', $data);
        return $this->db->insert_id();
    }

    public function delete($id, $user_id) {
        $this->db->where(['id' => $id, 'user_id' => $user_id])->delete('collections');
    }

    public function count($user_id) {
        return $this->db->where('user_id', $user_id)->count_all_results('collections');
    }
}
