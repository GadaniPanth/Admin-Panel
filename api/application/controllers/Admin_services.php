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
        $this->load->model('Form_model');
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

        // if($function != "login"){
        if ($function != "login" && $function != "reset_link" && $function != "update_password" && $function != "change_password") {
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



    // Nihar Start 
    
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
                    $payload = ['email' => $post['email'], 'password' => md5($post['password'])];
    
                    $data = $this->admin_model->user_auth($payload);

                    $user_type_id = $data['user_type_id'];
                    $user_id = $data['id'];

                    // print_r($data);
                    // die;
        
                    if(!empty($data)){


                        $user_permission_exists = $this->db->get_where('users_permissions',array('user_id'=>$user_id))->result_array();


                        if(!empty($user_permission_exists)){

                        $get_permissions = $this->db->query("SELECT up.*,m.module_name,m.module_slug from users_permissions up
                                                         LEFT JOIN modules m ON up.module_id = m.id 
                                                         WHERE up.user_id = $user_id "
                                                    )->result_array();
                        }
                        else{
                        $get_permissions = $this->db->query("SELECT r.*,m.module_name,m.module_slug from role_permissions r
                                                         LEFT JOIN modules m ON r.module_id = m.id 
                                                         WHERE r.user_type_id = $user_type_id ")->result_array();

                        }


                        
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
                        $response['data']['profile_pic'] = !empty($data['profile_pic']) ?  base_url() ."uploads/profile_images/" . $data['profile_pic'] : "";
                     
                        $response['data']['contact_no'] = $data['contact_no'];
                        $response['data']['user_type_id'] = $data['user_type_id'];
                        $response['data']['user_type'] = $user_type_name;
                        $response['data']['designation_id'] = $data['designation_id'];
                        $response['data']['designation'] = $user_designation;
                        $response['data']['token'] = $jwt_token;


                        foreach ($get_permissions as $key => $per) {
                            // $response['permissions'][] = $per;
            
                            // $response['permissions'][$key]['can_add'] = $per['can_add']; 
                            // $response['permissions'][$key]['can_edit'] = $per['can_edit'];
                            // $response['permissions'][$key]['can_view'] = $per['can_view'];
                            // $response['permissions'][$key]['can_delete'] = $per['can_delete'];
                            // $response['permissions'][$key]['can_export'] = $per['can_export'];
                            // $response['permissions'][$key]['slug'] = $per['module_slug']; 


                            $response['data']['permissions'][] = array(
                                'can_add' => $per['can_add'],
                                'can_edit' => $per['can_edit'],
                                'can_view' => $per['can_view'],
                                'can_export' => $per['can_export'],
                                'can_delete' => $per['can_delete'],
                                'slug' => $per['module_slug']
                            );
                            
                        }

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
        if ($post['status_id'] != '') {
            $and_condition .= " AND u.is_active = " . $post['status_id'];
        }
        

        $order_type = !empty($post['order_type']) ? $post['order_type'] : "created_at";
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
    
            


            // $user_permission_exists = $this->db->get_where('users_permissions' ,array('user_id' => $user_detail['id']))->result_array();
            
            
            // // echo json_encode($user_detail);
            // // die;
            
            // if(!empty($user_permission_exists)){
            //     // $permissions_list = $user_permission_exists;
            //     $permissions_list = $this->db->get_where('users_permissions' ,array('user_id' => $user_detail['id']))->result_array();;
            // }
            // else{
            //     $permissions_list = $this->db->get_where('role_permissions',array('user_type_id' => $user_detail['user_type_id']))->result_array();
            // }


            // 
            $user_id = $user_detail['id'];
            $user_type_id = $user_detail['user_type_id'];

            $user_permissions = $this->db->get_where('users_permissions', ['user_id' => $user_id])->result_array();
            
            $role_permissions = $this->db->get_where('role_permissions', ['user_type_id' => $user_type_id])->result_array();
            // Step 2: Build map with user permissions
            $final_permissions = [];
            $user_module_ids = [];

            foreach ($user_permissions as $perm) {
                $user_module_ids[] = $perm['module_id']; // track module_id
                $final_permissions[$perm['module_id']] = [
                    'module_id'    => $perm['module_id'],
                    'can_view'     => $perm['can_view'],
                    'can_add'      => $perm['can_add'],
                    'can_edit'     => $perm['can_edit'],
                    'can_delete'   => $perm['can_delete'],
                    'can_export'   => $perm['can_export'],
                ];
            }

            // Step 3: Fill in missing module_ids from role_permissions
            foreach ($role_permissions as $role_perm) {
                if (!in_array($role_perm['module_id'], $user_module_ids)) {
                    $final_permissions[$role_perm['module_id']] = [
                        'module_id'    => $role_perm['module_id'],
                        'can_view'     => $role_perm['can_view'],
                        'can_add'      => $role_perm['can_add'],
                        'can_edit'     => $role_perm['can_edit'],
                        'can_delete'   => $role_perm['can_delete'],
                        'can_export'   => $role_perm['can_export'],
                    ];
                }
            }

            // Step 4: Convert to indexed array
            $permissions_list = array_values($final_permissions);


            // 

        

    
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
                        'profile_pic' => !empty($user_detail['profile_pic']) ? base_url() ."uploads/profile_images/" . $user_detail['profile_pic'] : '',
                        'is_active' => $user_detail['is_active'],
                        'created_at' => $user_detail['created_at'],
                        'created_at_formated' => date('d F ,Y',$user_detail['created_at']),
                        'permissions' => $permissions_list,
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

        $other = [];
       

            $upload_path = FCPATH . 'uploads/inquiries/';
            if (!is_dir($upload_path)) {
                if (!mkdir($upload_path, 0777, true)) {
                    echo json_encode(['success'=>0, 'message'=>'Failed to create directory']);
                    die;
                }
            }


            if (isset($_FILES['other']['name']) && is_array($_FILES['other']['name'])) {
                foreach ($_FILES['other']['name'] as $key => $filename) {
                    if ($_FILES['other']['error'][$key] === 0) {
                        $original_name = basename($filename);
                        $file_name = time() . '_' . preg_replace('/\s+/', '_', $original_name);

                        if (move_uploaded_file($_FILES['other']['tmp_name'][$key], $upload_path . $file_name)) {
                            $other[$key] = $file_name;
                        } else {
                            $other[$key] = 'upload_failed';
                        }
                    }
                }
            }

            if (isset($_POST['other']) && is_array($_POST['other'])) {
                foreach ($_POST['other'] as $key => $val) {
                    if (!isset($other[$key])) {
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
                'updated_at'=> time(),
                'other'=> !empty($other) ? json_encode($other) : NULL,
            ];


        // echo json_encode($data);
        // die;
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
            // $and_condition .= " AND iq.created_at >= $from_date AND iq.created_at <= $to_date";
            $and_condition .= " AND iq.updated_at >= $from_date AND iq.updated_at <= $to_date";
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
            $and_condition .= " AND form_id = " . $post['form_id'] ;
        }
        if ($post['status_id'] != '') {
            $and_condition .= " AND status_id = '" . $post['status_id'] . "'";
        }

        $order_type = !empty($post['order_type']) ? $post['order_type'] : "status_id";
        $order_by = !empty($post['order_by']) ? $post['order_by'] : "ASC";


        $inquiry_query = $this->db->query("SELECT SQL_CALC_FOUND_ROWS
            iq.*,f.form_title from inquiries iq 
            LEFT JOIN forms f ON f.id = iq.form_id 
            WHERE 1=1 $and_condition $search_q 
            ORDER BY  $order_type $order_by , iq.id DESC  $pagelimit"
        )->result_array();

        $total_records = $this->db->query("SELECT FOUND_ROWS() AS total_records")->row()->total_records;



        if(!empty($inquiry_query)){

            foreach ($inquiry_query as  &$inquiry) {



                $status_name = $this->db->get_where('inquiry_status',array('id'=>$inquiry['status_id']))->row()->name;

                // echo json_encode($inquiry);die;

                $inquiry['other'] = !empty($inquiry['other']) ? json_decode($inquiry['other'], true) : [];

                if(!empty($inquiry['other']['resume'])){
                    $inquiry['other']['resume'] = base_url() . "uploads/inquiries/" . $inquiry['other']['resume'];
                }

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
                'last_updated_at' =>$inquiry['updated_at'] ?  date('d F, Y',$inquiry['updated_at']) :NULL,
                'closed_at' =>$inquiry['closed_at'] ? date('d F, Y',$inquiry['closed_at']) : NULL,
                'other' => $inquiry['other'],
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


        $types = $this->db->get_where('forms',array('deleted_at'=>NULL))->result_array();


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


    public function inquiryStatusCounter(){

        $post = $this->input->post();
       

        $pending_inquiry = $this->db->get_where('inquiries',array('status_id'=>1))->result_array();
        $pending_count = count($pending_inquiry);

        $inprogress_inquiry = $this->db->get_where('inquiries',array('status_id'=>2))->result_array();
        $inprogress_count = count($inprogress_inquiry);

        $onhold_inquiry = $this->db->get_where('inquiries',array('status_id'=>3))->result_array();
        $onhold_count = count($onhold_inquiry);

        $complete_inquiry = $this->db->get_where('inquiries',array('status_id'=>4))->result_array();
        $complete_count = count($complete_inquiry);



        $response['success'] = 1;
        $response['message'] = 'Inquiry Status Couters ';

        $response['data']['pending_count'] = $pending_count;
        $response['data']['inprogress_count'] = $inprogress_count;
        $response['data']['onhold_count'] = $onhold_count;
        $response['data']['complete_count'] = $complete_count;
        $response['data']['total'] = (int)$complete_count + (int)$inprogress_count + (int)$onhold_count  + (int)$pending_count ;

        echo json_encode($response) ;
        


    }


    public function managePermissions(){

        $post = $this->input->post();
        $response['success'] = 0;
        $response['message'] = '';


        if(empty($post)){
            $response['message'] = 'No post data found';
            echo json_encode($response);
            return;
        }


        if(empty($post['user_id'])){
            $response['message'] = 'User Id is required';
            echo json_encode($response);
            return;
        }

        
        if(!empty($post['permissions']) ){
            // $permissions = ($post['permissions']);
            $permissions = json_decode($post['permissions'],true);
            // echo json_encode($permissions);
            // die;
        }
        else{
            $response['message'] = 'Permission is required';
            echo json_encode($response);
            return;
        }


       


        $user_permisson_exist = $this->db->get_where('users_permissions',array('user_id'=>$post['user_id']))->result_array();

        if(!empty($user_permisson_exist)){
            $this->db->delete('users_permissions',array('user_id' => $post['user_id']));
        }

        foreach ($permissions as $value) {
            $permission_list = array(
                'user_id' => $post['user_id'],
                'module_id' => $value['module_id'],
                'can_add' => $value['can_add'],
                'can_edit' => $value['can_edit'],
                'can_view' => $value['can_view'],
                'can_export' => $value['can_export'],
                'can_delete' => $value['can_delete'],
            );


            $this->db->insert('users_permissions' , $permission_list);
        }

        // echo json_encode($permission_list);
        // die;

        $response['success'] = 1;
        $response['message'] = 'Permissions Update Successfully';


        echo json_encode($response);
        

    }


    public function dashboardCounters(){
        $post = $this->input->post();
       

        $response['success'] = 1;
        $response['message'] = 'Dashboard Couters ';

        //inquiry

        $pending_inquiry = $this->db->get_where('inquiries',array('status_id'=>1))->result_array();
        $pending_count = count($pending_inquiry);

        $inprogress_inquiry = $this->db->get_where('inquiries',array('status_id'=>2))->result_array();
        $inprogress_count = count($inprogress_inquiry);

        $onhold_inquiry = $this->db->get_where('inquiries',array('status_id'=>3))->result_array();
        $onhold_count = count($onhold_inquiry);

        $complete_inquiry = $this->db->get_where('inquiries',array('status_id'=>4))->result_array();
        $complete_count = count($complete_inquiry);


        $response['data']['inquiries_counter']['pending_count'] = $pending_count;
        $response['data']['inquiries_counter']['inprogress_count'] = $inprogress_count;
        $response['data']['inquiries_counter']['onhold_count'] = $onhold_count;
        $response['data']['inquiries_counter']['complete_count'] = $complete_count;
        $response['data']['inquiries_counter']['total'] = (int)$complete_count + (int)$inprogress_count + (int)$onhold_count  + (int)$pending_count ;

        // user 


        $admin_users = $this->db->get_where('users', array('user_type_id' => 1, 'deleted_at' => null))->result_array();
        $admin_count = count($admin_users);

        $staff_users = $this->db->get_where('users', array('user_type_id' => 2, 'deleted_at' => null))->result_array();
        $staff_count = count($staff_users);

      

        $response['data']['users_conter']['admin_count'] = $admin_count;
        $response['data']['users_conter']['staff_count'] = $staff_count;
        $response['data']['users_conter']['total'] = (int) $admin_count + (int) $staff_count;



        // form counter

        $form_counter = $this->db->get_where('forms',array('deleted_at'=>NULL))->result_array();

        $total_form = count($form_counter);

         $response['data']['form_counter']['total'] = $total_form;

        echo json_encode($response) ;
    }

    // Nihar End 



    // Panth Start

    //Form Build

    //Create Form
    public function create_form()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $form_title = $this->input->post('form_title');
        $others = $this->input->post('others');

        if (empty($form_title)) {
            echo json_encode(['success' => 0, 'message' => 'Title is Empty']);
            return;
        }

        // echo json_encode($form_title);
        // exit;

        $form_data = [
            'form_title' => $form_title,
            'others' => $others,
            'created_at' => $this->get_current_time(),
        ];

        // echo json_encode($form_data);
        // exit;

        $form = $this->Form_model->get_form_by_name($form_title);
        // echo json_encode($form);
        // echo json_encode(is_null($form->deleted_at));
        // exit;
        if (!empty($form) && is_null($form->deleted_at)) {
            echo json_encode(['success' => 0, 'message' => 'Form name already exists ' . $form_title]);
            return;
        }

        $result = $this->Form_model->create_form($form_data);

        if (!empty($result)) {
            echo json_encode(['success' => 1, "message" => "Form Created Successfully.", "form_id" => $result]);
            return;
        } else {
            $db_error = $this->db->error();
            if (!empty($db_error['code'])) {
                if ($db_error['code'] == 1062) {
                    // Duplicate entry
                    echo json_encode([
                        'success' => 0,
                        'message' => 'Form Already Exists.'
                    ]);
                } else {
                    // Other DB error
                    echo json_encode([
                        'success' => 0,
                        'message' => 'Database error: ' . $db_error['message']
                    ]);
                }
                return;
            }
        }
        echo json_encode(['success' => 0, 'message' => "Form Creation failed!"]);
    }

    //Get Form List
    public function get_forms()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $search = $this->input->post('search');
        $is_active = $this->input->post('status');
        $page = (int) $this->input->post('page');
        $limit = (int) $this->input->post('limit');

        // echo json_encode($is_active != "null");
        // exit;

        $limit = $limit > 0 ? $limit : 10;
        $page = $page > 0 ? $page : 1;
        $offset = ($page - 1) * $limit;

        $results = $this->Form_model->get_forms($search, $limit, $offset, $is_active);
        $total = $this->db->query('SELECT count(*) as total FROM forms WHERE deleted_at is NULL')->row();
        $total = $total->total;

        if (!empty($results)) {
            $results = array_filter($results, function ($result) {
                return empty($result->deleted_at);
            });

            $results = array_values($results);

            foreach ($results as $result) {
                $result->created_at_formated = !empty($result->created_at)
                    ? date('d F, Y', $result->created_at)
                    : null;

                $result->last_updated_at = !empty($result->updated_at)
                    ? date('d F, Y', $result->updated_at)
                    : null;

                $result->others = json_decode($result->others ?? '{}', true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $result->others = [];
                }

                $query = $this->db->query('SELECT COUNT(*) as total FROM inquiries WHERE form_id = ?', [$result->id]);
                $row = $query->row();
                $result->total_inquiries = $row->total;
            }

            echo json_encode([
                'success' => 1,
                'message' => count($results) . ' Forms Found.',
                'list' => $results,
                'total_records' => $total
            ]);
        } else {
            echo json_encode(['success' => 0, 'message' => 'No forms found.', 'list' => [], 'total_records' => 0]);
        }
    }

    //Get Form
    public function get_form_by_id()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $form_id = $this->input->post('form_id');

        if ($form_id == null) {
            echo json_encode(['success' => 0, 'message' => 'Required Form id.']);
            return;
        }

        $result = $this->Form_model->get_form_by_id($form_id);

        if ($result->deleted_at) {
            echo json_encode(['success' => 0, 'message' => "No Forms Found!"]);
            return;
        }

        if (!empty($result)) {
            if (!empty($result->created_at)) {
                $result->created_at_formated = date('d F, Y', $result->created_at);
            } else {
                $result->created_at_formated = null;
            }
            if (!empty($result->others)) {
                $decoded_others = json_decode($result->others, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $result->others = $decoded_others;
                } else {
                    $result->others = [];
                }
            } else {
                $result->others = [];
            }

            echo json_encode(['success' => 1, 'form' => $result]);
            return;
        }

        echo json_encode(['success' => 0, 'message' => "No Forms Found!"]);
    }

    //Change Active/Inactive
    public function change_form_status()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $form_id = $this->input->post('form_id');
        $is_active = $this->input->post('is_active');

        if (empty($this->Form_model->get_form_by_id($form_id))) {
            echo json_encode(['success' => 0, 'message' => 'Form not found!']);
            return;
        }

        $form_data = [
            'is_active' => $is_active
        ];

        // echo json_encode([$form_id, $form_data]);
        // exit;

        $this->db->where('id', $form_id);
        $result = $this->db->update('forms', $form_data);

        if ($result) {
            echo json_encode(["success" => 1, 'message' => 'Updated Status Successfully.']);
            return;
        }
        echo json_encode(["success" => 0, 'message' => 'Status is not Updated.']);
        return;
    }

    //Edit Form
    public function edit_form()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $form_id = $this->input->post('form_id');

        // echo json_encode(['id'=>$form_title]);

        if (empty($form_id)) {
            echo json_encode(['success' => 0, 'message' => 'Required Form Id.']);
            return;
        }

        $form = $this->Form_model->get_form_by_id($form_id);
        // echo json_encode($form);
        if (empty($form)) {
            echo json_encode(['success' => 0, 'message' => 'Form does not Exists!   ']);
            return;
        }

        $form_title = $this->input->post('form_title');
        $others = $this->input->post('others');

        // echo json_encode($others);
        // exit;

        $form_data = [
            'form_title' => $form_title,
            'others' => $others,
            'updated_at' => $this->get_current_time()
        ];

        // echo json_encode($form_data);
        // exit;

        // echo $form->id;
        $result = $this->Form_model->edit_form($form->id, $form_data);

        if ($result) {
            echo json_encode(['success' => 1, "message" => "Form Updated Successfully.", 'form_id' => $form->id]);
            return;
        } else {
            $db_error = $this->db->error();
            if (!empty($db_error['code'])) {
                if ($db_error['code'] == 1062) {
                    // Duplicate entry
                    echo json_encode([
                        'success' => 0,
                        'message' => 'Form name Already Exists ' . $form_title
                    ]);
                } else {
                    // Other DB error
                    echo json_encode([
                        'success' => 0,
                        'message' => 'Database error: ' . $db_error['message']
                    ]);
                }
                return;
            }
        }
    }

    //Delete Form
    public function delete_form()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $form_id = $this->input->post('form_id');
        if ($form_id == null) {
            echo json_encode(['success' => 0, 'message' => 'Required User id.']);
            return;
        }

        $form = $this->Form_model->get_form_by_id($form_id);
        // echo json_encode($form);
        // exit;
        if (!$form) {
            echo json_encode(['success' => 0, 'message' => 'Form Not Found!']);
            return;
        }

        // echo "In developing";
        // exit;

        $form_data = [
            'deleted_at' => $this->get_current_time()
        ];

        $result = $this->Form_model->delete_form($form_id, $form_data);
        // echo json_encode($result);
        // exit;
        if ($result) {
            echo json_encode(['success' => 1, 'message' => 'Form Deleted Successfully.', 'form_id' => $form_id]);
            return;
        } else {
            $db_error = $this->db->error();
            echo json_encode(['success' => 0, 'message' => $db_error['message']]);
            return;
        }
    }


    //User Build

    //Create User
    public function create_user()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $name = $this->input->post('name');
        $email = $this->input->post('email');
        $contact_no = $this->input->post('contact_no');

        // if(empty($name) || empty($email) || empty($contact_no)){
        //     echo json_encode();
        //     exit;
        // }

        $user = $this->admin_model->get_user_by_email($email);
        // echo json_encode($user);
        // echo json_encode(is_null($user->deleted_at));
        // exit;
        if (!empty($user) && is_null($user->deleted_at)) {
            echo json_encode(['success' => 0, 'message' => 'User Already Exists with email ' . $email]);
            return;
        }

        $user = $this->admin_model->get_user_by_contact_no($contact_no);
        // echo json_encode($user);
        // echo json_encode(is_null($user->deleted_at));
        // exit;
        if (!empty($user) && is_null($user->deleted_at)) {
            echo json_encode(['success' => 0, 'message' => 'User Already Exists with contact ' . $contact_no]);
            return;
        }

        // $this->load->helper('url');
        // $this->load->library('upload');

        // $config['upload_path']   = FCPATH . 'uploads/profile_images/';
        // $config['allowed_types'] = 'jpg|jpeg|png|gif';
        // $config['encrypt_name']  = FALSE;

        // $this->upload->initialize($config);

        // $profile_pic = '';

        // if (!empty($_FILES['profile_pic']['name'])) {
        //     if (!$this->upload->do_upload('profile_pic')) {
        //         echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
        //         return;
        //     } else {
        //         $uploaded_data = $this->upload->data();
        //         $timestamp     = $this->get_current_time();
        //         $new_name      = $timestamp . '_' . $uploaded_data['file_name'];

        //         $old_path = $uploaded_data['full_path'];
        //         $new_path = $uploaded_data['file_path'] . $new_name;
        //         rename($old_path, $new_path);

        //         $profile_pic = $new_name;
        //     }
        // } else {
        //     $profile_pic ='default.jpg';
        // }

        // echo json_encode(['status' => true, 'filename' => $profile_pic]);
        // exit();

        $user_type_id = $this->input->post('user_type_id');
        $designation_id = $this->input->post('designation_id');
        if($designation_id == "null"){
            $others = strtolower($this->input->post('others'));
            $this->db->where('LOWER(designation)', $others);
            $result = $this->db->get('designation')->result();
            // echo json_encode($result);
            // exit;
            if(!empty($result)){
                echo json_encode(['success'=>0,'message'=> 'Designation already Exists!']);
                return;
            }
            $des_data = [
                'designation' => $this->input->post('others'),
                'created_at' => time(),
                'is_active' => 1
            ];
            
            $this->db->insert('designation', $des_data);
            $designation_id = $this->db->insert_id();
            // echo $designation_id;
            // exit;
        }

        $password = $this->input->post('password');
        if (empty($password)) {
            echo json_encode(['success' => 0, 'message' => 'Both password fields are required.']);
            return;
        }
        $password = md5($password);
        $is_active = 1;
        $created_at = $this->get_current_time();

        $user_data = [
            'name' => $name,
            'email' => $email,
            'contact_no' => $contact_no,
            'user_type_id' => $user_type_id,
            'designation_id' => $designation_id,
            // 'profile_pic' => $profile_pic,
            'password' => $password,
            'is_active' => $is_active,
            'created_at' => $created_at,
        ];

        // echo json_encode($user_data);
        // exit;

        $result = $this->admin_model->create_user($user_data);

        if (!empty($result)) {
            echo json_encode(['success' => 1, "message" => "User Registered Successfully.", "id" => $result]);
            return;
        } else {
            $db_error = $this->db->error();
            echo json_encode(['success' => 0, 'message' => $db_error['message']]);
            return;
        }

        echo json_encode(['success' => 0, 'message' => "Something Went Wrong!"]);
    }

    //Delete user
    public function delete_user()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $user_id = $this->input->post('user_id');
        if ($user_id == null) {
            echo json_encode(['success' => 0, 'message' => 'Required User id.']);
            return;
        }

        // if($this->isAdmin($id)){
        //     echo json_encode(['success' => 0, 'message' => "Can not delete Admin."]);
        //     return;
        // }

        $user = $this->admin_model->get_user_by_id($user_id);
        // echo json_encode($user);
        // exit;
        if (!$user) {
            echo json_encode(['success' => 0, 'message' => 'User Not Found!']);
            return;
        }

        // $deleted_at = $this->get_current_time();

        $user_data = [
            // 'name' => $user->name,
            // 'email' => $user->email,
            // 'contact_no' => $user->contact_no,
            // 'user_type_id' => $user->user_type_id,
            // 'designation_id' => $user->designation_id,
            // 'password'=> $user->password,
            // 'is_active'=> $is_active,
            // 'created_at' => $user->created_at,
            'updated_at' => $this->get_current_time(),
            'deleted_at' => $this->get_current_time(),
        ];

        // echo json_encode($user);
        // exit;

        $result = $this->admin_model->delete_user($user_id, $user_data);
        // echo json_encode($result);
        // exit;
        if ($result) {
            echo json_encode(['success' => (int) $result, 'message' => 'User Deleted Successfully.', 'user_id' => $id]);
            return;
        } else {
            $db_error = $this->db->error();
            echo json_encode(['success' => 0, 'message' => $db_error['message']]);
            return;
        }
    }

    //Update User
     public function update_user()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $id = $this->input->post('user_id');

        if ($id == null) {
            echo json_encode(['success' => 0, 'message' => 'Required Id as Params.']);
            return;
        }

        $user = $this->admin_model->get_user_by_id($id);
        $check = $this->db->query(
            "SELECT *  FROM `users`  WHERE email = '" . $this->input->post('email') . "' OR  contact_no = '" . $this->input->post('contact_no') . "' AND  id  !=" . $id
        )->row_array();

        $check = $this->db->query(
            "SELECT * FROM `users` WHERE 
                 ( email = '" . $this->input->post('email') . "'
                 OR
                 contact_no = '" . $this->input->post('contact_no') . "')
                 AND id != '" . $id . "' AND deleted_at is NULL 
            "
        )->row_array();
        // 

        // echo json_encode($user);

        // exit; 
        if (empty($check)) {

            $name = $this->input->post('name');
            $email = $this->input->post('email');
            $contact_no = $this->input->post('contact_no');
            // $email = !empty($this->input->post('email')) ? $this->input->post('email') : $user->email;
            // $contact_no = !empty($this->input->post('contact_no')) ? $this->input->post('contact_no') : $user->contact_no;

            // $user = $this->admin_model->get_user_by_email($email);
            // echo json_encode($user);
            // echo json_encode(is_null($user->deleted_at));
            // exit;
            // if (!empty($check) && is_null($check->deleted_at)) {
            //     echo json_encode(['success' => 0, 'message' => 'User Already Exists email.']);
            //     return;
            // }

            // $user = $this->admin_model->get_user_by_contact_no($contact_no);
            // echo json_encode($user);
            // echo json_encode(is_null($user->deleted_at));
            // exit;
            // if (!empty($user) && is_null($user->deleted_at)) {
            //     echo json_encode(['success' => 0, 'message' => 'User Already Exists. no']);
            //     return;
            // }

            $this->load->helper('url');
            $this->load->library('upload');

            $config['upload_path'] = FCPATH . 'uploads/profile_images/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config['encrypt_name'] = FALSE;

            $this->upload->initialize($config);

            $profile_pic = '';

            if (!empty($_FILES['profile_pic']['name'])) {
                if (!$this->upload->do_upload('profile_pic')) {
                    echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                    return;
                } else {
                    $uploaded_data = $this->upload->data();
                    $timestamp = $this->get_current_time();
                    $new_name = $timestamp . '_' . $uploaded_data['file_name'];

                    $old_path = $uploaded_data['full_path'];
                    $new_path = $uploaded_data['file_path'] . $new_name;
                    rename($old_path, $new_path);
                    $profile_pic = $new_name;
                }
            }


            $password = $this->input->post('password');

            // if (empty($password)) {
            //     echo json_encode(['success' => 0, 'message' => 'Both password fields are required.']);
            //     return;
            // }

            // $password = md5($password);
            $user_type_id = $this->input->post('user_type_id');
            $designation_id = $this->input->post('designation_id');
            if($designation_id == "null"){
                $others = strtolower($this->input->post('others'));
                $this->db->where('LOWER(designation)', $others);
                $result = $this->db->get('designation')->result();
                // echo json_encode($result);
                // exit;
                if(!empty($result)){
                    echo json_encode(['success'=>0,'message'=> 'Designation already Exists!']);
                    return;
                }
                $designation = $this->input->post('others');
                if(empty($designation)){
                    echo json_encode(['success'=>0,'message'=> 'Designation is Empty!']);
                    return;
                }
                $des_data = [
                    'designation' => $designation,
                    'created_at' => time(),
                    'is_active' => 1
                ];
                
                $this->db->insert('designation', $des_data);
                $designation_id = $this->db->insert_id();
                // echo $designation_id;
                // exit;
            }

            $updated_at = $this->get_current_time();

            $user_data = [
                'name' => $name ? $name : $user->name,
                'email' => $email ? $email : $user->email,
                'contact_no' => $contact_no ? $contact_no : $user->contact_no,
                'password' => $password ? md5($password) : $user->password,
                'user_type_id' => $user_type_id ? $user_type_id : $user->user_type_id,
                'designation_id' => $designation_id ? $designation_id : $user->designation_id,
                'profile_pic' => $profile_pic ? $profile_pic : null,
                // 'is_active'=> $is_active,
                'updated_at' => $updated_at,
            ];

            // echo json_encode($user_type_id);
            // echo json_encode($designation_id);
            // exit();

            // $updated_at = $this->get_current_time();

            // $user_data = [
            //     'name' => $name,
            //     'email' => $email,
            //     'contact_no' => $contact_no,
            //     // 'user_type_id' => $user_type_id,
            //     // 'designation_id' => $designation_id,
            //     // 'password'=> $password,
            //     // 'is_active'=> $is_active,
            //     'updated_at' => $updated_at,
            // ];

            // echo json_encode($user_data);
            // exit();
            // $user_type_id = !empty($this->input->post('user_type_id')) ? $this->input->post('user_type_id') : $user->user_type_id;
            // $designation_id = !empty($this->input->post('designation_id')) ? $this->input->post('designation_id') : $user->designation_id;
            // $password = !empty($this->input->post('password')) ? $this->input->post('password') : $user->password;
            // $is_active = !empty($this->input->post('is_active')) ? $this->input->post('is_active') : $user->is_active;


            // echo json_encode($user_data);
            // exit;

            // $result = $this->admin_model->update_user($user->id, $user_data)
            $this->db->where('id', $id);
            $result = $this->db->update('users', $user_data);

            if ($result) {
                echo json_encode(['success' => 1, "message" => "User Updated Successfully."]);
                return;
            } else {
                // $db_error = $this->db->error();
                // echo json_encode(['success' => 0, 'message' => $db_error['message']]);
                // return;

                $db_error = $this->db->error();
                if (!empty($db_error['code'])) {
                    if ($db_error['code'] == 1062) {
                        // Duplicate entry
                        echo json_encode([
                            'success' => 0,
                            'message' => 'User Already Exists.'
                        ]);
                    } else {
                        // Other DB error
                        echo json_encode([
                            'success' => 0,
                            'message' => 'Database error: ' . $db_error['message']
                        ]);
                    }
                    return;
                }
            }
        } else {
            echo json_encode(['success' => 0, 'message' => "Email or Contact are already assigned"]);
            return;
        }
        echo json_encode(['success' => 0, 'message' => "Something Went Wrong!"]);
    }

    //Get User Counter
    public function userTypeCounter()
    {
        $post = $this->input->post();
        $admin_users = $this->db->get_where('users', array('user_type_id' => 1, 'deleted_at' => null))->result_array();
        $admin_count = count($admin_users);

        $staff_users = $this->db->get_where('users', array('user_type_id' => 2, 'deleted_at' => null))->result_array();
        $staff_count = count($staff_users);

        $response['success'] = 1;
        $response['message'] = 'User Type Couters ';

        $response['data']['admin_count'] = $admin_count;
        $response['data']['staff_count'] = $staff_count;
        $response['data']['total'] = (int) $admin_count + (int) $staff_count;

        echo json_encode($response);
    }

    //Dashboard Build

    //All Counters
    public function get_counters()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        // echo'HIHIHIHIH';
        // exit();

        $form_counts = $this->db->query("SELECT count(*) as total FROM forms WHERE deleted_at is NULL")->row();
        $form_counts = $form_counts->total;


        $user_counts = $this->db->query("SELECT count(*) as total FROM users WHERE deleted_at is NULL")->row();
        $user_counts = $user_counts->total;

        // echo $user_counts;
        // exit();

        $inquiry_counts = $this->db->query("SELECT count(*) as total FROM inquiries ")->row();
        $inquiry_counts = $inquiry_counts->total;

        // echo $inquiry_counts;
        // exit();
        $counter_list = [
            (object) ['counter' => 'Forms', 'value' => $form_counts],
            // (object) ['counter' => 'Users', 'value' => $user_counts],
            // (object) ['counter' => 'Inquiries', 'value' => $inquiry_counts]
        ];

        echo json_encode(['success' => 1, 'list' => $counter_list, 'total_records' => count($counter_list)]);
        return;

    }

    //
    //Get Modules
    
    public function modules_list() {
        $result = $this->db->get('modules')->result();
        if(!empty($result)){
            echo json_encode(['success'=>1, 'list'=> $result]);
            return;
        }
    }

    // Panth End


    // Yash Start

    //User Build

    //Change Active/Inactive
    public function change_user_status()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $user_id = $this->input->post('user_id');
        $is_active = $this->input->post('is_active');

        if (empty($this->admin_model->get_user_by_id($user_id))) {
            echo json_encode(['success' => 0, 'message' => 'User not found!']);
            return;
        }

        $user_data = ['is_active' => $is_active];

        // echo json_encode($user_data);
        // exit();

        $this->db->where('id', $user_id);
        $result = $this->db->update('users', $user_data);

        if ($result) {
            echo json_encode(["success" => 1, 'message' => 'Updated Status Successfully.']);
        } else {
            echo json_encode(["success" => 0, 'message' => 'Status is not Updated.']);
        }
    }

    //Change Password
    public function change_password()
    {

        // $email = $this->input->post("email") ?? '';
        $user_id = $this->input->post("user_id");
        $old_password = md5($this->input->post("old_password"));
        $new_password = md5($this->input->post("new_password"));
        // $confirm_password = $this->input->post("confirm_password") ?? '';

        if (empty($old_password) || empty($new_password)) {
            echo json_encode([
                'success' => 0,
                'message' => 'All fields are required.'
            ]);
            return;
        }

        if ($old_password == $new_password) {
            echo json_encode([
                'success' => 0,
                'message' => "Can not use old Password again."
            ]);
            return;
        }

        // if ($new_password !== $confirm_password) {
        //     echo json_encode([
        //         'success' => 0,
        //         'message' => 'Passwords do not match'
        //     ]);
        //     return;
        // }

        $user = $this->db->get_where('users', ['id' => $user_id, 'is_active' => 1])->row();

        // echo json_encode($user->password);
        // echo '</br>';
        // echo json_encode(md5($old_password));
        // echo json_encode($old_password == $user->password);
        // exit;

        if (!empty($user)) {
            if ($old_password == $user->password) {
                $this->db->where('id', $user_id);
                $result = $this->db->update('users', ['password' => $new_password, 'updated_at' => $this->get_current_time()]);

                if ($result) {
                    echo json_encode([
                        'success' => 1,
                        'message' => 'Password changed successfully'
                    ]);
                    return;
                }

                echo json_encode([
                    'success' => 0,
                    'message' => 'Password not Changed.'
                ]);
                return;
            } else {
                echo json_encode([
                    'success' => 0,
                    'message' => 'Old password is incorrect'
                ]);
                return;
            }
        } else {
            echo json_encode([
                'success' => 0,
                'message' => 'User not found'
            ]);
            return;
        }
    }

    //Update Password
    public function update_password()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $this->load->library('encryption');

        $token = $this->input->post('token'); // Or from URL segment/query param
        // echo $token;
        // echo '</br>';
        // echo '</br>';
        // echo '</br>';
        $token = urldecode($token);

        // echo $token;

        if (empty($token)) {
            echo json_encode(['success' => 0, 'message' => 'Token not Found or expired.']);
            return;
        }

        $email = $this->encryption->decrypt($token);

        // echo '</br>';
        // echo '</br>';
        // echo '</br>';
        // echo json_encode($email);
        // exit();
        if (empty($email)) {
            echo json_encode(['success' => 0, 'message' => 'Email Not Found.']);
            return;
        }

        // echo $token;
        // echo $email;
        // die;


        // echo json_encode($email);
        // exit();

        $this->db->where('email', $email);
        $this->db->where('user_type_id', 1);
        $this->db->where('deleted_at', NULL);
        $user = $this->db->get('users')->row();


        // echo '</br>';
        // echo '</br>';
        // echo '</br>';
        // echo json_encode($user);
        // $user = $this->db->query("SELECT * FROM users WHERE email = " . $email . 'AND user_type_id = ' . 1 . ' AND deleted_at = ' . NULL)->row();



        if (empty($user)) {
            echo json_encode(['success' => 0, 'message' => 'Invalid token or user not allowed to change password.']);
            return;
        }

        $password = $this->input->post('password');
        $confirm_password = $this->input->post('confirm_password');

        if (!$password || !$confirm_password) {
            echo json_encode(['success' => 0, 'message' => 'Both password fields are required.']);
            return;
        }

        if ($password !== $confirm_password) {
            echo json_encode(['success' => 0, 'message' => 'Passwords do not match.']);
            return;
        }

        $new_password = md5($password);



        // echo '</br>';
        // echo '</br>';
        // echo '</br>';
        // echo $new_password;
        // exit();

        $updated_at = $this->get_current_time();

        $update_data = [
            'password' => $new_password,
            'updated_at' => $updated_at,
        ];

        $this->db->where('id', $user->id);
        $result = $this->db->update('users', $update_data);

        if ($result) {
            echo json_encode(['success' => 1, 'message' => 'Password updated successfully.']);
        } else {
            echo json_encode(['success' => 0, 'message' => 'Failed to update password.']);
        }

        // 5df3fd82dfad67d68b399f8adfe3c3e3509826fcf23f195ed1c5478af874d5e8c30f7d80c11962a1848a63a1e9186391b306fb642f31120f0d1b8f7e8f10a37ct5yYYOyma6Wtj6qmUdBvCihHrSXQtIfTkcRn2s8jS6OimIY3ge0197kgZyy5rfMv
        // </br>
        // </br>
        // </br>5df3fd82dfad67d68b399f8adfe3c3e3509826fcf23f195ed1c5478af874d5e8c30f7d80c11962a1848a63a1e9186391b306fb642f31120f0d1b8f7e8f10a37ct5yYYOyma6Wtj6qmUdBvCihHrSXQtIfTkcRn2s8jS6OimIY3ge0197kgZyy5rfMv
        // </br>
        // </br>
        // </br>panth@coronation.in
        // </br>
        // </br>
        // </br>{"id":"92","name":"Priya","email":"panth@coronation.in","contact_no":"9091829387","user_type_id":"1","designation_id":"1","password":"7ece99e593ff5dd200e2b9233d9ba654","profile_pic":null,"is_active":"1","created_at":"1749130586","updated_at":"1749186975","deleted_at":null}
        // </br>
        // </br>
        // </br>827ccb0eea8a706c4c34a16891f84e7b{"success":1,"message":"Password updated successfully."}
    }

    //Email Generate SendGrid

    //Mail Link
    public function reset_link()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST.']);
            return;
        }

        $toEmail = $this->input->post('toEmail');

        if (empty($toEmail)) {
            echo json_encode(['success' => 0, 'message' => 'Email is required.']);
            return;
        }

        $this->db->where('deleted_at', NULL);
        $user = $this->db->where('email', $toEmail)->get('users')->row();

        if (empty($user)) {
            echo json_encode(['success' => 0, 'message' => 'Email Not Found.']);
            return;
        }

        if ($user->user_type_id != 1) {
            echo json_encode(['success' => 0, 'message' => 'You are not an admin.']);
            return;
        }

        $this->load->library('encryption');

        // $current = time();
        // $validTill = $current + 3600;

        // $payload = json_encode([
        //     'email' => $toEmail,
        //     'expires_at' => $validTill
        // ]);

        // $encrypted = urlencode($this->encryption->encrypt($payload));

        // encrypt email

        // $token = base64_encode($toEmail);

        $token = $this->encryption->encrypt($toEmail);
        $safeToken = urlencode($token);
        $resetLink = base_url("reset-password?token=" . $safeToken);

        // echo json_encode($token);
        // exit();

        // $hash = password_hash($toEmail, PASSWORD_BCRYPT);
        // echo json_encode(password_verify($toEmail, $hash));
        // exit;

        // this email === toEmail

        // $data = [
        //     'email' => $toEmail,
        //     'token' => $hash,
        //     'created_at' => $current,
        //     'expires_at' => $validTill
        // ];
        // $updated_at = $this->get_current_time();

        // $user_data = [
        //     'updated_at' => $updated_at,
        // ];

        // $this->db->where('email', $toEmail);
        // $this->db->update('users', $user_data);
        // $this->db->insert('password_resets', $data);
        // $result = $this->db->insert_id();

        $this->do_email(NULL, NULL, NULL, NULL, NULL, NULL, NULL, [], NULL, $toEmail, $safeToken);
        // if (!empty($result)) {
        // }
        // echo json_encode(['success' => 1, 'message' => 'Reset link sent to your email.']);
        // return;
        // echo json_encode($data);
        // exit;
    }


    //Sendgrid
    function do_email($msg = NULL, $sub = NULL, $to = NULL, $from = NULL, $bccs = null, $cc = NULL, $reply_to = NULL, $attachments = array(), $system_name = null, $toEmail = null, $token = null)
    {

        if ($attachments) {
            $attachments = array_unique($attachments);
        }

        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        // $toEmail = $this->input->post('toEmail');
        // echo json_encode($toEmail);
        // exit; 

        if (empty($toEmail)) {
            echo json_encode(['success' => 0, 'message' => 'Recievers email is Empty.']);
            return;
        }

        $base_url_front = 'http://url:4200/';

        // $user = $this->db->get_where('password_resets', ['email' => $toEmail])->row();
        // $resetLink = $base_url_front . ("reset-password?token={$user->token}");
        $resetLink = $base_url_front . "reset-password?token=" . $token;


        $from = "nihaal@coronation.in";
        $to = $toEmail;
        $sub = 'Reset Your Password';
        // $msg = '<h4 style="font-size: 14px; color: #1e1e1e">Click the link below to reset your password:</h4>'
        //     . '</br><a href="' . $resetLink . '" style="font-size: 16px; color: blue;">' . $resetLink . '</a>';

        $msg = '
        <!DOCTYPE html>
        <html>
        <head>
        <meta charset="UTF-8">
        <title>Reset Password</title>
        </head>
        <body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 0;">
            <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05); overflow:hidden;">
                <tr>
                    <td style="background-color: #5D87FF; color: #ffffff; padding: 30px; text-align: center;">
                    <h2 style="margin:0; font-size: 24px;">Reset Your Password</h2>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px; color: #333;">
                    <p style="font-size: 16px; line-height: 1.6;">
                        We received a request to reset your password. Click the button below to set a new one.
                    </p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $resetLink . '" style="background-color: #28a745; color: #ffffff; padding: 12px 24px; text-decoration: none; font-size: 16px; border-radius: 6px; display: inline-block;">
                        Reset Password
                        </a>
                    </div>
                    <p style="font-size: 14px; color: #666;">
                        If you did not request a password reset, please ignore this email.
                    </p>
                    <p style="font-size: 14px; color: #666; word-break: break-word;">
                        Or copy and paste this link into your browser:<br>
                        <a href="' . $resetLink . '" style="color: #007BFF;">' . $resetLink . '</a>
                    </p>
                    </td>
                </tr>
                <tr>
                    <td style="background-color: #f9f9f9; text-align: center; padding: 20px; font-size: 12px; color: #999;">
                    &copy; ' . date("Y") . ' Your Company Name. All rights reserved.
                    </td>
                </tr>
                </table>
            </td>
            </tr>
        </table>
        </body>
        </html>';


        $ci = get_instance();
        $ci->load->library('email');

        $config['protocol'] = "";
        // $config['smtp_crypto'] = "tls";
        $config['smtp_host'] = "";
        $config['smtp_port'] = "";
        $config['smtp_user'] = "";
        $config['smtp_pass'] = "";
        $config['mailtype'] = "";
        $config['charset'] = "";
        $config['wordwrap'] = TRUE;
        $config['newline'] = "\r\n";
        $config['crlf'] = "\n";
        $config['smtp_debug'] = 4;


        $ci->email->initialize($config);
        if (!$system_name) {
            $system_name = "Admin Panel";
        }
        $ci->email->clear(TRUE);
        $ci->email->from($from, $system_name);
        $ci->email->to($to);

        if ($reply_to) {
            $ci->email->reply_to($reply_to);
        }

        if ($bccs) {
            $ci->email->bcc($bccs);
        }

        $ci->email->subject($sub);
        $ci->email->message($msg);

        foreach ($attachments as $attachment) {
            if ($attachment) {
                $ci->email->attach($attachment);
            }
        }

        $IsSendMail = $ci->email->send();
        // log_message('error', $ci->email->print_debugger());
        // echo $IsSendMail;
        // echo $ci->email->print_debugger();
        // die;
        if ($IsSendMail) {
            echo json_encode(["success" => 1, 'message' => "Email Sent on " . $toEmail]);
            return;
        } else {
            echo json_encode(["success" => 0, 'message' => "Email not sent."]);
            return;
        }
    }

    // Yash End


    //Get Timestamp
    private function get_current_time()
    {
        return time();
    }
}