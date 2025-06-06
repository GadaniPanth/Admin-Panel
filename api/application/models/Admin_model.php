<?php

class Admin_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

     public function user_auth($data){
        // $query = $this->db->get_where('users', array('email' => $data['email'], 'password' => $data['password']));
        $query = $this->db->get_where('users', array('email' => $data['email'], 'password' => $data['password'],'deleted_at'=>NULL));
        return $query->row_array();
    }

    public function create_user($data) {
        $this->db->insert('users', $data);
        return $this->db->insert_id();
    }

    public function get_user_by_id($id) {
        return $this->db->get_where('users', ['id' => $id])->row();
    }

    public function get_user_by_email($email) {
        $this->db->where('email', $email);
        $this->db->order_by('id', 'DESC');
        $result = $this->db->get('users')->row();
        return $result;
    }

    public function get_user_by_contact_no($contact_no) {
        $this->db->where('contact_no', $contact_no);
        $this->db->order_by('id', 'DESC');
        $result = $this->db->get('users')->row();
        return $result;
    }

    public function update_user($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('users', $data);
    }

    public function delete_user($id, $user_data) {
        // echo json_encode($user);
        // exit;

        $this->db->where('id', $id);
        return $this->db->update('users', $user_data);
    }

}