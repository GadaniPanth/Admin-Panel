<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Authorization, Access-Token, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");



class Admin_services extends CI_Controller{
    
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->model('common_model');
        $this->load->model('admin_model');
        $this->load->library('Authorization_Token');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            exit;
        }

        $postJson = file_get_contents("php://input");
    
        if ($this->common_model->checkjson($postJson)) {
            /** Skipping Json Decode Request if from_app is true **/
            if (isset($_POST['from_app']) && $_POST['from_app'] == "true") { 
            } else {
                $_POST = json_decode(file_get_contents("php://input"), true);
            }
        }

        // check authantication
        $headers = $this->input->request_headers();
        
        // uncomment below

        $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
        $function = $this->router->fetch_method();

        if($function != "login"){
            if (empty($decodedToken)) {
                $response['success'] = 3;
                $response['message'] = "Failed to authenticate request.";
                $this->output->set_status_header(401);
                echo json_encode($response);
                exit;
            } 
            else if (!empty($decodedToken) && ($decodedToken["status"] === false || $decodedToken["status"] === 1 && empty($decodedToken["data"]))) {
                $response['success'] = 2;
                $response['message'] = "Failed to authenticate request.";
                $this->output->set_status_header(401);
                echo json_encode($response);
                exit;
            }
            else {
                $authenticate = $this->db->get_where("users", array("id"=>$decodedToken["data"]->id))->row_array();
                if (empty($authenticate)) {
                    $response['success'] = 5;
                    $response['message'] = "Failed to authenticate request.";
                    $this->output->set_status_header(401);
                    echo json_encode($response);
                    exit;
                }
            }
        }
    }



    
    public function login($action = null){
        $post = $this->input->post();
        
        $response["success"] = 0;
        $response["message"] = "";

        $actions = ['with_password'];

        if(in_array($action, $actions)){

            if($action == 'with_password'){   
                if(empty($post['email'])){
                    $response["message"] = "Please enter Email.";
                }
                else if(empty($post['password'])){
                    $response["message"] = "Please enter Password.";
                }
                else{
                    $payload = ['email' => $post['email'], 'password' => $post['password']];
    
                    $data = $this->admin_model->user_auth($payload);
        
                    if(!empty($data)){
                        
                        // $issued_at = time();
                        // $expiration_time = $issued_at  + 3600*24*7;
            
                        // $token_data = array(
                        //     'issued_at' => $issued_at,
                        //     'expiration_time' => $expiration_time,
                        //     'username' => $data['username']
                        // );

						$token_data['id'] = $data['id'];
            
                        // $jwt = JWT::encode($token_data, $this->key, $this->algorithm);
                        $jwt_token = $this->authorization_token->generateToken($token_data);


                        $user_type_name = $this->db->get_where('user_type',array('id' => $data['user_type_id']))->row()->type;
                        $user_designation = $this->db->get_where('designation',array('id' => $data['designation_id']))->row()->designation;
                        
                        $response['data']['user_id'] = $data['id'];
                        $response['data']['name'] = $data['name'];
                        $response['data']['email'] = $data['email'];
                        $response['data']['contact_no'] = $data['contact_no'];
                        $response['data']['user_type_id'] = $data['user_type_id'];
                        $response['data']['user_type'] = $user_type_name;
                        $response['data']['designation_id'] = $data['designation_id'];
                        $response['data']['designation'] = $user_designation;
                        $response['data']['token'] = $jwt_token;

                        $response["success"] = 1;
                        $response["message"] = "Logged in successfully.";

                        
                    }
                    else{
                        $response["success"] = 0;
                        $response["message"] = "Invalid Credentials.";
                    }
                }
                  
            } 
        } 
        else{
            $response["success"] = 0;
            $response["message"] = "Invalid URL";
            // $this->output->set_status_header(404);
        }
        
        echo json_encode($response);
    }

    public function usersList(){
        $post = $this->input->post();

        

        // $user = $this->db->get('users')->result_array();


        $response['success'] = 0;
        $response['message'] = '';

        $and_condition = "";
        $pagelimit = "";

        $post["limit"] = !empty($post["limit"]) ? $post["limit"] : 1000;
        if (!empty($post["page"]) && $post['limit']) {
            $post["page"] = (int)$post["page"];
            $post["limit"] = (int)$post["limit"];
            $pagelimit .= " LIMIT " . (($post["page"] - 1) * $post["limit"]) . ", " . $post["limit"];
        }

        if (!empty($post["from_date"]) && !empty($post["to_date"])) {
            $from_date = strtotime($post["from_date"] . " 00:00:01");
            $to_date = strtotime($post["to_date"] . " 23:59:59");
            $and_condition .= " AND created_at >= $from_date AND created_at <= $to_date";
        }

        $search_q = "";
        if (isset($post['search']) && $post['search'] != "") {
            $searchColumns = explode(", ", "name, designation, email, contact_no");
            foreach ($searchColumns as $column) {
                $search_q .= ($search_q == "" ? " AND (" : " OR ") . "$column LIKE '%" . $post['search'] . "%'";
            }
            $search_q .= ")";
        }

        if ($post['user_type_id'] != '') {
            $and_condition .= " AND user_type_id = " . $post['user_type_id'];
        }
        if ($post['designation_id'] != '') {
            $and_condition .= " AND designation_id = " . $post['designation_id'];
        }
        

        $order_type = !empty($post['order_type']) ? $post['order_type'] : "name";
        $order_by = !empty($post['order_by']) ? $post['order_by'] : "DESC";


        $user_list = $this->db->query("SELECT SQL_CALC_FOUND_ROWS u.*,uty.type as user_type,d.designation
            FROM users u
            LEFT JOIN user_type uty ON uty.id = u.user_type_id
            LEFT JOIN designation d ON d.id = u.designation_id
            WHERE deleted_at IS NULL  
            $and_condition 
            $search_q 
            ORDER BY $order_type $order_by 
            $pagelimit"
        )->result_array();

     
        
        

        $total_records = $this->db->query("SELECT FOUND_ROWS() AS total_records")->row()->total_records;

        if(!empty($user_list)){

            foreach ($user_list as  $user) {
                $users_list[]= array(
                    'user_id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'contact_no' => $user['contact_no'],
                    'user_type_id' => $user['user_type_id'],
                    'user_type' => $user['user_type'],
                    'designation_id' => $user['designation_id'],
                    'designation' => $user['designation'],
                    'is_active' => $user['is_active'],
                    'created_at' => $user['created_at'],
                    'created_at_formated' => date('d F ,Y',$user['created_at']),
                );
            }
            $response['success'] = 1;
            $response['list'] = $users_list;
            $response['message'] = $total_records . ' Users Found.';
            $response['total_records'] = $total_records;
        }
        else{
            $response['success'] = 0;
            $response['message'] =  'No Users Found.';
        }

        // $user_list = $this->db->query("SELECT 
        //     u.id,u.name,u.email,u.contact_no,u.user_type_id,u.designation_id,u.is_active,u.created_at"
        // );


       echo json_encode($response);

    }


    public function userDetails(){
        $post = $this->input->post();

        $response['success'] = 0;
        $response['message'] = '';



        if(empty($post['user_id'])){
            $response['message'] = 'User Id is required ';
            echo json_encode($response);
            return;
        }


        if(!empty($post['user_id'])){

            $user_detail = $this->db->query("SELECT  u.*,uty.type as user_type,d.designation
                FROM users u
                LEFT JOIN user_type uty ON uty.id = u.user_type_id
                LEFT JOIN designation d ON d.id = u.designation_id
                WHERE u.id = ? ", ($post['user_id']) 
            )->row_array();
    
    
            if(!empty($user_detail)){
    
                    $user_details= array(
                        'user_id' => $user_detail['id'],
                        'name' => $user_detail['name'],
                        'email' => $user_detail['email'],
                        'contact_no' => $user_detail['contact_no'],
                        'user_type_id' => $user_detail['user_type_id'],
                        'user_type' => $user_detail['user_type'],
                        'designation_id' => $user_detail['designation_id'],
                        'designation' => $user_detail['designation'],
                        'is_active' => $user_detail['is_active'],
                        'created_at' => $user_detail['created_at'],
                        'created_at_formated' => date('d F ,Y',$user_detail['created_at']),
                    );
                
                $response['success'] = 1;
                $response['details'] = $user_details;
                $response['message'] =  'Users details found.';
            }
            else{
                $response['success'] = 0;
                $response['message'] =  'No Users Found.';
            }
        }

        echo json_encode($response);

        
    }


    public function designationList(){
        $post = $this->input->post();

        $response['success'] = 0;
        $response['message'] =  '';


        $designation_query = $this->db->query("SELECT SQL_CALC_FOUND_ROWS * FROM designation WHERE is_active = 1")->result_array();

        $total_records = $this->db->query("SELECT FOUND_ROWS() AS total_records")->row()->total_records;



        if(!empty($designation_query)){

            foreach ($designation_query as $designation) {
               
                $designation_list[] = array(
                    'id' =>  $designation['id'],
                    'designation' => $designation['designation'],
                    // 'is_active' => $designation['is_active'],
                    // 'created_at' => $designation['created_at'],
                    'created_at_formated' => date('d F , Y',$designation['created_at']),
                );
            }

            $response['success'] = 1;
            $response['message'] =  $total_records .' designation found';
            $response['list'] =  $designation_list;
            $response['total_records'] = $total_records;
        }
        else{

            $response['success'] = 0;
            $response['message'] =  'No data found';
            
        }

        echo json_encode($response);


    }

    public function userTypeList(){
        $post = $this->input->post();

        $response['success'] = 0;
        $response['message'] =  '';


        $user_query = $this->db->query("SELECT SQL_CALC_FOUND_ROWS * FROM user_type WHERE is_active = 1")->result_array();

        $total_records = $this->db->query("SELECT FOUND_ROWS() AS total_records")->row()->total_records;



        if(!empty($user_query)){

            foreach ($user_query as $usertype) {
               
                $usertype_list[] = array(
                    'id' =>  $usertype['id'],
                    'user_type' => $usertype['type'],
                    // 'is_active' => $usertype['is_active'],
                    // 'created_at' => $designation['created_at'],
                    'created_at_formated' => date('d F , Y',$usertype['created_at']),
                );
            }

            $response['success'] = 1;
            $response['message'] =  $total_records .' types found';
            $response['list'] =  $usertype_list;
            $response['total_records'] = $total_records;
        }
        else{
            $response['success'] = 0;
            $response['message'] =  'No data found';
        }

        echo json_encode($response);


    }

    
    public function saveInquiry1(){

        $post = $this->input->post();

        $response['success'] = 0;
        $response['message'] =  '';

        if(empty($post)){
            $response['message'] =  'No Post Data Found';
            echo json_encode($response);
            return;
        }

        // if(empty($post['name'])){
        //     $response['message'] =  'Name is required';
        // }
        // else if(empty($post['email'])){
        //     $response['message'] =  'Email is required';
        // }
        // else if(empty($post['contact_no'])){
        //     $response['message'] =  'Contact No is required';
        // }
        // else if(empty($post['message'])){
        //     $response['message'] =  'Message is required';
        // }
        // else if(empty($post['form_id'])){
        //     $response['message'] =  'Form Id is required';
        // }


        $required = ['name', 'email', 'contact_no','message','form_id'];
        foreach ($required as $field) {
            if (empty($post[$field])) {
                $response['message'] = ucfirst($field) . " can't be empty";
                echo json_encode($response);
                return;
            }
        }

        
    

    }


    public function save_inquiry()
    {
        $post = $this->input->post();
        $response = ['success' => 0, 'message' => ''];

        // Required fields validation
        $required = ['name', 'email', 'contact_no', 'message', 'form_id'];
        foreach ($required as $field) {
            if (empty($post[$field])) {
                $response['message'] = ucfirst(str_replace('_', ' ', $field)) . " can't be empty";
                echo json_encode($response);
                return;
            }
        }

        // $other = [];

        // $upload_dir = './uploads/inquiries/';
        // if (!is_dir($upload_dir)) {
        //     mkdir($upload_dir, 0777, true);
        // }

        
        if (isset($_POST['other'])) {
            foreach ($_POST['other'] as $key => $val) {
               
                if (isset($_FILES['other']['name'][$key]) && $_FILES['other']['name'][$key] != '') {
                    $file_name = time() . '_' . $_FILES['other']['name'][$key];
                    $upload_path = FCPATH . 'uploads/inquiries/';

                   
                    if (!is_dir($upload_path)) {
                        mkdir($upload_path, 0777, true);
                    }

                    move_uploaded_file($_FILES['other']['tmp_name'][$key], $upload_path . $file_name);

                    $other[$key] = $file_name;
                } else {
                    $other[$key] = $val;
                }
            }
        }

       
        $data = [
            'name' => $post['name'] ,
            'email'=> $post['email'] ,
            'contact_no'=> $post['contact_no'] ,
            'message'=> $post['message'],
            'form_id'=> $post['form_id'] ,
            'remarks'=> $post['remarks'] ? $post['remarks'] : NULL,
            'created_at'=> time(),
            'other'=> !empty($other) ? json_encode($other) : NULL,
        ];

        $this->db->insert('inquiries', $data);

        $response['success'] = 1;
        $response['message'] = 'Inquiry submitted successfully';

        echo json_encode($response);
    }



    public function inquiryList(){
        $post = $this->input->post();

        $response['success'] = 0;
        $response['message'] = '';

        
        $and_condition = "";
        $pagelimit = "";

        $post["limit"] = !empty($post["limit"]) ? $post["limit"] : 1000;
        if (!empty($post["page"]) && $post['limit']) {
            $post["page"] = (int)$post["page"];
            $post["limit"] = (int)$post["limit"];
            $pagelimit .= " LIMIT " . (($post["page"] - 1) * $post["limit"]) . ", " . $post["limit"];
        }

        if (!empty($post["from_date"]) && !empty($post["to_date"])) {
            $from_date = strtotime($post["from_date"] . " 00:00:01");
            $to_date = strtotime($post["to_date"] . " 23:59:59");
            $and_condition .= " AND created_at >= $from_date AND created_at <= $to_date";
        }

        $search_q = "";
        if (isset($post['search']) && $post['search'] != "") {
            $searchColumns = explode(", ", "name, email, contact_no");
            foreach ($searchColumns as $column) {
                $search_q .= ($search_q == "" ? " AND (" : " OR ") . "$column LIKE '%" . $post['search'] . "%'";
            }
            $search_q .= ")";
        }

        if ($post['form_id'] != '') {
            $and_condition .= " AND form_id = " . $post['form_id'];
        }
        if ($post['status_id'] != '') {
            $and_condition .= " AND status_id = '" . $post['status_id'] . "'";
        }

        $order_type = !empty($post['order_type']) ? $post['order_type'] : "created_at";
        $order_by = !empty($post['order_by']) ? $post['order_by'] : "DESC";


        $inquiry_query = $this->db->query("SELECT SQL_CALC_FOUND_ROWS
            iq.*,f.form_title from inquiries iq 
            LEFT JOIN forms f ON f.id = iq.form_id 
            WHERE 1=1 $and_condition $search_q 
            ORDER BY $order_type $order_by $pagelimit"
        )->result_array();

        $total_records = $this->db->query("SELECT FOUND_ROWS() AS total_records")->row()->total_records;



        if(!empty($inquiry_query)){

            foreach ($inquiry_query as  $inquiry) {



                $status_name = $this->db->get_where('inquiry_status',array('id'=>$inquiry['status_id']))->row()->name;


               $inquiry_list[] = array(
                'id' => $inquiry['id'],
                'name' => $inquiry['name'],
                'email' => $inquiry['email'],
                'contact_no' => $inquiry['contact_no'],
                'message' => $inquiry['message'],
                'form_id' => $inquiry['form_id'],
                'form_name' => $inquiry['form_title'],
                'status_id' => $inquiry['status_id'],
                'status' => $status_name,
                'remarks' => $inquiry['remarks'],
                'created_at' => $inquiry['created_at'],
                'created_at_formated' => date('d F ,Y',$inquiry['created_at']),
                'last_updated_at' =>$inquiry['updated_at'] ?  date('d F ,Y',$inquiry['updated_at']) :NULL,
                'closed_at' =>$inquiry['closed_at'] ? date('d F ,Y',$inquiry['closed_at']) : NULL,
                'other' => json_decode($inquiry['other']),
               );
               
            }

            $response['success'] = 1;
            $response['list'] = $inquiry_list;
            $response['message'] = $total_records . ' Inquiries Found';
            $response['total_records'] = $total_records;

        }
        else{
            $response['success'] = 0;
            $response['message'] = 'No Inquiries Found';
        }


        echo json_encode($response);

    }


    public function inquiry_follwup(){
        $post = $this->input->post();

        $response['success'] = 0;
        $response['message'] = '';

        if(empty($post)){
            $response['message'] = 'No post data found ';
            echo json_encode($response);
            return;
        }


        // Required fields validation
        $required = [ 'inquiry_id','status_id' ];
        foreach ($required as $field) {
            if (empty($post[$field])) {
                $response['message'] = ucfirst(str_replace('_', ' ', $field)) . " can't be empty";
                echo json_encode($response);
                return;
            }
        }

        $inquiry_data = array(
            'id' => $post['inquiry_id'],
            'remarks' => $post['remarks'],
        );

        $inquiry_logs = array(
            'inquiry_id' => $post['inquiry_id'],
            'remarks' => $post['remarks'] ? $post['remarks'] : NULL ,
            'status_id' => $post['status_id'],
            'performed_by' => $post['login_user_id'],
            'updated_at' => time(),
        );

        if(!empty($inquiry_data)){

           $this->db->update('inquiries', array(
                'remarks' => $post['remarks'] ? $post['remarks'] :NULL, 
                'status_id' => $post['status_id'],
                'updated_at' => time()
            ), array('id' => $post['inquiry_id']));


            if($inquiry_logs){
                $this->db->insert('inquiry_logs',$inquiry_logs);
            }

            $response['success'] = 1;
            $response['message'] = 'Inquiry Status Update Successfully';
        }
        else{
            $response['success'] = 0;
            $response['message'] = 'Enter Valid Data';
        }
       

        echo json_encode($response);
    }
    

    public function inquiry_type(){
        $post = $this->input->post();
        $response['success'] = 0;
        $response['message'] = '';


        $types = $this->db->get('forms')->result_array();


        if(!empty($types)){

            foreach ($types as  $value) {
                $type_list[] =array(
                    'id' => $value['id'],
                    'type' => $value['form_title'],
                    'created_at' => date('d F ,Y',$value['created_at']),
                );
            }
    
            $response['success'] = 1;
            $response['message'] = 'inquiry Type List';
            $response['list'] = $type_list;
        }
        else{
            $response['success'] = 0;
            $response['message'] = 'No data found';
        }


        echo json_encode($response);


    }


    public function inquiryStatus(){
        $post = $this->input->post();

        $response['success'] = 0;
        $response['message'] = '';


        $status = $this->db->get('inquiry_status')->result_array();

        if(!empty($status)){
            foreach ($status as  $value) {
                $status_list[] =array(
                    'id' => $value['id'],
                    'status' => $value['name'],
                    'created_at' => date('d F ,Y',$value['created_at']),
                );
            }

            $response['success'] = 1;
            $response['message'] = 'Inquiry Status List';
            $response['list'] = $status_list;
        }else{
            $response['success'] = 0;
            $response['message'] = 'No data found';
        }


        echo json_encode($response);
        
    }



    public function inquirHistory(){
        $post = $this->input->post();

        $response['success'] = 0;
        $response['message'] = '';
        

        if(empty($post['inquiry_id'])){
            $response['message'] = 'Please Provide Inquiry Id';
            echo json_encode($response);
            return;
        }


        $inquiries = $this->db->query("SELECT 
         ilog.*,u.name as performed_by_name,s.name as status ,iq.name as inquiry_name FROM inquiry_logs ilog
         LEFT JOIN users u ON u.id = ilog.performed_by
         LEFT JOIN inquiry_status s ON s.id = ilog.status_id
         LEFT JOIN inquiries iq ON iq.id = ilog.inquiry_id
         WHERE ilog.inquiry_id = ? ORDER BY updated_at DESC", $post['inquiry_id']
         
        )->result_array();



        if(!empty($inquiries)){
            foreach ($inquiries as  $value) {
                $inquiry_history_list[] = array(
                    'id' => $value['id'],
                    'inquiry_id' => $value['inquiry_id'],
                    'remarks' => $value['remarks'],
                    'performed_by' => $value['performed_by'],
                    'performed_by_name' => $value['performed_by_name'],
                    'status_id' => $value['status_id'],
                    'status' => $value['status'],
                    'inquiry_by' => $value['inquiry_name'],
                    'updated_at' => $value['updated_at'] ? date('d F,Y',$value['updated_at']) : NULL,
                    'update_time' => $value['updated_at'] ? date('h:i A',$value['updated_at']) : NULL,
                );
            }

            $response['success'] = 1;
            $response['message'] = 'Inquiry History List';
            $response['list'] = $inquiry_history_list;

        }
        else{
             $response['success'] = 0;
            $response['message'] = 'No data found';
    
        }


        echo json_encode($response);

    }

}