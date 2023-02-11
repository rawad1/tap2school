<?php

namespace App\Http\Controllers;

class LecturesController extends Controller {

    var $data = array();
    var $panelInit;
    var $layout = 'dashboard';

    public function __construct() {
        if (app('request')->header('Authorization') != "" || \Input::has('token')) {
            $this->middleware('jwt.auth');
        } else {
            $this->middleware('authApplication');
        }

        $this->panelInit = new \DashboardInit();
        $this->data['panelInit'] = $this->panelInit;
        $this->data['breadcrumb']['Settings'] = \URL::to('/dashboard/lectures');
        $this->data['users'] = $this->panelInit->getAuthUser();
        if (!isset($this->data['users']->id)) {
            return \Redirect::to('/');
        }
    }

    public function listClasses() {
        if (!$this->panelInit->can(array("Homework.list", "Homework.View", "Homework.addHomework", "Homework.editHomework", "Homework.delHomework", "Homework.Download"))) {
            // exit;
        }
        $classes = \classes::where('classAcademicYear', '3')->get();

        return json_encode($classes);
    }

    public function classSubjects($id) {
        if (!$this->panelInit->can(array("Homework.list", "Homework.View", "Homework.addHomework", "Homework.editHomework", "Homework.delHomework", "Homework.Download"))) {
            // exit;
        }
        $class = \classes::where('id', $id)->first();
        $subjectList = array();
        $class->classSubjects = json_decode($class->classSubjects, true);
        if (is_array($class->classSubjects)) {
            foreach ($class->classSubjects as $value2) {
                $subjectList[] = $value2;
            }
        }
        $subjects = \subject::whereIn('id',  $subjectList)->get()->toArray();
        return json_encode($subjects);
    }

    
    public function subjectLectures($id) {
        if (!$this->panelInit->can(array("Homework.list", "Homework.View", "Homework.addHomework", "Homework.editHomework", "Homework.delHomework", "Homework.Download"))) {
            // exit;
        }
        $class = \subject::where('id', $id)->first();
        $subjectList = array();
        $class->classSubjects = json_decode($class->classSubjects, true);
        if (is_array($class->classSubjects)) {
            foreach ($class->classSubjects as $value2) {
                $subjectList[] = $value2;
            }
        }
        $subjects = \subject::whereIn('id',  $subjectList)->get()->toArray();
        return json_encode($subjects);
    }

    
    public function offline() {
        if (!$this->panelInit->can(array("Homework.list", "Homework.View", "Homework.addHomework", "Homework.editHomework", "Homework.delHomework", "Homework.Download"))) {
            // exit;
        }

        $toReturn = array();
        //if($this->data['users']->role == "admin" ){
        $toReturn['lectures'] = \lectures::orderby('createAt', 'DESC')->get()->toArray();
        //}else{
        //	$toReturn['lectures'] = \lectures::where('eventFor',$this->data['users']->role)->orWhere('eventFor','all')->orderby('createAt','DESC')->get()->toArray();
        //}

        foreach ($toReturn['lectures'] as $key => $item) {
            $toReturn['lectures'][$key]['lectureDescription'] = strip_tags(htmlspecialchars_decode($toReturn['lectures'][$key]['lectureDescription'], ENT_QUOTES));
            $toReturn['lectures'][$key]['subjectId'] = $this->getSubject($toReturn['lectures'][$key]['subjectId'], 'subjectTitle');
            $toReturn['lectures'][$key]['classId'] = $this->getClass($toReturn['lectures'][$key]['classId'], 'className');
            $toReturn['lectures'][$key]['teacherId'] = $this->getTeacher($toReturn['lectures'][$key]['teacherId'], 'fullName');
        }

        return $toReturn;
    }

    public function online() {
        if (!$this->panelInit->can(array("Homework.list", "Homework.View", "Homework.addHomework", "Homework.editHomework", "Homework.delHomework", "Homework.Download"))) {
            // exit;
        }

        $toReturn = array();
        //if($this->data['users']->role == "admin" ){
        $toReturn['lectures'] = \lectures::orderby('createAt', 'DESC')->get()->toArray();
        //}else{
        //	$toReturn['lectures'] = \lectures::where('eventFor',$this->data['users']->role)->orWhere('eventFor','all')->orderby('createAt','DESC')->get()->toArray();
        //}

        foreach ($toReturn['lectures'] as $key => $item) {
            $toReturn['lectures'][$key]['lectureDescription'] = strip_tags(htmlspecialchars_decode($toReturn['lectures'][$key]['lectureDescription'], ENT_QUOTES));
            $toReturn['lectures'][$key]['subjectId'] = $this->getSubject($toReturn['lectures'][$key]['subjectId'], 'subjectTitle');
            $toReturn['lectures'][$key]['classId'] = $this->getClass($toReturn['lectures'][$key]['classId'], 'className');
            $toReturn['lectures'][$key]['teacherId'] = $this->getTeacher($toReturn['lectures'][$key]['teacherId'], 'fullName');
        }

        return $toReturn;
    }

