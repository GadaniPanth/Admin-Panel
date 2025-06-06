<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Authorization, Access-Token, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");


class Admin_services_panth extends CI_Controller
{

    public function __construct()
    {
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

        // log_message('error', 'Auth Header: ' . json_encode($headers));
        // log_message('error', 'Decoded Token: ' . json_encode($decodedToken));

        $function = $this->router->fetch_method();

        // if($function != "login"){
        //     if (empty($decodedToken)) {
        //         $response['success'] = 3;
        //         $response['message'] = "Failed to authenticate request.";
        //         $this->output->set_status_header(401);
        //         echo json_encode($response);
        //         exit;
        //     } 
        //     else if (!empty($decodedToken) && ($decodedToken["status"] === false || $decodedToken["status"] === 1 && empty($decodedToken["data"]))) {
        //         $response['success'] = 2;
        //         $response['message'] = "Failed to authenticate request.";
        //         $this->output->set_status_header(401);
        //         echo json_encode($response);
        //         exit;
        //     }
        //     else {
        //         $authenticate = $this->db->get_where("users", array("id"=>$decodedToken["data"]->id))->row_array();
        //         if (empty($authenticate)) {
        //             $response['success'] = 5;
        //             $response['message'] = "Failed to authenticate request.";
        //             $this->output->set_status_header(401);
        //             echo json_encode($response);
        //             exit;
        //         }
        //     }
        // }
    }

    // public function index() {
    //     echo "INDEX";
    // }


    //User Build

    //User Login
    public function login()
    {
        // $data = json_decode(file_get_contents("php://input"), true);

        // $email = $data['email'] ?? '';
        // $password = $data['password'] ?? '';
        $email = $this->input->post("email") ?? '';
        $password = md5($this->input->post("password")) ?? '';

        // echo json_encode($this->input->post("email"));
        // exit;

        $user = $this->db->get_where('users', ['email' => $email, 'is_active' => 1])->row();

        if ($user) {
            // Verify password (assuming password stored hashed - if not, adjust accordingly)
            // If plain text, just compare directly:
            // if ($password === $user->password)

            // If password is hashed, use password_verify()
            // For example: if (password_verify($password, $user->password))

            if ($password_verify($password, $user->password)) { // Change this if passwords are hashed
                // Prepare JWT payload with user info
                $payload = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact_no' => $user->contact_no,
                    'user_type_id' => $user->user_type_id,
                    'designation_id' => $user->designation_id,
                    // 'iat' => time(),
                    // 'exp' => time() + 3600,
                ];

                $jwt = $this->authorization_token->generateToken($payload);

                echo json_encode([
                    'success' => 1,
                    'token' => $jwt,
                    'user_data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'contact_no' => $user->contact_no,
                        'user_type_id' => $user->user_type_id,
                        'designation_id' => $user->designation_id,
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => 0,
                    'message' => 'Invalid Password'
                ]);
            }
        } else {
            echo json_encode([
                'success' => 0,
                'message' => 'Invalid Email'
            ]);
        }
    }

    //Get Timestamp
    private function get_current_time()
    {
        return time();
    }

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
    //Update User

    //Check User Is Amin
    public function isAdmin($id)
    {
        $user = $this->admin_model->get_user_by_id($id);

        $is_admin = $this->db->get_where('users', ['id' => $id])->row();
        $is_admin = $this->db->get_where('user_type', ['id' => $is_admin->user_type_id])->row();
        $is_admin = $is_admin->type == 'admin' ? 1 : 0;
        return $is_admin;
    }
    //Check User Is Amin


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

    //Get Password
    // public function get_user_password()
    // {
    //     $user_id = $this->input->post("user_id") ?? '';
    //     $user = $this->db->get_where('users', ['id' => $user_id, 'deleted_at' => null])->row();

    //     if (!empty($user)) {
    //         echo json_encode([
    //             'success' => 1,
    //             'password' => $user->password
    //         ]);
    //         return;
    //     } else {
    //         echo json_encode([
    //             'success' => 0,
    //             'message' => 'User not found'
    //         ]);
    //         return;
    //     }
    // }

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

    // public function get_user_by_id($id = null) {

    // }

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

    // public function get_form_others($id = null)
    // {
    //     if ($this->input->method() !== 'get') {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use GET method.']);
    //         return;
    //     }

    //     if ($id == null) {
    //         echo json_encode(['success' => 0, 'message' => 'Required Id as Params.']);
    //         return;
    //     }

    //     $result = $this->Form_model->get_form_others($id);

    //     if (!empty($result)) {
    //         // Convert string to proper JSON format (single quotes → double quotes)
    //         $jsonString = str_replace("'", '"', $result);

    //         // Decode it into PHP array
    //         $list = json_decode($jsonString, true);

    //         // Only return if decoding was successful
    //         if (json_last_error() === JSON_ERROR_NONE) {
    //             echo json_encode(['success' => 1, 'list' => $list]);
    //             return;
    //         } else {
    //             echo json_encode(['success' => 0, 'message' => 'Invalid JSON format in database.']);
    //             return;
    //         }
    //     }

    //     echo json_encode(['success' => 0, 'message' => "No Other Fields Found!"]);
    // }

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

    public function modules_list() {
        $result = $this->db->get('modules')->result();
        if(!empty($result)){
            echo json_encode(['success'=>1, 'list'=> $result]);
            return;
        }
    }

    public function encryption_trial() {
        $this->load->library('encryption');
        
        $email = "test@example.com";
        $ciphertext = $this->encryption->encrypt($email);
        
        // header('Content-Type: text/plain');
        echo $ciphertext;
        echo '</br>';
        echo $this->encryption->decrypt($ciphertext);
        exit;
    }

}
