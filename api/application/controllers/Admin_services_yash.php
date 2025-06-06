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
        $this->load->model('email_model');
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

        // if ($function != "login" && $function != "reset_link" && $function != "update_password"  && $function != "change_password") {
        if ($function != "login" && $function != "reset_link" && $function != "update_password" && $function != "change_password") {
            // if ($function != "login" || $function != "reset_link") {
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

    // public function update_password()
    // {
    //     if ($this->input->method() !== 'post') {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST method.']);
    //         return;
    //     }

    //     $token = $this->input->post('token');

    //     //decode
    //     $this->load->library('encryption');
    //     $email = base64_decode($token);

    //     // $this->db->where('token', $token);
    //     // $user = $this->db->get('password_resets')->row();

    //     // if(empty($user)){
    //     //     echo json_encode(["success"=> 0, "message"=> "Token Expired!"]);
    //     //     return;
    //     // }

    //     $this->db->where('user_type_id', 1);
    //     // $this->db->where('email', $user->email);
    //     $this->db->where('email', $email);
    //     $user = $this->db->get('users')->row();

    //     if (empty($user)) {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid token or user not allowed to change password.']);
    //         return;
    //     }

    //     // $email = $user->email;


    //     $new_password = $this->input->post('password');

    //     $confirm_password = $this->input->post('confirm_password');

    //     if (!$new_password || !$confirm_password) {
    //         echo json_encode(['success' => 0, 'message' => 'both password fields are required.']);
    //         return;
    //     }

    //     if ($new_password !== $confirm_password) {
    //         echo json_encode(['success' => 0, 'message' => 'Passwords do not match.']);
    //         return;
    //     }

    //     $updated_at = $this->get_current_time();
    //     $update_data = [
    //         'password' => $new_password,
    //         'updated_at' => $updated_at
    //     ];

    //     $this->db->where('email', $email);
    //     $result = $this->db->update('users', $update_data);

    //     if ($result) {
    //         $this->db->where('email', $email);
    //         $result = $this->db->delete('password_resets');

    //         echo json_encode(['success' => 1, 'message' => 'Password updated successfully.']);
    //     } else {
    //         echo json_encode(['success' => 0, 'message' => 'Failed to update password.']);
    //     }
    // }


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


            $password = md5($this->input->post('password'));
            $user_type_id = $this->input->post('user_type_id');
            $designation_id = $this->input->post('designation_id');

            $updated_at = $this->get_current_time();

            $user_data = [
                'name' => $name ? $name : $user->name,
                'email' => $email ? $email : $user->email,
                'contact_no' => $contact_no ? $contact_no : $user->contact_no,
                'password' => $password ? $password : $user->password,
                'user_type_id' => $user_type_id ? $user_type_id : $user->user_type_id,
                'user_type_id' => $user_type_id ? $user_type_id : $user->user_type_id,
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

    //Get Timestamp
    private function get_current_time()
    {
        return time();
    }


    // public function change_password()
    // {

    //     $user_id = $this->input->post("user_id") ?? '';
    //     $old_password = md5($this->input->post("old_password")) ?? '';
    //     $new_password = md5($this->input->post("new_password")) ?? '';
    //     // $confirm_password = $this->input->post("confirm_password") ?? '';

    //     if (empty($user_id) || empty($old_password) || empty($new_password)) {
    //         echo json_encode([
    //             'success' => 0,
    //             'message' => 'All fields are required'
    //         ]);
    //         return;
    //     }

    //     // if ($new_password !== $confirm_password) {
    //     //     echo json_encode([
    //     //         'success' => 0,
    //     //         'message' => 'Passwords do not match'
    //     //     ]);
    //     //     return;
    //     // }

    //     $user = $this->db->get_where('users', ['id' => $user_id, 'is_active' => 1])->row();

    //     // echo json_encode($user->password);
    //     // echo json_encode($old_password);
    //     // echo json_encode($old_password == $user->password);
    //     // exit;

    //     if (!empty($user)) {
    //         if ($old_password == $user->password) {
    //             $this->db->where('id', $user_id);
    //             $result = $this->db->update('users', ['password' => $new_password]);

    //             if ($result) {
    //                 echo json_encode([
    //                     'success' => 1,
    //                     'message' => 'Password changed successfully'
    //                 ]);
    //                 return;
    //             }

    //             echo json_encode([
    //                 'success' => 0,
    //                 'message' => 'Password not Changed.'
    //             ]);
    //             return;
    //         } else {
    //             echo json_encode([
    //                 'success' => 0,
    //                 'message' => 'Old password is incorrect'
    //             ]);
    //             return;
    //         }
    //     } else {
    //         echo json_encode([
    //             'success' => 0,
    //             'message' => 'Email not found'
    //         ]);
    //         return;
    //     }
    // }


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


    // public function forgot_password()
    // {
    //     if ($this->input->method() !== 'post') {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST.']);
    //         return;
    //     }

    //     $email = $this->input->post('email');

    //     if (empty($email)) {
    //         echo json_encode(['success' => 0, 'message' => 'Email is required.']);
    //         return;
    //     }

    //     $user = $this->db->get_where("users", array("email" => $email, "deleted_at" => NULL))->row_array();

    //     if (!$user) {
    //         echo json_encode(['success' => 0, 'message' => 'Email not registered.']);
    //         return;
    //     }
    // }

    // public function generate_otp($toEmail)
    // {
    //     if ($this->input->method() !== 'post') {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST.']);
    //         return;
    //     }

    //     if (empty($toEmail)) {
    //         echo json_encode(['success' => 0, 'message' => 'Email is required.']);
    //         return;
    //     }

    //     $otp = '';
    //     $length = 6;
    //     $characters = '0123456789';

    //     for ($i = 0; $i < $length; $i++) {
    //         $otp .= $characters[rand(0, strlen($characters) - 1)];
    //     }

    //     $current = $this->get_current_time();
    //     $validTill = $current + 300;


    //     $data = [
    //         'email' => $toEmail,
    //         'otp' => $otp,
    //         'created_at' => $current,
    //         'valid_till' => $validTill
    //     ];
    //     $this->db->insert('email_otps', $data);

    //     echo json_encode([
    //         'success' => 1,
    //         'message' => 'OTP generated successfully.',
    //         'otp' => $otp,
    //         'valid_till' => $validTill
    //     ]);
    // }

    // public function verify_otp()
    // {
    //     if ($this->input->method() !== 'post') {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid HTTP method. Use POST.']);
    //         return;
    //     }

    //     $input_otp = $this->input->post('otp');

    //     if (empty($input_otp)) {
    //         echo json_encode(['success' => 0, 'message' => 'OTP is required.']);
    //         return;
    //     }

    //     $current_time = $this->get_current_time();

    //     $email_otps = $this->db
    //         ->where('otp', $input_otp)
    //         ->where('valid_till >=', $current_time)
    //         ->order_by('id', 'DESC')
    //         ->get('email_otps')
    //         ->row();

    //     if (!$email_otps) {
    //         echo json_encode(['success' => 0, 'message' => 'Invalid or expired OTP.']);
    //         return;
    //     }

    //     $this->db->where('id', $email_otps->id)->delete('email_otps');

    //     echo json_encode(['success' => 1, 'message' => 'OTP verified successfully.']);
    // }

}