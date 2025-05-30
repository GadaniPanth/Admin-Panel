<?php

class Form_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function create_form($data) {
        $this->db->insert('forms', $data);
        return $this->db->insert_id();
    }

    public function edit_form($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('forms', $data);
    }

    public function delete_form($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('forms', $data);
    }

    public function get_forms($search = '', $limit = 10, $offset = 0) {
        if (!empty($search)) {
            $this->db->like('form_title', $search);
        }

        $this->db->limit($limit, $offset);
        $query = $this->db->get('forms');
        return $query->result();
    }

    public function count_forms($search = '') {
        if (!empty($search)) {
            $this->db->like('form_title', $search);
        }

        return $this->db->count_all_results('forms');
    }

    public function get_form_by_id($id) {
        $result = $this->db->get_where('forms', ["id"=> $id])->row();
        return $result;
    }

    public function get_form_by_name($name) {
        $this->db->where('form_title', $name);
        $this->db->order_by('id', 'DESC');
        $result = $this->db->get('forms')->row();
        return $result;
    }

    public function get_form_others($id) {
        $result = $this->db->get_where('forms', ["id"=>$id])->row();
        return $result->others;
    }

}