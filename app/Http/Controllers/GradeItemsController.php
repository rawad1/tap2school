<?php
namespace App\Http\Controllers;

use \App\models\grade_items;
use \App\models\item_marks;

class GradeItemsController extends Controller {

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
        $this->data['breadcrumb']['Settings'] = \URL::to('/dashboard/languages');
        $this->data['users'] = $this->panelInit->getAuthUser();
        if (!isset($this->data['users']->id)) {
            return \Redirect::to('/');
        }
    }

    public function listAll() {
        if (!$this->panelInit->can(array("gradeItems.list", "gradeItems.View", "gradeItems.addItem", "gradeItems.editItem", "gradeItems.delItem", "gradeItems.itemDetailsNot", "gradeItems.showMarks", "gradeItems.controlMarksItem"))) {
            exit;
        }
        $toReturn['items'] = array();
        if ($this->data['users']->role == "student") {
            $toReturn['items'] = grade_items::where('itemAcYear', $this->panelInit->selectAcYear)->where('itemClasses', 'LIKE', '%"' . $this->data['users']->studentClass . '"%')->get()->toArray();
        } elseif ($this->data['users']->role == "parent") {

            $studentId = array();
            $parentOf = json_decode($this->data['users']->parentOf, true);
            if (is_array($parentOf)) {
                foreach ($parentOf as $value) {
                    $studentId[] = $value['id'];
                }
            }

            if (count($studentId) > 0) {
                $studentDetails = \User::where('role', 'student')->whereIn('id', $studentId)->select('studentClass');
                if ($studentDetails->count() > 0) {
                    $studentDetails = $studentDetails->get()->toArray();

                    $toReturn['items'] = grade_items::where('itemAcYear', $this->panelInit->selectAcYear)->where(function($query) use ($studentDetails) {
                                foreach ($studentDetails as $value) {
                                    $query->orWhere('itemClasses', 'LIKE', '%"' . $value['studentClass'] . '"%');
                                }
                            })->get()->toArray();
                }
            }
        } else {
            $toReturn['items'] = \App\models\grade_items::where('itemAcYear', $this->panelInit->selectAcYear)->get()->toArray();
        }

        if ($this->data['users']->role == "teacher") {
            $toReturn['classes'] = \classes::where('classAcademicYear', $this->panelInit->selectAcYear)->where('classTeacher', 'LIKE', '%"' . $this->data['users']->id . '"%')->get()->toArray();
        } else {
            $toReturn['classes'] = \classes::where('classAcademicYear', $this->panelInit->selectAcYear)->get()->toArray();
        }

        foreach ($toReturn['items'] as $key => $value) {
            $toReturn['items'][$key]['itemDate'] = $this->panelInit->unix_to_date($toReturn['items'][$key]['itemDate']);
        }

        $toReturn['subjects'] = array();
        $subjects = \subject::select('id', 'subjectTitle')->get()->toArray();
        foreach ($subjects as $value) {
            $toReturn['subjects'][$value['id']] = $value['subjectTitle'];
        }

        $toReturn['userRole'] = $this->data['users']->role;
        return $toReturn;
    }

    public function delete($id) {

        if (!$this->panelInit->can("gradeItems.delItem")) {
        //    exit;
        }

        if ($postDelete = grade_items::where('id', $id)->first()) {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true, $this->panelInit->language['delItem'], $this->panelInit->language['exDeleted']);
        } else {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['delItem'], $this->panelInit->language['exNotExist']);
        }
    }

    public function create() {

        if (!$this->panelInit->can("gradeItems.addItem")) {
            exit;
        }

        $gradeItem = new grade_items();
        $gradeItem->itemTitle = \Input::get('itemTitle');
        $gradeItem->itemDescription = \Input::get('itemDescription');
        $gradeItem->itemWeight = \Input::get('itemWeight');
        $gradeItem->itemDate = $this->panelInit->date_to_unix(\Input::get('itemDate'));
        $gradeItem->itemAcYear = $this->panelInit->selectAcYear;
        if (\Input::has('itemClasses')) {
            $gradeItem->itemClasses = json_encode(\Input::get('itemClasses'));
        }
        $gradeItem->save();


        //Send Push Notifications
        $tokens_list = array();
        $user_list = \User::where('role', 'student')->whereIn('studentClass', \Input::get('itemClasses'))->select('firebase_token')->get();

        foreach ($user_list as $value) {
            if ($value['firebase_token'] != "") {
                $tokens_list[] = $value['firebase_token'];
            }
        }

        if (count($tokens_list) > 0) {
            $this->panelInit->send_push_notification($tokens_list, $this->panelInit->language['newItemNotif'] . " : " . \Input::get('itemTitle'), $this->panelInit->language['gradeItem'], "items", $gradeItem->id);
        }

        $gradeItem->itemDate = \Input::get('itemDate');

        return $this->panelInit->apiOutput(true, $this->panelInit->language['addItem'], $this->panelInit->language['itemCreated'], $gradeItem->toArray());
    }

    function fetch($id) {

        if (!$this->panelInit->can(array("gradeItems.View", "gradeItems.editItem"))) {
            exit;
        }
        $grade_items = grade_items::where('id', $id)->first()->toArray();
        $grade_items['itemDate'] = $this->panelInit->unix_to_date($grade_items['itemDate']);
        $grade_items['itemClasses'] = json_decode($grade_items['itemClasses'], true);
        if (is_array($grade_items['itemClasses'])) {
            $grade_items['itemClassesNames'] = \classes::whereIn('id', $grade_items['itemClasses'])->select('className')->get()->toArray();
        }
        return $grade_items;
    }

    function edit($id) {

        if (!$this->panelInit->can("gradeItems.editItem")) {
            exit;
        }

        $gradeItem = grade_items::find($id);
        $gradeItem->itemTitle = \Input::get('itemTitle');
        $gradeItem->itemDescription = \Input::get('itemDescription');
        $gradeItem->itemWeight = \Input::get('itemWeight');
        $gradeItem->itemDate = $this->panelInit->date_to_unix(\Input::get('itemDate'));
        if (\Input::has('itemClasses')) {
            $gradeItem->itemClasses = json_encode(\Input::get('itemClasses'));
        }
        $gradeItem->save();

        $gradeItem->itemDate = \Input::get('itemDate');

        return $this->panelInit->apiOutput(true, $this->panelInit->language['editItem'], $this->panelInit->language['itemModified'], $gradeItem->toArray());
    }

    function fetchMarks() {

        if (!$this->panelInit->can(array("gradeItems.showMarks", "gradeItems.controlMarksItem"))) {
            exit;
        }

        $toReturn = array();

        $toReturn['item'] = grade_items::where('id', \Input::get('item'))->first()->toArray();
        $toReturn['subject'] = \subject::where('id', \Input::get('subjectId'))->first()->toArray();
        $toReturn['class'] = \classes::where('id', \Input::get('classId'))->first()->toArray();

        $toReturn['item']['itemClasses'] = json_decode($toReturn['item']['itemClasses'], true);


        $toReturn['students'] = array();
        $studentArray = \User::where('role', 'student')->where('studentClass', \Input::get('classId'));
        if ($this->panelInit->settingsArray['enableSections'] == true) {
            $studentArray = $studentArray->where('studentSection', \Input::get('sectionId'));
        }
        if ($this->data['panelInit']->settingsArray['studentsSort'] != "") {
            $studentArray = $studentArray->orderByRaw($this->data['panelInit']->settingsArray['studentsSort']);
        }
        
        $studentArray = $studentArray->get();

        $itemMarksArray = array();       
        $itemMarks = item_marks::where('itemId', \Input::get('item'))
                ->where('classId', \Input::get('classId'))
                ->where('subjectId', \Input::get('subjectId'))
                ->get();
        
        foreach ($itemMarks as $stMark) {
            $itemMarksArray[$stMark->studentId] = $stMark;
        }

        $i = 0;
        foreach ($studentArray as $stOne) {
            $toReturn['students'][$i] = array('id' => $stOne->id, 'name' => $stOne->fullName, 'studentRollId' => $stOne->studentRollId, 'itemMark' => '', 'attendanceMark' => '', 'markComments' => '');
            if (isset($itemMarksArray[$stOne->id])) {
                $toReturn['students'][$i]['itemMark'] = json_decode($itemMarksArray[$stOne->id]->itemMark, true);
                $toReturn['students'][$i]['totalMarks'] = $itemMarksArray[$stOne->id]->totalMarks;
                $toReturn['students'][$i]['maxMark'] = $itemMarksArray[$stOne->id]->maxMark;
                $toReturn['students'][$i]['markComments'] = $itemMarksArray[$stOne->id]->markComments;
            }
            $i ++;
        }

        echo json_encode($toReturn);
        exit;
    }

    function saveMarks($item, $class, $subject) {

        if (!$this->panelInit->can("gradeItems.controlMarksItem")) {
        //    exit;
        }
   
        $studentList = array();
        $studentArray = \User::where('role', 'student')->where('studentClass', $class)->get();
        foreach ($studentArray as $stOne) {
            $studentList[] = $stOne->id;
        }

        $itemMarksList = array();
        $itemMarks = item_marks::where('itemId', $item)->where('classId', $class)->where('subjectId', $subject)->get();
        foreach ($itemMarks as $stMark) {
            $itemMarksList[$stMark->studentId] = array("itemMark" => $stMark->itemMark, "attendanceMark" => $stMark->attendanceMark, "markComments" => $stMark->markComments);
        }
        
       
        $stMarks = \Input::get('respStudents');
        foreach ($stMarks as $key => $value) {
            if (!isset($itemMarksList[$value['id']])) {
                $itemMarks = new item_marks();
                $itemMarks->itemId = $item;
                $itemMarks->classId = $class;
                $itemMarks->subjectId = $subject;
                $itemMarks->studentId = $value['id'];
                $itemMarks->maxMark = $value['maxMark'];
//                if (isset($value['itemMark'])) {
//                    $itemMarks->itemMark = json_encode($value['itemMark']);
//                }
                if (isset($value['totalMarks'])) {
                    $itemMarks->totalMarks = $value['totalMarks'];
                }
                if (isset($value['markComments'])) {
                    $itemMarks->markComments = $value['markComments'];
                }
                $itemMarks->save();
    
                } else {
                $itemMarks = item_marks::where('itemId', $item)->where('classId', $class)->where('subjectId', $subject)->where('studentId', $value['id'])->first();
//                if (isset($value['itemMark'])) {
//                    $itemMarks->itemMark = json_encode($value['itemMark']);
//                }
                $itemMarks->maxMark = $value['maxMark'];
                if (isset($value['totalMarks'])) {
                    $itemMarks->totalMarks = $value['totalMarks'];
                }
                if (isset($value['markComments'])) {
                    $itemMarks->markComments = $value['markComments'];
                }
                $itemMarks->save();
            }
        }

        return $this->panelInit->apiOutput(true, $this->panelInit->language['editItem'], $this->panelInit->language['itemModified']);
    }

    function notifications($id) {

        if (!$this->panelInit->can("gradeItems.itemDetailsNot")) {
            exit;
        }

        if ($this->panelInit->settingsArray['itemDetailsNotif'] == "0") {
            return json_encode(array("jsTitle" => $this->panelInit->language['itemDetailsNot'], "jsMessage" => $this->panelInit->language['adjustItemNot']));
        }

        $gradeItem = grade_items::where('id', $id)->first()->toArray();

        $subjectArray = array();
        $subject = \subject::get();
        foreach ($subject as $value) {
            $subjectArray[$value->id] = $value->subjectTitle;
        }

        $usersArray = array();
        if ($this->data['panelInit']->settingsArray['itemDetailsNotifTo'] == "parent" || $this->data['panelInit']->settingsArray['itemDetailsNotifTo'] == "both") {
            $users = \User::where('role', 'student')->orWhere('role', 'parent')->get();
        } else {
            $users = \User::where('role', 'student')->get();
        }
        foreach ($users as $value) {
            if ($value->parentOf == "" AND $value->role == "parent")
                continue;
            if (!isset($usersArray[$value->id])) {
                $usersArray[$value->id] = array();
            }
            if ($value->parentOf != "") {
                $value->parentOf = json_decode($value->parentOf);
                if (!is_array($value->parentOf)) {
                    continue;
                }
                if (count($value->parentOf) > 0) {
                    $usersArray[$value->id]['parents'] = array();
                }
                foreach ($value->parentOf as $parentOf) {
                    $usersArray[$parentOf->id]['parents'][$value->id] = array('username' => $value->username, "email" => $value->email, "fullName" => $value->fullName, "mobileNo" => $value->mobileNo, "firebase_token" => $value->firebase_token, "comVia" => $value->comVia);
                }
            }
            $usersArray[$value->id]['student'] = array('username' => $value->username, "studentRollId" => $value->studentRollId, "mobileNo" => $value->mobileNo, "email" => $value->email, "fullName" => $value->fullName, "firebase_token" => $value->firebase_token, "comVia" => $value->comVia);
        }

        $return['marks'] = array();
        $itemMarks = item_marks::where('itemId', $id)->get();
        foreach ($itemMarks as $value) {
            if (!isset($return['marks'][$value->studentId])) {
                $return['marks'][$value->studentId] = array();
            }
            if (isset($subjectArray[$value->subjectId])) {
                $value->itemMark = json_decode($value->itemMark, true);
                $return['marks'][$value->studentId][$subjectArray[$value->subjectId]] = array("itemMark" => $value->itemMark, "maxMark" => $value->maxMark, "totalMarks" => $value->totalMarks, "markComments" => $value->markComments);
            }
        }

        $mailTemplate = \mailsms_templates::where('templateTitle', 'Item Details')->first();

        if ($this->panelInit->settingsArray['itemDetailsNotif'] == "mail" || $this->panelInit->settingsArray['itemDetailsNotif'] == "mailsms") {
            $mail = true;
        }
        if ($this->panelInit->settingsArray['itemDetailsNotif'] == "sms" || $this->panelInit->settingsArray['itemDetailsNotif'] == "mailsms") {
            $sms = true;
        }
        $sms = true;

        $MailSmsHandler = new \MailSmsHandler();
        foreach ($return['marks'] as $key => $value) {
            if (!isset($usersArray[$key]))
                continue;
            if (isset($mail)) {
                $studentTemplate = $mailTemplate->templateMail;
                $itemGradesTable = "";
                foreach ($value as $keyG => $valueG) {
                    if ((!is_array($valueG['itemMark']) || (is_array($valueG['itemMark']) AND count($valueG['itemMark']) == 0) ) AND $valueG['totalMarks'] == "") {
                        continue;
                    }
                    $itemGradesTable .= $keyG . " => ";

                    $itemGradesTable .= " - Total Marks : " . $valueG['totalMarks'] . " - Comments : " . $valueG['markComments'] . "<br/>";
                }
                if ($itemGradesTable == "") {
                    continue;
                }
                $searchArray = array("{studentName}", "{studentRoll}", "{studentEmail}", "{studentUsername}", "{itemTitle}", "{itemDescription}", "{itemDate}", "{schoolTitle}", "{itemGradesTable}");
                $replaceArray = array($usersArray[$key]['student']['fullName'], $usersArray[$key]['student']['studentRollId'], $usersArray[$key]['student']['email'], $usersArray[$key]['student']['username'], $gradeItem['itemTitle'], $gradeItem['itemDescription'], $this->panelInit->unix_to_date($gradeItem['itemDate']), $this->panelInit->settingsArray['siteTitle'], $itemGradesTable);
                $studentTemplate = str_replace($searchArray, $replaceArray, $studentTemplate);

                if (strpos($usersArray[$key]['student']['comVia'], 'mail') !== false) {
                    $MailSmsHandler->mail($usersArray[$key]['student']['email'], "Item grade details", $studentTemplate, $usersArray[$key]['student']['fullName']);
                }
                if (isset($usersArray[$key]['parents'])) {
                    foreach ($usersArray[$key]['parents'] as $keyP => $valueP) {
                        if (strpos($valueP['comVia'], 'mail') !== false) {
                            $MailSmsHandler->mail($valueP['email'], "Item grade details", $studentTemplate, $usersArray[$key]['student']['fullName']);
                        }
                    }
                }
            }

            $studentTemplate = $mailTemplate->templateSMS;
            $itemGradesTable = "";
            reset($value);
            foreach ($value as $keyG => $valueG) {
                if ((!is_array($valueG['itemMark']) || (is_array($valueG['itemMark']) AND count($valueG['itemMark']) == 0) ) AND $valueG['totalMarks'] == "") {
                    continue;
                }
                $itemGradesTable .= $keyG . " => ";

                if (is_array($gradeItem['itemMarksheetColumns'])) {
                    reset($gradeItem['itemMarksheetColumns']);
                    foreach ($gradeItem['itemMarksheetColumns'] as $key_ => $value_) {
                        if (isset($valueG['itemMark'][$value_['id']])) {
                            $itemGradesTable .= $value_['title'] . " : " . $valueG['itemMark'][$value_['id']] . " - ";
                        }
                    }
                }

                $itemGradesTable .= " - Total Marks : " . $valueG['totalMarks'] . " - Comments : " . $valueG['markComments'] . "<br/>";
            }
            if ($itemGradesTable == "") {
                continue;
            }
            $searchArray = array("{studentName}", "{studentRoll}", "{studentEmail}", "{studentUsername}", "{itemTitle}", "{itemDescription}", "{itemDate}", "{schoolTitle}", "{itemGradesTable}");
            $replaceArray = array($usersArray[$key]['student']['fullName'], $usersArray[$key]['student']['studentRollId'], $usersArray[$key]['student']['email'], $usersArray[$key]['student']['username'], $gradeItem['itemTitle'], $gradeItem['itemDescription'], $this->panelInit->unix_to_date($gradeItem['itemDate']), $this->panelInit->settingsArray['siteTitle'], $itemGradesTable);
            $studentTemplate = str_replace($searchArray, $replaceArray, $studentTemplate);

            if (isset($sms) AND $usersArray[$key]['student']['mobileNo'] != "" AND strpos($usersArray[$key]['student']['comVia'], 'sms') !== false) {
                $MailSmsHandler->sms($usersArray[$key]['student']['mobileNo'], $studentTemplate);
            }
            if ($usersArray[$key]['student']['firebase_token'] != "") {
                $this->panelInit->send_push_notification($usersArray[$key]['student']['firebase_token'], $studentTemplate, "Item grade details", "marksheet");
            }

            if (isset($usersArray[$key]['parents'])) {
                reset($usersArray[$key]['parents']);
                foreach ($usersArray[$key]['parents'] as $keyP => $valueP) {
                    if (isset($sms) AND trim($valueP['mobileNo']) != "" AND strpos($valueP['comVia'], 'sms') !== false) {
                        $MailSmsHandler->sms($valueP['mobileNo'], $studentTemplate);
                    }

                    if ($usersArray[$key]['student']['firebase_token'] != "") {
                        $this->panelInit->send_push_notification($valueP['firebase_token'], $studentTemplate, "Item grade details", "marksheet");
                    }
                }
            }
        }

        return $this->panelInit->apiOutput(true, $this->panelInit->language['itemDetailsNot'], $this->panelInit->language['itemNotSent']);
    }

}
