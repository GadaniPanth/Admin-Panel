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
        $password = $this->input->post("password") ?? '';

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
        if(!empty($user) && is_null($user->deleted_at)){
            echo json_encode(['success' => 0,'message' => 'User Already Exists.']);
            return;
        }

        $user = $this->admin_model->get_user_by_contact_no($contact_no);
        // echo json_encode($user);
        // echo json_encode(is_null($user->deleted_at));
        // exit;
        if(!empty($user) && is_null($user->deleted_at)){
            echo json_encode(['success' => 0,'message' => 'User Already Exists.']);
            return;
        }

        $user_type_id = $this->input->post('user_type_id');
        $designation_id = $this->input->post('designation_id');
        $password = $this->input->post('password');
        // $password = password_hash($password, PASSWORD_BCRYPT);
        $is_active = 1;
        $created_at = $this->get_current_time();

        $user_data = [
            'name' => $name,
            'email' => $email,
            'contact_no' => $contact_no,
            'user_type_id' => $user_type_id,
            'designation_id' => $designation_id,
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
    // public function update_user($id = null){
    //     if ($this->input->method() !== 'post') {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
    //         return;
    //     }
    //     if ($id == null) {
    //         echo json_encode(['success' => 0, 'message' => 'Required Id as Params.']);
    //         return;
    //     }

    //     $user = $this->admin_model->get_user_by_id($id);

    //     $check = $this->db->query(
    //         "SELECT *  FROM `users`  WHERE email = '" .

    //         $this->input->post('email') . "' AND  contact_no = '" .

    //         $this->input->post('contact_no') . "' AND  id  !="
    //         . $id
    //     )->row_array();


    //     // echo json_encode($check);
    //     print_r($check);
    //     // exit; 
    //     if (empty($check)) {

    //         $name = !empty($this->input->post('username')) ? $this->input->post('username') : $user->name;
    //         $email = !empty($this->input->post('email')) ? $this->input->post('email') : $user->email;
    //         $contact_no = !empty($this->input->post('contact_no')) ? $this->input->post('contact_no') : $user->contact_no;
    //         $user_type_id = !empty($this->input->post('user_type_id')) ? $this->input->post('user_type_id') : $user->user_type_id;
    //         $designation_id = !empty($this->input->post('designation_id')) ? $this->input->post('designation_id') : $user->designation_id;
    //         // $password = !empty($this->input->post('password')) ? $this->input->post('password') : $user->password;
    //         // $is_active = !empty($this->input->post('is_active')) ? $this->input->post('is_active') : $user->is_active;
    //         $updated_at = $this->get_current_time();

    //         $user_data = [
    //             'name' => $name,
    //             'email' => $email,
    //             'contact_no' => $contact_no,
    //             'user_type_id' => $user_type_id,
    //             'designation_id' => $designation_id,
    //             // 'password'=> $password,
    //             // 'is_active'=> $is_active,
    //             'updated_at' => $updated_at,
    //         ];

    //         // echo json_encode($user_data);
    //         // exit;

    //         $result = $this->admin_model->update_user($user->id, $user_data);

    //         if ($result) {
    //             echo json_encode(['success' => 1, "message" => "User Updated Successfully."]);
    //             return;
    //         } else {
    //             $db_error = $this->db->error();
    //             echo json_encode(['success' => 0, 'message' => $db_error['message']]);
    //             return;
    //         }

    //         echo json_encode(['success' => 0, 'message' => "Something Went Wrong!"]);
    //     }
    // }

    // public function update_user($id = null)
    // {
    //     if ($this->input->method() !== 'post') {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
    //         return;
    //     }
    //     if ($id == null) {
    //         echo json_encode(['success' => 0, 'message' => 'Required Id as Params.']);
    //         return;
    //     }

    //     $user = $this->admin_model->get_user_by_id($id);

    //     $check = $this->db->query(
    //         "SELECT *  FROM `users`  WHERE email = '" .

    //         $this->input->post('email') . "' AND  contact_no = '" .

    //         $this->input->post('contact_no') . "' AND  id  !="
    //         . $id
    //     )->row_array();


    //     // echo json_encode($check);
    //     // print_r($check);
    //     // exit; 
    //     if (empty($check)) {

    //         $name = !empty($this->input->post('username')) ? $this->input->post('username') : $user->name;
    //         $email = !empty($this->input->post('email')) ? $this->input->post('email') : $user->email;
    //         $contact_no = !empty($this->input->post('contact_no')) ? $this->input->post('contact_no') : $user->contact_no;
    //         $user_type_id = !empty($this->input->post('user_type_id')) ? $this->input->post('user_type_id') : $user->user_type_id;
    //         $designation_id = !empty($this->input->post('designation_id')) ? $this->input->post('designation_id') : $user->designation_id;
    //         // $password = !empty($this->input->post('password')) ? $this->input->post('password') : $user->password;
    //         // $is_active = !empty($this->input->post('is_active')) ? $this->input->post('is_active') : $user->is_active;
    //         $updated_at = $this->get_current_time();

    //         $user_data = [
    //             'name' => $name,
    //             'email' => $email,
    //             'contact_no' => $contact_no,
    //             'user_type_id' => $user_type_id,
    //             'designation_id' => $designation_id,
    //             // 'password'=> $password,
    //             // 'is_active'=> $is_active,
    //             'updated_at' => $updated_at,
    //         ];

    //         // echo json_encode($user_data);
    //         // exit;

    //         $result = $this->admin_model->update_user($user->id, $user_data);

    //         if ($result) {
    //             echo json_encode(['success' => 1, "message" => "User Updated Successfully."]);
    //             return;
    //         } else {
    //             $db_error = $this->db->error();
    //             echo json_encode(['success' => 0, 'message' => $db_error['message']]);
    //             return;
    //         }
    //     } else {
    //         echo json_encode(['success' => 0, 'message' => "Email and Contact are already assigned"]);
    //         return;
    //     }
    //     echo json_encode(['success' => 0, 'message' => "Something Went Wrong!"]);
    // }


    //  public function update_user()
    // {
    //     if ($this->input->method() !== 'post') {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
    //         return;
    //     }

    //     $id = $this->input->post('user_id');

    //     if ($id == null) {
    //         echo json_encode(['success' => 0, 'message' => 'Required User Id.']);
    //         return;
    //     }

    //     // $user = $this->admin_model->get_user_by_id($id);

    //     $check = $this->db->query(
    //         "SELECT *  FROM `users`  WHERE id =" . $id
    //     )->row_array();

    //     // echo json_encode($check);
    //     // print_r($check);
    //     // exit; 
    //     if (empty($check)) {

    //         $name = !empty($this->input->post('username')) ? $this->input->post('username') : $user->name;
    //         $email = !empty($this->input->post('email')) ? $this->input->post('email') : $user->email;
    //         $contact_no = !empty($this->input->post('contact_no')) ? $this->input->post('contact_no') : $user->contact_no;
    //         $user_type_id = !empty($this->input->post('user_type_id')) ? $this->input->post('user_type_id') : $user->user_type_id;
    //         $designation_id = !empty($this->input->post('designation_id')) ? $this->input->post('designation_id') : $user->designation_id;
    //         // $password = !empty($this->input->post('password')) ? $this->input->post('password') : $user->password;
    //         // $is_active = !empty($this->input->post('is_active')) ? $this->input->post('is_active') : $user->is_active;
    //         $updated_at = $this->get_current_time();

    //         $user_data = [
    //             'name' => $name,
    //             'email' => $email,
    //             'contact_no' => $contact_no,
    //             'user_type_id' => $user_type_id,
    //             'designation_id' => $designation_id,
    //             // 'password'=> $password,
    //             // 'is_active'=> $is_active,
    //             'updated_at' => $updated_at,
    //         ];

    //         // echo json_encode($user_data);
    //         // exit;

    //         $result = $this->admin_model->update_user($user->id, $user_data);

    //         if ($result) {
    //             echo json_encode(['success' => 1, "message" => "User Updated Successfully."]);
    //             return;
    //         } else {
    //             $db_error = $this->db->error();
    //             echo json_encode(['success' => 0, 'message' => $db_error['message']]);
    //             return;
    //         }
    //     } else {
    //         echo json_encode(['success' => 0, 'message' => "Email and Contact are already assigned"]);
    //         return;
    //     }
    //     echo json_encode(['success' => 0, 'message' => "Something Went Wrong!"]);
    // }

    public function update_user()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $id = $this->input->post('user_id');

        if ($id == null) {
            echo json_encode(['success' => 0, 'message' => 'Required User Id.']);
            return;
        }

        $user = $this->admin_model->get_user_by_id($id);

        $check = $this->db->query(
            "SELECT *  FROM `users`  WHERE id =" . $id
        )->row_array();

        // echo json_encode($check);
        // print_r($check);
        // exit; 
        if (empty($check)) {

            $name = !empty($this->input->post('username')) ? $this->input->post('username') : $user->name;
            $email = !empty($this->input->post('email')) ? $this->input->post('email') : $user->email;
            $contact_no = !empty($this->input->post('contact_no')) ? $this->input->post('contact_no') : $user->contact_no;
            $user_type_id = !empty($this->input->post('user_type_id')) ? $this->input->post('user_type_id') : $user->user_type_id;
            $designation_id = !empty($this->input->post('designation_id')) ? $this->input->post('designation_id') : $user->designation_id;
            // $password = !empty($this->input->post('password')) ? $this->input->post('password') : $user->password;
            // $is_active = !empty($this->input->post('is_active')) ? $this->input->post('is_active') : $user->is_active;
            $updated_at = $this->get_current_time();

            $user_data = [
                'name' => $name,
                'email' => $email,
                'contact_no' => $contact_no,
                'user_type_id' => $user_type_id,
                'designation_id' => $designation_id,
                // 'password'=> $password,
                // 'is_active'=> $is_active,
                'updated_at' => $updated_at,
            ];

            // echo json_encode($user_data);
            // exit;

            $result = $this->admin_model->update_user($user->id, $user_data);

            if ($result) {
                echo json_encode(['success' => 1, "message" => "User Updated Successfully."]);
                return;
            } else {
                $db_error = $this->db->error();
                echo json_encode(['success' => 0, 'message' => $db_error['message']]);
                return;
            }
        } else {
            echo json_encode(['success' => 0, 'message' => "Email and Contact are already assigned"]);
            return;
        }
        echo json_encode(['success' => 0, 'message' => "Something Went Wrong!"]);
    }


    //Check User Is Amin
    public function isAdmin($id)
    {
        $user = $this->admin_model->get_user_by_id($id);

        $is_admin = $this->db->get_where('users', ['id' => $id])->row();
        $is_admin = $this->db->get_where('user_type', ['id' => $is_admin->user_type_id])->row();
        $is_admin = $is_admin->type == 'admin' ? 1 : 0;
        return $is_admin;
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
        if(!empty($form) && is_null($form->deleted_at)){
            echo json_encode(['success' => 0,'message' => 'Form name already exists ' . $form_title]);
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
        $page = (int) $this->input->post('page');
        $limit = (int) $this->input->post('limit');

        if ($page < 1)
            $page = 1;
        if ($limit < 1)
            $limit = 10;

        $offset = ($page - 1) * $limit;

        $results = $this->Form_model->get_forms($search, $limit, $offset);
        $total = $this->Form_model->count_forms($search);

        if (!empty($results)) {
            $results = array_filter($results, function ($result) {
                return empty($result->deleted_at);
            });

            $results = array_values($results);
            foreach ($results as $result) {
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
            }

            echo json_encode([
                'success' => 1,
                'message' => count($results) . ' Forms Found.',
                'list' => $results,
                'total_records' => $total
            ]);
            return;
        }

        echo json_encode(['success' => 0, 'message' => 'No forms found.', 'list' => [], 'total' => 0]);
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
}