    public function delete($id) {

        if (!$this->panelInit->can("lecture.delLecture")) {
            //	exit;
        }

        if ($postDelete = \lectures::find($id)) {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true, $this->panelInit->language['delLecture'], $this->panelInit->language['lectureDeleted']);
        } else {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['delLecture'], $this->panelInit->language['lectureNotEist']);
        }
    }

    public function create() {

        if (!$this->panelInit->can("lectures.addLecture")) {
//			exit;
        }

        $lectures = new \lectures();
        $lectures->lectureTitle = \Input::get('lectureTitle');
        $lectures->lectureDescription = htmlspecialchars(\Input::get('lectureDescription'), ENT_QUOTES);
        $lectures->teacherId = \Input::get('teacherId');
        $lectures->classId = \Input::get('classId');
        $lectures->subjectId = \Input::get('subjectId');
        $lectures->lectureStatus = \Input::get('lectureStatus');

        if (\Input::hasFile('url')) {
            $fileInstance = \Input::file('url');

            if (!$this->panelInit->validate_upload($fileInstance)) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['addLecture'], "Sorry, This File Type Is Not Permitted For Security Reasons ");
            }

            $newFileName = uniqid() . "." . $fileInstance->getClientOriginalExtension();
            $fileInstance->move('uploads/lectures/'. $lectures->subjectId  , $newFileName);

            $lectures->url = $newFileName;
        }

        $lectures->save();
//
//		//Send Push Notifications
//		$tokens_list = array();
//		if($events->eventFor == "all"){
//			$user_list = \User::select('firebase_token')->get();
//		}else{
//			$user_list = \User::where('role',$events->eventFor)->select('firebase_token')->get();
//		}
//		foreach ($user_list as $value) {
//			if($value['firebase_token'] != ""){
//				$tokens_list[] = $value['firebase_token'];				
//			}
//		}
//
//		if(count($tokens_list) > 0){
//			$lectureDescription = strip_tags(\Input::get('lectureDescription'));
//			$this->panelInit->send_push_notification($tokens_list,$lectureDescription,$lectures->eventTitle,"events",$lectures->id);			
//		}
//
        $lectures->lectureDescription = strip_tags(htmlspecialchars_decode($lectures->lectureDescription));
//		$lectures->eventDate = $this->panelInit->unix_to_date($events->eventDate);

        return $this->panelInit->apiOutput(true, $this->panelInit->language['addLecture'], $this->panelInit->language['lectureCreated'], $lectures->toArray());
    }

    function fetch($id) {

        if (!$this->panelInit->can(array("lectures.View", "lectures.editLecture"))) {
//			exit;
        }

        $data = \lectures::where('id', $id)->first()->toArray();
        $data['lectureDescription'] = htmlspecialchars_decode($data['lectureDescription'], ENT_QUOTES);
        $data['createAt'] = $data['createAt'];
        return json_encode($data);
    }

    function edit($id) {
        if (!$this->panelInit->can("lectures.editLecture")) {
            //exit;
        }

        $lectures = \lectures::where('id', $id)->first();
        $lectures->lectureTitle = \Input::get('lectureTitle');
        $lectures->lectureDescription = htmlspecialchars(\Input::get('lectureDescription'), ENT_QUOTES);
        $lectures->teacherId = \Input::get('teacherId');
        $lectures->classId = \Input::get('classId');
        $lectures->subjectId = \Input::get('subjectId');
        $lectures->lectureStatus = \Input::get('lectureStatus');

        if (\Input::hasFile('url')) {
            $fileInstance = \Input::file('url');

            if (!$this->panelInit->validate_upload($fileInstance)) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['editLecture'], "Sorry, This File Type Is Not Permitted For Security Reasons ");
            }

            $newFileName = uniqid() . "." . $fileInstance->getClientOriginalExtension();
            $fileInstance->move('uploads/lectures/', $newFileName);

            $lectures->url = $newFileName;
        }

        $lectures->save();
        $lectures->subjectId = $this->getSubject($lectures->subjectId, 'subjectTitle');
        $lectures->classId = $this->getClass($lectures->classId, 'className');
        $lectures->teacherId = $this->getTeacher($lectures->teacherId, 'fullName');
        $lectures->lectureDescription = strip_tags(htmlspecialchars_decode($lectures->lectureDescription));


        return $this->panelInit->apiOutput(true, $this->panelInit->language['editLecture'], $this->panelInit->language['lectureModified'], $lectures->toArray());
    }

    function fe_active($id) {

        if (!$this->panelInit->can("events.editEvent")) {
            exit;
        }

        $events = \lectures::find($id);

        if ($events->status == 1) {
            $events->status = 0;
        } else {
            $events->status = 1;
        }

        $events->save();

        return $this->panelInit->apiOutput(true, $this->panelInit->language['editLecture'], $this->panelInit->language['lectureModified'], array("status" => $events->status));
    }

    function play($id) {
        if (!$this->panelInit->can("lectures.playLecture")) {
            //exit;
        }
        $lecture = \lectures::where('id', $id)->first();
        if (file_exists('uploads/lectures/' . $lecture->url)) {
            $lecture->url = '/uploads/lectures/' . $lecture->url;
            $data['status'] = true;
        } else {
            $data['status'] = false;
        }
        $data['lecture'] = $lecture;
        return json_encode($data);
    }

    function getTeacher($id, $attr) {
        $model = \User::where('id', $id)->first();
        if ($model) {
            return $model->$attr;
        }
        return 'not-found';
    }

    function getSubject($id, $attr) {
        $model = \subject::where('id', $id)->first();
        if ($model) {
            return $model->$attr;
        }
        return 'not-found';
    }

    function getClass($id, $attr) {
        $model = \classes::where('id', $id)->first();
        if ($model) {
            return $model->$attr;
        }
        return 'not-found';
    }

    public function teachersList() {
        if ($this->data['users']->role == "teacher") {
            return \User::where('id', $id)->get()->toArray();
        } else {
            return \User::whereIn('role', 'teacher')->get()->toArray();
        }
    }

}
