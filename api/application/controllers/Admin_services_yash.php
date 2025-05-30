<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Authorization, Access-Token, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");


class Admin_services_yash extends CI_Controller
{

    public function __construct()
    {
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

        if ($function != "login") {
            if (empty($decodedToken)) {
                $response['success'] = 3;
                $response['message'] = "Failed to authenticate request.";
                $this->output->set_status_header(401);
                echo json_encode($response);
                exit;
            } else if (!empty($decodedToken) && ($decodedToken["status"] === false || $decodedToken["status"] === 1 && empty($decodedToken["data"]))) {
                $response['success'] = 2;
                $response['message'] = "Failed to authenticate request.";
                $this->output->set_status_header(401);
                echo json_encode($response);
                exit;
            } else {
                $authenticate = $this->db->get_where("users", array("id" => $decodedToken["data"]->id))->row_array();
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

    public function form()
    {

    }

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

        // $check = $this->db->query(
        //     "SELECT *  FROM `users`  WHERE email = '" . $this->input->post('email') . "' AND  contact_no = '" . $this->input->post('contact_no') . "' AND  id  !=" . $id
        // )->row_array();


        // echo json_encode($user);
        // print_r($check);
        // exit; 
        if (!empty($user)) {

            $name = $this->input->post('name');
            $email = $this->input->post('email');
            $contact_no = $this->input->post('contact_no');
            // $email = !empty($this->input->post('email')) ? $this->input->post('email') : $user->email;
            // $contact_no = !empty($this->input->post('contact_no')) ? $this->input->post('contact_no') : $user->contact_no;

            $user = $this->admin_model->get_user_by_email($email);
            // echo json_encode($user);
            // echo json_encode(is_null($user->deleted_at));
            // exit;
            if (!empty($user) && is_null($user->deleted_at)) {
                echo json_encode(['success' => 0, 'message' => 'User Already Exists email.']);
                return;
            }

            $user = $this->admin_model->get_user_by_contact_no($contact_no);
            // echo json_encode($user);
            // echo json_encode(is_null($user->deleted_at));
            // exit;
            if (!empty($user) && is_null($user->deleted_at)) {
                echo json_encode(['success' => 0, 'message' => 'User Already Exists. no']);
                return;
            }

            $user_type_id = $this->input->post('user_type_id');
            $designation_id = $this->input->post('designation_id');

            // $user_type_id = !empty($this->input->post('user_type_id')) ? $this->input->post('user_type_id') : $user->user_type_id;
            // $designation_id = !empty($this->input->post('designation_id')) ? $this->input->post('designation_id') : $user->designation_id;
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
        }
        //  else {
        //     echo json_encode(['success' => 0, 'message' => "Email and Contact are already assigned"]);
        //     return;
        // }
        echo json_encode(['success' => 0, 'message' => "Something Went Wrong!"]);
    }

    //Get Timestamp
    private function get_current_time()
    {
        return time();
    }


    // public function update_user()
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

    //     // $check = $this->db->query(
    //     //     "SELECT *  FROM `users`  WHERE email = '" .

    //     //     $this->input->post('email') . "' AND  contact_no = '" .

    //     //     $this->input->post('contact_no') . "' AND  id  !="
    //     //     . $id
    //     // )->row_array();

    //     // echo json_encode($check);
    //     // print_r($check);
    //     // exit; 
    //     if (!empty($check)) {
    //         $name = !empty($this->input->post('username')) ? $this->input->post('username') : $check->name;
    //         $email = !empty($this->input->post('email')) ? $this->input->post('email') : $check->email;
    //         $contact_no = !empty($this->input->post('contact_no')) ? $this->input->post('contact_no') : $check->contact_no;
    //         $user_type_id = !empty($this->input->post('user_type_id')) ? $this->input->post('user_type_id') : $check->user_type_id;
    //         $designation_id = !empty($this->input->post('designation_id')) ? $this->input->post('designation_id') : $check->designation_id;
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

    //         $result = $this->admin_model->update_user($id, $user_data);

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


    // public function update_user()
    // {
    //     if ($this->input->method() !== 'post') {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
    //         return;
    //     }

    //     $id = $this->input->post('user_id');
    //     if (empty($id)) {
    //         echo json_encode(['success' => 0, 'message' => 'Required User Id.']);
    //         return;
    //     }

    //     $check = $this->db->get_where('users', ['id' => $id])->row_array();
    //     if (empty($check)) {
    //         echo json_encode(['success' => 0, 'message' => 'User not found.']);
    //         return;
    //     }

    //     $name = trim($this->input->post('name'));
    //     $email = trim($this->input->post('email'));
    //     $contact_no = trim($this->input->post('contact_no'));
    //     $user_type_id = $this->input->post('user_type_id');
    //     $designation_id = $this->input->post('designation_id');

    //     $name = $name !== '' ? $name : $check['name'];
    //     $email = $email !== '' ? $email : $check['email'];
    //     $contact_no = $contact_no !== '' ? $contact_no : $check['contact_no'];
    //     $user_type_id = !empty($user_type_id) ? $user_type_id : $check['user_type_id'];
    //     $designation_id = !empty($designation_id) ? $designation_id : $check['designation_id'];

    //     $existing_user = $this->db->where('id !=', $id)
    //         ->group_start()
    //         ->where('email', $email)
    //         ->or_where('contact_no', $contact_no)
    //         ->group_end()
    //         ->get('users')
    //         ->row();

    //     // if (!empty($existing_user)) {
    //     //     echo json_encode(['success' => 0, 'message' => 'Email or Contact Number already in use by another user.']);
    //     //     return;
    //     // }

    //     print_r($existing_user->delete_at);
    //     exit;
    //     if (!empty($existing_user) && is_null($existing_user->deleted_at)) {
    //         echo json_encode(['success' => 0, 'message' => 'User already exists ']);
    //         return;
    //     }
    //     $user_data = [
    //         'name' => $name,
    //         'email' => $email,
    //         'contact_no' => $contact_no,
    //         'user_type_id' => $user_type_id,
    //         'designation_id' => $designation_id,
    //         'updated_at' => $this->get_current_time(),
    //     ];

    //     $result = $this->admin_model->update_user($id, $user_data);

    //     if ($result) {
    //         echo json_encode(['success' => 1, 'message' => 'User Updated Successfully.']);
    //     } else {
    //         $db_error = $this->db->error();
    //         echo json_encode(['success' => 0, 'message' => $db_error['message']]);
    //     }
    // }


    public function test()
    {
        $post = $this->input->post();

        echo "Hello";
    }

}