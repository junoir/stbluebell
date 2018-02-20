<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Activities extends Admin_Controller {
/*
| -----------------------------------------------------
| PRODUCT NAME: 	INILABS SCHOOL MANAGEMENT SYSTEM
| -----------------------------------------------------
| AUTHOR:			INILABS TEAM
| -----------------------------------------------------
| EMAIL:			info@inilabs.net
| -----------------------------------------------------
| COPYRIGHT:		RESERVED BY INILABS IT
| -----------------------------------------------------
| WEBSITE:			http://iNilabs.net
| -----------------------------------------------------
*/
	function __construct() {
		parent::__construct();
		$this->load->model("activities_m");
		$this->load->model("activitiescategory_m");
		$this->load->model("activitiesstudent_m");
		$this->load->model("activitiesmedia_m");
		$this->load->model("activitiescomment_m");
		$this->load->model("student_m");
		$this->load->model("classes_m");
		$language = $this->session->userdata('lang');
		$this->lang->load('activities', $language);
        $this->load->helper('date');
	}

	public function index() {
        $allUserTypes = $this->usertype_m->get_usertype();
		$this->data['activitiescategories'] = $this->activitiescategory_m->get_activitiescategory();
		$this->data['activities'] = $this->activities_m->get_activities_data();
        $this->data['allusertype'] = pluck($allUserTypes, 'usertype', 'usertypeID');
        $this->data['usertypeID'] = $this->session->userdata('usertypeID');
        $this->data['userID'] = $this->session->userdata('loginuserID');
        $usertypeID = $this->session->userdata("usertypeID");
        $userID = $this->session->userdata("loginuserID");
        if ($_POST) {
            $id = $this->uri->segment(3);
            if ((int)$id) {
                if ($_POST['comment']) {
                    $array = array();
                    $array['activitiesID'] = $id;
                    $array['comment'] = $this->input->post('comment');
                    $array['userID'] = $userID;
                    $array['usertypeID'] = $usertypeID;
                    $array['create_date'] = date("Y-m-d h:i:s");
                    $this->activitiescomment_m->insert_activitiescomment($array);
                    $this->session->set_flashdata('success', $this->lang->line("menu_success"));
                    redirect(base_url("activities/index"));
                }
            }
        }
		$this->data["subview"] = "activities/index";
		$this->load->view('_layout_main', $this->data);
	}

	protected function rules() {
		$rules = array(
                    array(
                        'field' => 'description',
                        'label' => $this->lang->line("activities_description"),
                        'rules' => 'trim|required|xss_clean'
                    ),
                    array(
                        'field' => 'attachment',
                        'label' => $this->lang->line("activities_attachment"),
                        'rules' => 'trim|xss_clean'
                    ),
                    array(
                        'field' => 'time_from',
                        'label' => $this->lang->line("activities_time_from"),
                        'rules' => 'trim|max_length[10]|xss_clean'
                    ),
                    array(
                        'field' => 'time_to',
                        'label' => $this->lang->line("activities_time_to"),
                        'rules' => 'trim|max_length[10]|xss_clean'
                    ),
                    array(
                        'field' => 'time_at',
                        'label' => $this->lang->line("activities_time_at"),
                        'rules' => 'trim|max_length[10]|xss_clean'
                    ),
                    array(
                        'field' => 'students[]',
                        'label' => $this->lang->line("activities_students"),
                        'rules' => 'trim|xss_clean|callback_activities_students'
                    )
                );
		return $rules;
	}

	function activities_students() {
        $students = array_filter($this->input->post("students"));
        if(count($students) <= 0) {
            $this->form_validation->set_message("activities_students", "The %s is already exists.");
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function single_student_info() {
        $id = $this->input->post('id');
        if((int)$id) {
            $this->data['student_info'] = $this->student_m->get_student($id);
            return $this->load->view('activities/student_info', $this->data);
        } else {
            return "";
        }
    }

	public function add() {
        $this->data['headerassets'] = array(
            'css' => array(
                'assets/datepicker/datepicker.css',
                'assets/timepicker/timepicker.css',
                'assets/select2/css/select2.css',
                'assets/select2/css/select2-bootstrap.css',
                'assets/tooltipster/css/tooltipster.bundle.min.css'
            ),
            'js' => array(
                'assets/datepicker/datepicker.js',
                'assets/select2/select2.js',
                'assets/tooltipster/js/tooltipster.bundle.min.js',
                'assets/timepicker/timepicker.js'
            )
        );
        $categoryID= $this->uri->segment(3);

        if ((int)$categoryID) {
            $this->data['activities_categories'] = $this->activitiescategory_m->get_activitiescategory();
            $this->data['students'] = $this->student_m->get_order_by_student(['schoolYearID' => $this->session->userdata('defaultschoolyearID')]);

            if($_POST) {
                $rules = $this->rules();

                $this->form_validation->set_rules($rules);
                if ($this->form_validation->run() == FALSE) {
                    $this->data["subview"] = "activities/add";
                    $this->load->view('_layout_main', $this->data);
                } else {
                    $array = array(
                        "description" => $this->input->post("description"),
                        "activitiescategoryID" => $categoryID,
                        "schoolyearID" => $this->data['siteinfos']->school_year,
                        "usertypeID" => $this->session->userdata('usertypeID'),
                        "userID" => $this->session->userdata('loginuserID'),
                    );
                    if ($this->input->post("time_to")!="0:00"){
                        $array["time_to"] = $this->input->post("time_to");
                    }
                    if ($this->input->post("time_from")!="0:00"){
                        $array["time_from"] = $this->input->post("time_from");
                    }
                    if ($this->input->post("time_at")!="0:00"){
                        $array["time_at"] = $this->input->post("time_at");
                    }
                    $array["create_date"] = date("Y-m-d h:i:s");
                    $array["modify_date"] = date("Y-m-d h:i:s");

                    $id = $this->activities_m->insert_activities($array);
                    if ((int)$id) {
                        $students = array_filter($this->input->post("students"));
                        foreach ($students as $student) {
                            $student_info = $this->student_m->get_student($student);
                            $activitiesstudent = array(
                                "activitiesID" => $id,
                                "studentID" => $student,
                                "classesID" => $student_info->classesID,
                            );
                            $this->activitiesstudent_m->insert_activitiesstudent($activitiesstudent);
                        }
                    }
                    // attachment upload and save to db
                    if(!empty($_FILES['attachment']['name'])){
                        $filesCount = count($_FILES['attachment']['name']);
                        dump($filesCount);
                        for($i = 0; $i < $filesCount; $i++){
                            $_FILES['attach']['name'] = $_FILES['attachment']['name'][$i];
                            $_FILES['attach']['type'] = $_FILES['attachment']['type'][$i];
                            $_FILES['attach']['tmp_name'] = $_FILES['attachment']['tmp_name'][$i];
                            $_FILES['attach']['error'] = $_FILES['attachment']['error'][$i];
                            $_FILES['attach']['size'] = $_FILES['attachment']['size'][$i];

                            $uploadPath = 'uploads/activities/';
                            $config['upload_path'] = $uploadPath;
                            $config['allowed_types'] = 'gif|jpg|png';

                            $this->load->library('upload', $config);
                            $this->upload->initialize($config);
                            if($this->upload->do_upload('attach')){
                                $fileData = $this->upload->data();
                                $uploadData[$i]['attachment'] = $fileData['file_name'];
                                $uploadData[$i]['activitiesID'] = $id;
                                $uploadData[$i]['create_date'] = date("Y-m-d H:i:s");
                            }
                        }
                        if(!empty($uploadData)){
                            //Insert file information into the database
                            $this->activitiesmedia_m->insert_batch_activitiesmedia($uploadData);
                        }
                    }
                    // end attachment upload

                    $this->session->set_flashdata('success', $this->lang->line('menu_success'));
                    redirect(base_url("activities/index"));
                }
            } else {
                $this->data["subview"] = "activities/add";
                $this->load->view('_layout_main', $this->data);
            }
        } else {

        }
	}

	public function delete() {
		$id = htmlentities(escapeString($this->uri->segment(3)));
        $usertypeID = $this->session->userdata('usertypeID');
        $userID = $this->session->userdata('loginuserID');

		if((int)$id) {
            $activities = $this->activities_m->get_activities($id);
            if(($usertypeID == $activities->usertypeID && $userID == $activities->userID) || ($usertypeID == 1)) {
                $this->activities_m->delete_activities($id);
                $this->session->set_flashdata('success', $this->lang->line('menu_success'));
            }
			redirect(base_url("activities/index"));
		} else {
			redirect(base_url("activities/index"));
		}
	}

	public function delete_comment() {
		$id = htmlentities(escapeString($this->uri->segment(3)));
        $usertypeID = $this->session->userdata('usertypeID');
        $userID = $this->session->userdata('loginuserID');

        if((int)$id) {
            $comment = $this->activitiescomment_m->get_activitiescomment($id);
            $activities = $this->activities_m->get_activities($comment->activitiesID);
            if(($usertypeID == $activities->usertypeID && $userID == $activities->userID) || ($usertypeID == 1)) {
                $this->activitiescomment_m->delete_activitiescomment($id);
                $this->session->set_flashdata('success', $this->lang->line('menu_success'));
            }
			redirect(base_url("activities/index"));
		} else {
			redirect(base_url("activities/index"));
		}
	}

}

/* End of file activities.php */
/* Location: .//D/xampp/htdocs/school/mvc/controllers/activities.php */