<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\URL;
use \App\models\grade_items;
use \App\models\item_marks;

class OverviewController extends Controller {

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

    public function details($id) {
        $subject = \subject::where('id', $id)->get()->first();
        $classes = \classes::where('classSubjects', 'like', '%"' . $id . '"%')->where('classAcademicYear', $this->panelInit->selectAcYear)->get();
        $teachers = \User::where('role', 'teacher')->whereIn('id', json_decode($subject['teacherId']))->get();

        foreach ($teachers as $teacher) {
            $teacher->photo = URL::asset('/dashboard/profileImage/' . $teacher->id);
        }
        $images = [
            'overview' => URL::asset('assets/images/subject/overview-icon.svg'),
            'content' => URL::asset('assets/images/subject/content-icon.svg'),
            'assignments' => URL::asset('assets/images/subject/assignments-icon.svg'),
            'quizzes' => URL::asset('assets/images/subject/quizzes-icon.svg'),
            'posts' => URL::asset('assets/images/subject/posts-icon.svg'),
            'gradeItems' => URL::asset('assets/images/subject/gradeItems-icon.png'),
            'lectures' => URL::asset('assets/images/subject/lectures-icon.png'),
        ];
        return [
            'subject' => $subject,
            'classes' => $classes,
            'teachers' => $teachers,
            'images' => $images
        ];
    }

    public function content($id) {
        $subject = \subject::where('id', $id)->get()->first();
        $teachers = \User::where('role', 'teacher')->whereIn('id', json_decode($subject['teacherId']))->get();

        $toReturn = array();

        $toReturn['classes'] = \classes::where('classSubjects', 'like', '%"' . $id . '"%')->where('classAcademicYear', $this->panelInit->selectAcYear)->get()->toArray();
        $classesArray = array();
        $classIds = [];
        foreach ($toReturn['classes'] as $class) {
            $classIds[] = $class['id'];
            $classesArray[$class['id']] = $class['className'];
        }

        $subjectArray[$subject['id']] = $subject['subjectTitle'];

        $toReturn['materials'] = array();
        $studyMaterial = new \study_material();
        $studyMaterial = $studyMaterial->where('subject_id', $id);
        if ($this->data['users']->role == "student") {
            foreach ($classIds as $class_id) {
                $studyMaterial = $studyMaterial->orWhere('class_id', 'like', '%"' . $class_id . '"%');
            }
            if ($this->panelInit->settingsArray['enableSections'] == true) {
                $studyMaterial = $studyMaterial->where('sectionId', 'LIKE', '%"' . $this->data['users']->studentSection . '"%');
            }
        } elseif ($this->data['users']->role == "parent") {

            if (!is_array($this->data['users']->parentOf)) {
                $parentOf = json_decode($this->data['users']->parentOf, true);
            } else {
                $parentOf = $this->data['users']->parentOf;
            }

            if (!is_array($parentOf) || count($parentOf) == 0) {
                return array();
            }

            $std_id = array();
            foreach ($parentOf as $key => $value) {
                $std_id[] = $value['id'];
            }

            $students = \User::whereIn('id', $std_id)->select('studentClass', 'studentSection');

            if ($students->count() > 0) {

                $classes = array();
                $sections = array();
                $students = $students->get();
                foreach ($students as $key => $value) {
                    $classes[] = $value->studentClass;
                    $sections[] = $value->studentSection;
                }

                if (count($classes) == 0) {
                    return array();
                } else {
                    $studyMaterial = $studyMaterial->where(function($query) use ($classes) {
                        foreach ($classes as $key => $value) {
                            $query->orWhere('class_id', 'LIKE', '%"' . $value . '"%');
                        }
                    });

                    if ($this->panelInit->settingsArray['enableSections'] == true AND count($sections) > 0) {
                        $studyMaterial = $studyMaterial->where(function($query) use ($sections) {
                            foreach ($sections as $key => $value) {
                                $query->orWhere('sectionId', 'LIKE', '%"' . $value . '"%');
                            }
                        });
                    }
                }
            } else {
                return array();
            }
        } elseif ($this->data['users']->role == "teacher") {
            $studyMaterial = $studyMaterial->where('teacher_id', $this->data['users']->id);
        }

        $toReturn['totalItems'] = $studyMaterial->count();
        $studyMaterial = $studyMaterial->get();

        foreach ($studyMaterial as $key => $material) {
            $classId = json_decode($material->class_id);
            if ($this->data['users']->role == "student" AND ! in_array($this->data['users']->studentClass, $classId)) {
                continue;
            }
            $toReturn['materials'][$key]['id'] = $material->id;
            $toReturn['materials'][$key]['subjectId'] = $material->subject_id;
            if (isset($subjectArray[$material->subject_id])) {
                $toReturn['materials'][$key]['subject'] = $subjectArray[$material->subject_id];
            } else {
                $toReturn['materials'][$key]['subject'] = "";
            }
            $toReturn['materials'][$key]['material_title'] = $material->material_title;
            $toReturn['materials'][$key]['material_description'] = $material->material_description;
            $toReturn['materials'][$key]['material_file'] = $material->material_file;
            $toReturn['materials'][$key]['classes'] = "";

            if (is_array($classId)) {
                foreach ($classId as $value) {
                    if (isset($classesArray[$value])) {
                        $toReturn['materials'][$key]['classes'] .= $classesArray[$value] . ", ";
                    }
                }
            }
        }

        return $toReturn;
    }

    public function createMaterial() {

        if (!$this->panelInit->can("studyMaterial.addMaterial")) {
            exit;
        }

        $studyMaterial = new \study_material();
        $studyMaterial->class_id = json_encode(\Input::get('class_id'));
        if ($this->panelInit->settingsArray['enableSections'] == true) {
            $studyMaterial->sectionId = json_encode(\Input::get('sectionId'));
        }
        $studyMaterial->subject_id = \Input::get('subject_id');
        $studyMaterial->material_title = \Input::get('material_title');
        $studyMaterial->material_description = \Input::get('material_description');
        $studyMaterial->teacher_id = $this->data['users']->id;
        $studyMaterial->save();
        if (\Input::hasFile('material_file')) {
            $fileInstance = \Input::file('material_file');

            if (!$this->panelInit->validate_upload($fileInstance)) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['addMaterial'], "Sorry, This File Type Is Not Permitted For Security Reasons ");
            }

            $newFileName = "material_" . uniqid() . "." . $fileInstance->getClientOriginalExtension();
            $fileInstance->move('uploads/studyMaterial/', $newFileName);

            $studyMaterial->material_file = $newFileName;
            $studyMaterial->save();
        }

        //Send Push Notifications
        $tokens_list = array();
        $user_list = \User::where('role', 'student')->whereIn('studentClass', \Input::get('class_id'));
        if ($this->panelInit->settingsArray['enableSections'] == true) {
            $user_list = $user_list->whereIn('studentSection', \Input::get('sectionId'));
        }
        $user_list = $user_list->select('firebase_token')->get();

        foreach ($user_list as $value) {
            if ($value['firebase_token'] != "") {
                $tokens_list[] = $value['firebase_token'];
            }
        }

        if (count($tokens_list) > 0) {
            $this->panelInit->send_push_notification($tokens_list, $this->panelInit->language['materialNotif'] . " : " . \Input::get('material_title'), $this->panelInit->language['studyMaterial'], "material");
        }

        return $this->panelInit->apiOutput(true, $this->panelInit->language['addMaterial'], $this->panelInit->language['materialAdded'], $studyMaterial->toArray());
    }

    function editMaterial($id) {

        if (!$this->panelInit->can("studyMaterial.editMaterial")) {
            exit;
        }

        $studyMaterial = \study_material::find($id);
        $studyMaterial->class_id = json_encode(\Input::get('class_id'));
        if ($this->panelInit->settingsArray['enableSections'] == true) {
            $studyMaterial->sectionId = json_encode(\Input::get('sectionId'));
        }
        $studyMaterial->subject_id = \Input::get('subject_id');
        $studyMaterial->material_title = \Input::get('material_title');
        $studyMaterial->material_description = \Input::get('material_description');
        if (\Input::hasFile('material_file')) {
            $fileInstance = \Input::file('material_file');

            if (!$this->panelInit->validate_upload($fileInstance)) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['editMaterial'], "Sorry, This File Type Is Not Permitted For Security Reasons ");
            }
            @unlink("uploads/studyMaterial/" . $studyMaterial->material_file);

            $newFileName = "material_" . uniqid() . "." . $fileInstance->getClientOriginalExtension();
            $fileInstance->move('uploads/studyMaterial/', $newFileName);

            $studyMaterial->material_file = $newFileName;
        }
        $studyMaterial->save();
        return $this->panelInit->apiOutput(true, $this->panelInit->language['editMaterial'], $this->panelInit->language['materialEdited'], $studyMaterial->toArray());
    }

    public function deleteMaterial($id) {

        if (!$this->panelInit->can("studyMaterial.delMaterial")) {
            exit;
        }

        if ($postDelete = \study_material::where('id', $id)->first()) {
            @unlink('uploads/studyMaterial/' . $postDelete->material_file);
            $postDelete->delete();
            return $this->panelInit->apiOutput(true, $this->panelInit->language['delMaterial'], $this->panelInit->language['materialDel']);
        } else {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['delMaterial'], $this->panelInit->language['materialNotExist']);
        }
    }

    public function assignments($id) {
        $subject = \subject::where('id', $id)->get()->first();
        $teachers = \User::where('role', 'teacher')->whereIn('id', json_decode($subject['teacherId']))->get();


        if (!$this->panelInit->can(array("Assignments.list", "Assignments.AddAssignments", "Assignments.editAssignment", "Assignments.delAssignment", "Assignments.viewAnswers", "Assignments.applyAssAnswer", "Assignments.Download"))) {
            exit;
        }

        $toReturn = array();

        $toReturn['classes'] = \classes::where('classSubjects', 'like', '%"' . $id . '"%')->where('classAcademicYear', $this->panelInit->selectAcYear)->get();

        $classesArray = array();
        $classIds = [];
        foreach ($toReturn['classes'] as $class) {
            $classIds[] = $class['id'];
            $classesArray[$class['id']] = $class['className'];
        }

        $assignments = new \assignments();
        $assignments = $assignments->where('subjectId', $id);
        $toReturn['assignments'] = array();
        if (count($classesArray) > 0) {


            if ($this->data['users']->role == "teacher") {

                $assignments = $assignments->where('teacherId', $this->data['users']->id)->where(function($query) use ($classesArray) {
                    foreach ($classesArray as $key => $value) {
                        $query = $query->orWhere('classId', 'LIKE', '%"' . $key . '"%');
                    }
                });
                $toReturn['totalItems'] = $assignments->count();
                $assignments = $assignments->orderByRaw('AssignDeadLine + 0 Desc')->get();
            } elseif ($this->data['users']->role == "student") {

                foreach ($classIds as $class_id) {
                    $assignments = $assignments->orWhere('classId', 'like', '%"' . $class_id . '"%');
                }
                $assignments = $assignments->where('classId', 'LIKE', '%"' . $this->data['users']->studentClass . '"%');
                if ($this->panelInit->settingsArray['enableSections'] == true) {
                    $assignments = $assignments->where('sectionId', 'LIKE', '%"' . $this->data['users']->studentSection . '"%');
                }
                $toReturn['totalItems'] = $assignments->count();
                $assignments = $assignments->orderByRaw('AssignDeadLine + 0 Desc')->get();
            } elseif ($this->data['users']->role == "parent") {

                $parentOf = json_decode($this->data['users']->parentOf, true);
                if (!is_array($parentOf)) {
                    $parentOf = array();
                }
                $ids = array();
                foreach ($parentOf as $value) {
                    $ids[] = $value['id'];
                }

                if (count($ids) == 0) {
                    unset($assignments);
                }

                if (count($ids) > 0) {
                    $classArray = array();
                    $sectionArray = array();
                    $studentArray = \User::where('role', 'student')->whereIn('id', $ids)->select('id', 'fullName', 'studentClass', 'studentSection')->get();
                    foreach ($studentArray as $stOne) {
                        $students[$stOne->id] = array('id' => $stOne->id, 'fullName' => $stOne->fullName, 'studentClass' => $stOne->studentClass, 'studentSection' => $stOne->studentSection);
                        $classArray[] = $stOne->studentClass;
                        if ($this->panelInit->settingsArray['enableSections'] == true) {
                            $sectionArray[] = $stOne->studentSection;
                        }
                    }

                    if (count($classArray) > 0) {
                        $assignments = $assignments->where(function($query) use ($classArray) {
                            foreach ($classArray as $value) {
                                $query = $query->orWhere('classId', 'LIKE', '%"' . $value . '"%');
                            }
                        });
                    }

                    if (count($sectionArray) > 0) {
                        $assignments = $assignments->where(function($query) use ($sectionArray) {
                            foreach ($sectionArray as $value) {
                                $query = $query->orWhere('sectionId', 'LIKE', '%"' . $value . '"%');
                            }
                        });
                    }
                    $toReturn['totalItems'] = $assignments->count();
                    $assignments = $assignments->orderByRaw('AssignDeadLine + 0 Desc')->take('20')->skip(20 * ($page - 1))->get();

                    $assignmentsIds = array();
                    foreach ($assignments as $assignment) {
                        $assignmentsIds[] = $assignment->id;
                        $toReturn['assignmentsAnswers'][$assignment->id] = array();

                        $classId = json_decode($assignment->classId, true);
                        $sectionId = json_decode($assignment->sectionId, true);

                        reset($students);
                        foreach ($students as $student) {
                            if (is_array($classId) AND in_array($student['studentClass'], $classId)) {
                                if (is_array($sectionId) AND in_array($student['studentSection'], $sectionId)) {
                                    $toReturn['assignmentsAnswers'][$assignment->id][$student['id']] = $student;
                                }
                            }
                        }
                    }

                    if (count($assignmentsIds) > 0) {
                        $assignmentsAnswers = \DB::table('assignments_answers')
                                ->leftJoin('users', 'users.id', '=', 'assignments_answers.userId')
                                ->select('assignments_answers.id as id',
                                        'assignments_answers.userId as userId',
                                        'assignments_answers.assignmentId as assignmentId',
                                        'assignments_answers.userNotes as userNotes',
                                        'assignments_answers.userTime as userTime',
                                        'assignments_answers.fileName as AssignFile',
                                        'users.fullName as fullName')
                                ->whereIn('assignments_answers.userId', $ids)
                                ->whereIn('assignments_answers.assignmentId', $assignmentsIds)
                                ->get();

                        foreach ($assignmentsAnswers as $answer) {
                            $toReturn['assignmentsAnswers'][$answer->assignmentId][$answer->userId] = $answer;
                            $toReturn['assignmentsAnswers'][$answer->assignmentId][$answer->userId]->userTime = $this->panelInit->unix_to_date($answer->userTime);
                        }
                    }
                }
            } else {
                $assignments = $assignments->where(function($query) use ($classesArray) {
                    foreach ($classesArray as $key => $value) {
                        $query = $query->orWhere('classId', 'LIKE', '%"' . $key . '"%');
                    }
                });
                $toReturn['totalItems'] = $assignments->count();
                $assignments = $assignments->orderByRaw('AssignDeadLine + 0 Desc')->take('20')->skip(20 * ($page - 1))->get();
            }

            $toReturn['userRole'] = $this->data['users']->role;

            if (!isset($assignments)) {
                return $toReturn;
            }

            foreach ($assignments as $key => $assignment) {
                $classId = json_decode($assignment->classId);
                if ($this->data['users']->role == "student" AND ! in_array($this->data['users']->studentClass, $classId)) {
                    continue;
                }
                $toReturn['assignments'][$key]['id'] = $assignment->id;
                $toReturn['assignments'][$key]['subjectId'] = $assignment->subjectId;
                $toReturn['assignments'][$key]['AssignTitle'] = $assignment->AssignTitle;
                $toReturn['assignments'][$key]['AssignDescription'] = $assignment->AssignDescription;
                $toReturn['assignments'][$key]['AssignFile'] = $assignment->AssignFile;
                $toReturn['assignments'][$key]['AssignDeadLine'] = $this->panelInit->unix_to_date($assignment->AssignDeadLine);
                $toReturn['assignments'][$key]['classes'] = "";

                foreach ($classId as $value) {
                    if (isset($classesArray[$value])) {
                        $toReturn['assignments'][$key]['classes'] .= $classesArray[$value] . ", ";
                    }
                }
            }
        }

        $toReturn['totalItems'] = $assignments->count();
        return $toReturn;
    }

    public function deleteAssignment($id) {

        if (!$this->panelInit->can("Assignments.delAssignment")) {
            exit;
        }

        if ($postDelete = \assignments::where('id', $id)->first()) {
            @unlink("uploads/assignments/" . $postDelete->AssignFile);
            $postDelete->delete();
            return $this->panelInit->apiOutput(true, $this->panelInit->language['delAssignment'], $this->panelInit->language['assignemntDel']);
        } else {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['delAssignment'], $this->panelInit->language['assignemntNotExist']);
        }
    }

    public function createAssignment() {

        if (!$this->panelInit->can("Assignments.AddAssignments")) {
            exit;
        }

        $assignments = new \assignments();
        $assignments->classId = json_encode(\Input::get('classId'));
        if ($this->panelInit->settingsArray['enableSections'] == true) {
            $assignments->sectionId = json_encode(\Input::get('sectionId'));
        }
        $assignments->subjectId = \Input::get('subjectId');
        $assignments->teacherId = \Input::get('teacherId');
        $assignments->AssignTitle = \Input::get('AssignTitle');
        $assignments->AssignDescription = \Input::get('AssignDescription');
        $assignments->AssignDeadLine = $this->panelInit->date_to_unix(\Input::get('AssignDeadLine'));
        $assignments->teacherId = $this->data['users']->id;
        $assignments->save();
        if (\Input::hasFile('AssignFile')) {
            $fileInstance = \Input::file('AssignFile');

            if (!$this->panelInit->validate_upload($fileInstance)) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['AddAssignments'], "Sorry, This File Type Is Not Permitted For Security Reasons ");
            }

            $newFileName = "assignments_" . uniqid() . "." . $fileInstance->getClientOriginalExtension();
            $fileInstance->move('uploads/assignments/', $newFileName);

            $assignments->AssignFile = $newFileName;
            $assignments->save();
        }

        //Send Push Notifications
        $tokens_list = array();
        $user_list = \User::where('role', 'student')->whereIn('studentClass', \Input::get('classId'));
        if ($this->panelInit->settingsArray['enableSections'] == true) {
            $user_list = $user_list->whereIn('studentSection', \Input::get('sectionId'));
        }
        $user_list = $user_list->select('firebase_token')->get();

        foreach ($user_list as $value) {
            if ($value['firebase_token'] != "") {
                $tokens_list[] = $value['firebase_token'];
            }
        }

        if (count($tokens_list) > 0) {
            $this->panelInit->send_push_notification($tokens_list, \Input::get('AssignTitle'), $this->panelInit->language['newAssigmentAdded'], "assignment", $assignments->id);
        }

        $assignments->AssignDeadLine = \Input::get('AssignDeadLine');

        return $this->panelInit->apiOutput(true, $this->panelInit->language['AddAssignments'], $this->panelInit->language['assignmentCreated'], $assignments->toArray());
    }

    function fetchAssignment($id) {

        if (!$this->panelInit->can(array("Assignments.editAssignment", "Assignments.viewAnswers", "Assignments.applyAssAnswer"))) {
            exit;
        }

        $toReturn = \assignments::where('id', $id)->first();
        $DashboardController = new DashboardController();
        $toReturn['sections'] = $DashboardController->sectionsList(json_decode($toReturn->classId, true));
        $toReturn['subject'] = $DashboardController->subjectList(json_decode($toReturn->classId, true));
        $toReturn->classId = json_decode($toReturn->classId, true);
        $toReturn->AssignDeadLine = $this->panelInit->unix_to_date($toReturn->AssignDeadLine);
        return $toReturn;
    }

    function editAssignment($id) {

        if (!$this->panelInit->can(array("Assignments.editAssignment", "Assignments.viewAnswers", "Assignments.applyAssAnswer"))) {
            exit;
        }

        $assignments = \assignments::find($id);
        $assignments->classId = json_encode(\Input::get('classId'));
        if ($this->panelInit->settingsArray['enableSections'] == true) {
            $assignments->sectionId = json_encode(\Input::get('sectionId'));
        }
        $assignments->subjectId = \Input::get('subjectId');
        $assignments->AssignTitle = \Input::get('AssignTitle');
        $assignments->AssignDescription = \Input::get('AssignDescription');
        $assignments->AssignDeadLine = $this->panelInit->date_to_unix(\Input::get('AssignDeadLine'));
        if (\Input::hasFile('AssignFile')) {
            $fileInstance = \Input::file('AssignFile');

            if (!$this->panelInit->validate_upload($fileInstance)) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['editAssignment'], "Sorry, This File Type Is Not Permitted For Security Reasons ");
            }
            @unlink("uploads/assignments/" . $assignments->AssignFile);

            $newFileName = "assignments_" . uniqid() . "." . $fileInstance->getClientOriginalExtension();
            $fileInstance->move('uploads/assignments/', $newFileName);

            $assignments->AssignFile = $newFileName;
        }
        $assignments->save();

        $assignments->AssignDeadLine = \Input::get('AssignDeadLine');

        return $this->panelInit->apiOutput(true, $this->panelInit->language['editAssignment'], $this->panelInit->language['assignmentModified'], $assignments->toArray());
    }

    public function downloadAssignment($id) {

        if (!$this->panelInit->can(array("Assignments.Download"))) {
            exit;
        }

        $toReturn = \assignments::where('id', $id)->first();
        if (file_exists('uploads/assignments/' . $toReturn->AssignFile)) {
            $fileName = preg_replace('/[^a-zA-Z0-9-_\.]/', '-', $toReturn->AssignTitle) . "." . pathinfo($toReturn->AssignFile, PATHINFO_EXTENSION);
            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=" . $fileName);
            echo file_get_contents('uploads/assignments/' . $toReturn->AssignFile);
        } else {
            echo "<br/><br/><br/><br/><br/><center>File not exist, Please contact site administrator to reupload it again.</center>";
        }
        exit;
    }

    function checkAssignmentUpload() {

        if (!$this->panelInit->can(array("Assignments.editAssignment", "Assignments.viewAnswers", "Assignments.applyAssAnswer"))) {
            exit;
        }

        $toReturn = \assignments::where('id', \Input::get('assignmentId'))->first();

        if ($toReturn->AssignDeadLine < time()) {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['applyAssAnswer'], $this->panelInit->language['assDeadTime']);
        }

        $assignmentsAnswers = \assignments_answers::where('assignmentId', \Input::get('assignmentId'))->where('userId', $this->data['users']->id)->count();
        if ($assignmentsAnswers > 0) {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['applyAssAnswer'], $this->panelInit->language['assAlreadySub']);
        }
        return array("canApply" => "true");
    }

    function uploadAssignment($id) {

        if (!$this->panelInit->can(array("Assignments.viewAnswers", "Assignments.applyAssAnswer"))) {
            exit;
        }

        $assignmentsAnswers = new \assignments_answers();
        $assignmentsAnswers->assignmentId = $id;
        $assignmentsAnswers->userId = $this->data['users']->id;
        $assignmentsAnswers->userNotes = \Input::get('userNotes');
        $assignmentsAnswers->userTime = time();
        if (!\Input::hasFile('fileName')) {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['applyAssAnswer'], $this->panelInit->language['assNoFilesUploaded']);
        } elseif (\Input::hasFile('fileName')) {
            $fileInstance = \Input::file('fileName');

            if (!$this->panelInit->validate_upload($fileInstance)) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['applyAssAnswer'], "Sorry, This File Type Is Not Permitted For Security Reasons ");
            }

            $newFileName = "assignments_" . uniqid() . "." . $fileInstance->getClientOriginalExtension();
            $fileInstance->move('uploads/assignmentsAnswers/', $newFileName);

            $assignmentsAnswers->fileName = $newFileName;
            $assignmentsAnswers->save();
        }
        $assignmentsAnswers->save();

        return $this->panelInit->apiOutput(true, $this->panelInit->language['applyAssAnswer'], $this->panelInit->language['assUploadedSucc']);
    }

    function listAssignmentAnswers($id) {

        if (!$this->panelInit->can("Assignments.viewAnswers")) {
            exit;
        }

        $assignmentsAnswers = \DB::table('assignments_answers')
                ->leftJoin('users', 'users.id', '=', 'assignments_answers.userId')
                ->leftJoin('classes', 'classes.id', '=', 'users.studentClass')
                ->select('assignments_answers.id as id',
                        'assignments_answers.userId as userId',
                        'assignments_answers.userNotes as userNotes',
                        'assignments_answers.userTime as userTime',
                        'assignments_answers.fileName as AssignFile',
                        'users.fullName as fullName',
                        'classes.className as className')
                ->where('assignmentId', $id)
                ->get();

        foreach ($assignmentsAnswers as $key => $assignment) {
            $assignmentsAnswers[$key]->userTime = $this->panelInit->unix_to_date($assignmentsAnswers[$key]->userTime, $this->panelInit->settingsArray['dateformat'] . " hr:mn a");
        }


        return $assignmentsAnswers;
    }

    public function downloadAssignmentAnswer($id) {

        if (!$this->panelInit->can("Assignments.viewAnswers")) {
            exit;
        }

        $toReturn = \assignments_answers::where('id', $id)->first();
        $user = \User::where('id', $toReturn->userId)->first();
        if (file_exists('uploads/assignmentsAnswers/' . $toReturn->fileName)) {
            $fileName = preg_replace('/[^a-zA-Z0-9-_\.]/', '-', $user->fullName) . "." . pathinfo($toReturn->fileName, PATHINFO_EXTENSION);
            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=" . $fileName);
            echo file_get_contents('uploads/assignmentsAnswers/' . $toReturn->fileName);
        } else {
            echo "<br/><br/><br/><br/><br/><center>File not exist, Please contact site administrator to reupload it again.</center>";
        }
        exit;
    }

    public function quizzes($id) {

        if (!$this->panelInit->can(array("onlineExams.list", "onlineExams.addExam", "onlineExams.editExam", "onlineExams.delExam", "onlineExams.takeExam", "onlineExams.showMarks", "onlineExams.QuestionsArch"))) {
            exit;
        }

        $toReturn = array();

        $classesArray = array();
        $toReturn['classes'] = \classes::where('classSubjects', 'like', '%"' . $id . '"%')->where('classAcademicYear', $this->panelInit->selectAcYear)->get();

        foreach ($toReturn['classes'] as $class) {
            $classesArray[$class['id']] = array("classTitle" => $class['className'], "subjects" => json_decode($class['classSubjects']));
        }

        if ($this->data['users']->role == "teacher") {
            $subjects = \subject::where('teacherId', 'LIKE', '%"' . $this->data['users']->id . '"%')->get()->toArray();
        } else {
            $subjects = \subject::get()->toArray();
        }
        $subjectArray = array();
        foreach ($subjects as $subject) {
            $subjectArray[$subject['id']] = $subject['subjectTitle'];
        }

        $toReturn['onlineExams'] = array();
        $onlineExams = new \online_exams();
        $onlineExams = $onlineExams->where('examSubject', $id);
        if ($this->data['users']->role == "teacher") {
            $onlineExams = $onlineExams->where('examTeacher', $this->data['users']->id);
        }

        if ($this->data['users']->role == "student") {
            $onlineExams = $onlineExams->where('examClass', 'LIKE', '%"' . $this->data['users']->studentClass . '"%');
            if ($this->panelInit->settingsArray['enableSections'] == true) {
                $onlineExams = $onlineExams->where('sectionId', 'LIKE', '%"' . $this->data['users']->studentSection . '"%');
            }
            $onlineExams = $onlineExams->where('examDate', '<=', time())->where('ExamEndDate', '>=', time());
        }

        $onlineExams = $onlineExams->where('exAcYear', $this->panelInit->selectAcYear);
        $onlineExams = $onlineExams->get();
        foreach ($onlineExams as $key => $onlineExam) {
            $classId = json_decode($onlineExam->examClass);
            if ($this->data['users']->role == "student" AND ! in_array($this->data['users']->studentClass, $classId)) {
                continue;
            }
            $toReturn['onlineExams'][$key]['id'] = $onlineExam->id;
            $toReturn['onlineExams'][$key]['examTitle'] = $onlineExam->examTitle;
            $toReturn['onlineExams'][$key]['examDescription'] = $onlineExam->examDescription;
            if (isset($subjectArray[$onlineExam->examSubject])) {
                $toReturn['onlineExams'][$key]['examSubject'] = $subjectArray[$onlineExam->examSubject];
            }
            $toReturn['onlineExams'][$key]['ExamEndDate'] = $onlineExam->ExamEndDate;
            $toReturn['onlineExams'][$key]['ExamShowGrade'] = $onlineExam->ExamShowGrade;
            $toReturn['onlineExams'][$key]['examDate'] = $this->panelInit->unix_to_date($onlineExam->examDate);
            $toReturn['onlineExams'][$key]['ExamEndDate'] = $this->panelInit->unix_to_date($onlineExam->ExamEndDate);
            $toReturn['onlineExams'][$key]['classes'] = "";

            foreach ($classId as $value) {
                if (isset($classesArray[$value])) {
                    $toReturn['onlineExams'][$key]['classes'] .= $classesArray[$value]['classTitle'] . ", ";
                }
            }
        }
        $toReturn['subject_list'] = array();
        $subject_list = \subject::select('id', 'subjectTitle')->get();
        foreach ($subject_list as $key => $value) {
            $toReturn['subject_list'][$value->id] = $value->subjectTitle;
        }

        return $toReturn;
    }

    public function deleteQuiz($id) {

        if (!$this->panelInit->can("onlineExams.delExam")) {
            exit;
        }

        if ($postDelete = \online_exams::where('id', $id)->first()) {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true, $this->panelInit->language['delExam'], $this->panelInit->language['exDeleted']);
        } else {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['delExam'], $this->panelInit->language['exNotExist']);
        }
    }

    public function createQuiz() {

        if (!$this->panelInit->can("onlineExams.addExam")) {
            exit;
        }

        $examQuestionH = array();
        if (\Input::has('examQuestion')) {
            $examQuestion = \Input::get('examQuestion');
            foreach ($examQuestion as $key => $value) {
                $examQuestionH[] = $value['id'];
            }
            $examQuestionH = array_unique($examQuestionH);
        }

        $onlineExams = new \online_exams();
        $onlineExams->examTitle = \Input::get('examTitle');
        $onlineExams->examDescription = \Input::get('examDescription');
        $onlineExams->examClass = json_encode(\Input::get('class_id'));
        if (\Input::has('sectionId')) {
            $onlineExams->sectionId = json_encode(\Input::get('sectionId'));
        }
        $onlineExams->examTeacher = $this->data['users']->id;
        $onlineExams->examSubject = \Input::get('examSubject');
        $onlineExams->examDate = $this->panelInit->date_to_unix(\Input::get('examDate'));
        $onlineExams->exAcYear = $this->panelInit->selectAcYear;
        $onlineExams->ExamEndDate = $this->panelInit->date_to_unix(\Input::get('ExamEndDate'));
        if (\Input::has('ExamShowGrade')) {
            $onlineExams->ExamShowGrade = \Input::get('ExamShowGrade');
        }
        if (\Input::has('random_questions')) {
            $onlineExams->random_questions = \Input::get('random_questions');
        }
        $onlineExams->examTimeMinutes = \Input::get('examTimeMinutes');
        $onlineExams->examDegreeSuccess = \Input::get('examDegreeSuccess');
        $onlineExams->examQuestion = json_encode($examQuestionH);
        $onlineExams->save();

        $onlineExams->examDate = \Input::get('examDate');
        $onlineExams->ExamEndDate = \Input::get('ExamEndDate');

        //Send Push Notifications
        $tokens_list = array();
        $user_list = \User::where('role', 'student')->whereIn('studentClass', \Input::get('class_id'));
        if (\Input::has('sectionId')) {
            $user_list = $user_list->whereIn('studentSection', \Input::get('sectionId'));
        }
        $user_list = $user_list->select('firebase_token')->get();

        foreach ($user_list as $value) {
            if ($value['firebase_token'] != "") {
                $tokens_list[] = $value['firebase_token'];
            }
        }

        if (count($tokens_list) > 0) {
            $this->panelInit->send_push_notification($tokens_list, \Input::get('examTitle'), $this->panelInit->language['newOnlineExamAdded'], "online_exams", $onlineExams->id);
        }

        return $this->panelInit->apiOutput(true, $this->panelInit->language['addExam'], $this->panelInit->language['examCreated'], $onlineExams->toArray());
    }

    function fetchQuiz($id) {

        if (!$this->panelInit->can("onlineExams.editExam")) {
            exit;
        }

        $istook = \online_exams_grades::where('examId', $id)->where('studentId', $this->data['users']->id)->count();

        $onlineExams = \online_exams::where('id', $id)->first()->toArray();
        $onlineExams['examClass'] = json_decode($onlineExams['examClass']);
        $onlineExams['sectionId'] = json_decode($onlineExams['sectionId']);


        $examQuestionArray = json_decode($onlineExams['examQuestion'], true);
        $examQuestionArrayDb = \online_exams_questions::whereIn('id', $examQuestionArray)->get();

        $examQuestion = array();
        foreach ($examQuestionArrayDb as $value) {
            $examQuestion[] = array("id" => $value->id, "question_text" => strip_tags(htmlspecialchars_decode($value->question_text)), "question_type" => $value->question_type);
        }
        $onlineExams['examQuestion'] = $examQuestion;

        if (time() > $onlineExams['ExamEndDate'] || time() < $onlineExams['examDate']) {
            $onlineExams['finished'] = true;
        }
        if ($istook > 0) {
            $onlineExams['taken'] = true;
        }
        $onlineExams['examDate'] = $this->panelInit->unix_to_date($onlineExams['examDate']);
        $onlineExams['ExamEndDate'] = $this->panelInit->unix_to_date($onlineExams['ExamEndDate']);

        $DashboardController = new DashboardController();
        $onlineExams['subject'] = $DashboardController->subjectList($onlineExams['examClass']);
        $onlineExams['sections'] = $DashboardController->sectionsList($onlineExams['examClass']);
        return $onlineExams;
    }

    function quizMarks($id) {

        if (!$this->panelInit->can("onlineExams.showMarks")) {
            exit;
        }

        $return = array();

        $exam = \online_exams::where('id', $id)->first();
        $return['examDegreeSuccess'] = $exam->examDegreeSuccess;

        $return['grade'] = \DB::table('online_exams_grades')
                ->where('examId', $id)
                ->leftJoin('users', 'users.id', '=', 'online_exams_grades.studentId')
                ->select('online_exams_grades.id as id',
                        'online_exams_grades.examGrade as examGrade',
                        'online_exams_grades.examDate as examDate',
                        'online_exams_grades.examQuestionsAnswers as examQuestionsAnswers',
                        'users.fullName as fullName',
                        'users.id as studentId')
                ->get();

        foreach ($return['grade'] as $key => $value) {
            $return['grade'][$key]->examQuestionsAnswers = json_decode($return['grade'][$key]->examQuestionsAnswers, true);
            $return['grade'][$key]->examDate = $this->panelInit->unix_to_date($return['grade'][$key]->examDate);
        }

        return json_encode($return);
    }

    function editQuiz($id) {

        if (!$this->panelInit->can("onlineExams.editExam")) {
            exit;
        }

        $examQuestionH = array();
        if (\Input::has('examQuestion')) {
            $examQuestion = \Input::get('examQuestion');
            foreach ($examQuestion as $key => $value) {
                $examQuestionH[] = $value['id'];
            }
            $examQuestionH = array_unique($examQuestionH);
        }
        $onlineExams = \online_exams::find($id);
        $onlineExams->examTitle = \Input::get('examTitle');
        $onlineExams->examDescription = \Input::get('examDescription');
        $onlineExams->examClass = json_encode(\Input::get('class_id'));
        if (\Input::has('sectionId')) {
            $onlineExams->sectionId = json_encode(\Input::get('sectionId'));
        }
        $onlineExams->examTeacher = $this->data['users']->id;
        $onlineExams->examSubject = \Input::get('examSubject');
        $onlineExams->examDate = $this->panelInit->date_to_unix(\Input::get('examDate'));
        $onlineExams->ExamEndDate = $this->panelInit->date_to_unix(\Input::get('ExamEndDate'));
        if (\Input::has('ExamShowGrade')) {
            $onlineExams->ExamShowGrade = \Input::get('ExamShowGrade');
        }
        if (\Input::has('random_questions')) {
            $onlineExams->random_questions = \Input::get('random_questions');
        }
        $onlineExams->examTimeMinutes = \Input::get('examTimeMinutes');
        $onlineExams->examDegreeSuccess = \Input::get('examDegreeSuccess');
        $onlineExams->examQuestion = json_encode($examQuestionH);
        $onlineExams->save();

        $onlineExams->examDate = \Input::get('examDate');
        $onlineExams->ExamEndDate = \Input::get('ExamEndDate');

        return $this->panelInit->apiOutput(true, $this->panelInit->language['editExam'], $this->panelInit->language['examModified'], $onlineExams->toArray());
    }

    function takeQuiz($id) {

        if (!$this->panelInit->can("onlineExams.takeExam")) {
            exit;
        }

        $istook = \online_exams_grades::where('examId', $id)->where('studentId', $this->data['users']->id);
        $istookFinish = $istook->first();
        $istook = $istook->count();

        if ($istook > 0 AND $istookFinish['examQuestionsAnswers'] != null) {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['takeExam'], $this->panelInit->language['exAlreadyTook']);
        }

        if ($istook == 0) {
            $onlineExamsGrades = new \online_exams_grades();
            $onlineExamsGrades->examId = $id;
            $onlineExamsGrades->studentId = $this->data['users']->id;
            $onlineExamsGrades->examDate = time();
            $onlineExamsGrades->save();
        }

        $onlineExams = \online_exams::where('id', $id)->first()->toArray();

        if ($onlineExams['examTimeMinutes'] != 0 AND $istook > 0) {
            if ((time() - $istookFinish['examDate']) > $onlineExams['examTimeMinutes'] * 60) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['takeExam'], $this->panelInit->language['examTimedOut']);
            }
        }

        if ($onlineExams['ExamEndDate'] < time()) {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['takeExam'], $this->panelInit->language['examTimedOut']);
        }

        $onlineExams['examClass'] = json_decode($onlineExams['examClass']);
        $onlineExams['examQuestion'] = json_decode($onlineExams['examQuestion'], true);

        $examQuestions = \online_exams_questions::whereIn('id', $onlineExams['examQuestion'])->select('id', 'question_text', 'question_type', 'question_answers', 'question_mark', 'question_image');
        if ($onlineExams['random_questions'] == "1") {
            $examQuestions = $examQuestions->orderByRaw("RAND()");
        }
        $examQuestions = $examQuestions->get()->toArray();
        foreach ($examQuestions as $key => $value) {
            $examQuestions[$key]['question_answers'] = json_decode($examQuestions[$key]['question_answers'], true);
            if (isset($examQuestions[$key]['question_answers']['tans'])) {
                unset($examQuestions[$key]['question_answers']['tans']);
            }
        }
        $onlineExams['examQuestions'] = $examQuestions;

        if (time() > $onlineExams['ExamEndDate'] || time() < $onlineExams['examDate']) {
            $onlineExams['finished'] = true;
        }

        if ($onlineExams['examTimeMinutes'] == 0) {
            $onlineExams['timeLeft'] = 0;
        } else {
            if ($istook == 0) {
                $onlineExams['timeLeft'] = $onlineExams['examTimeMinutes'] * 60;
            }
            if ($istook > 0) {
                $onlineExams['timeLeft'] = $onlineExams['examTimeMinutes'] * 60 - (time() - $istookFinish['examDate']);
            }
        }

        $onlineExams['examDate'] = $this->panelInit->unix_to_date($onlineExams['examDate']);
        $onlineExams['ExamEndDate'] = $this->panelInit->unix_to_date($onlineExams['ExamEndDate']);

        return $onlineExams;
    }

    function tookQuiz($id) {

        if (!$this->panelInit->can("onlineExams.takeExam")) {
            exit;
        }

        $istook = \online_exams_grades::where('examId', $id)->where('studentId', $this->data['users']->id);
        if ($istook->count() == 0) {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['takeExam'], "An error occured");
        }
        $istook = $istook->first();

        if ($istook['examQuestionsAnswers'] != null) {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['takeExam'], $this->panelInit->language['exAlreadyTook']);
        }

        $onlineExams = \online_exams::where('id', $id)->first()->toArray();

        if ($onlineExams['examTimeMinutes'] != 0) {
            if ((time() - $istook['examDate'] - 30) > $onlineExams['examTimeMinutes'] * 60) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['takeExam'], $this->panelInit->language['examTimedOut']);
            }
        }

        $onlineExams['examQuestion'] = json_decode($onlineExams['examQuestion'], true);

        $score = 0;
        $answers = \Input::get('answers');
        $examQuestions = \online_exams_questions::whereIn('id', $onlineExams['examQuestion'])->select('id', 'question_text', 'question_type', 'question_answers', 'question_mark', 'question_image')->get()->toArray();

        $examQuestionsAnswers = array();
        foreach ($examQuestions as $key => $value) {
            $value['question_answers'] = json_decode($value['question_answers'], true);
            reset($answers);
            foreach ($answers as $keyAnswer => $valueAnswer) {

                if (!isset($examQuestionsAnswers[$value['id']])) {
                    $examQuestionsAnswers[$value['id']] = array();
                }
                $examQuestionsAnswers[$value['id']]['question_text'] = strip_tags(htmlspecialchars_decode($value['question_text'], ENT_QUOTES));
                $examQuestionsAnswers[$value['id']]['question_type'] = $value['question_type'];

                if ($value['id'] == $valueAnswer['id'] && isset($valueAnswer['question_answers']['answer'])) {
                    if (isset($value['question_type']) AND $value['question_type'] == "radio") {
                        $examQuestionsAnswers[$value['id']]['userAnswer'] = $valueAnswer['question_answers']['answer'];
                        $answers[$keyAnswer]['state'] = 0;
                        if ($valueAnswer['question_answers']['answer'] == $value['question_answers']['tans']) {
                            $answers[$keyAnswer]['state'] = 1;
                            if (isset($value['question_mark'])) {
                                $score += $value['question_mark'];
                            } else {
                                $score++;
                            }
                        }
                        $examQuestionsAnswers[$value['id']]['state'] = $answers[$keyAnswer]['state'];
                        continue;
                    }
                    if (isset($value['question_type']) AND $value['question_type'] == "check") {
                        $examQuestionsAnswers[$value['id']]['userAnswer'] = implode(",", $valueAnswer['question_answers']['answer']);
                        $answers[$keyAnswer]['state'] = 0;
                        $pass = true;
                        if (count($valueAnswer['question_answers']['answer']) != count($value['question_answers']['tans'])) {
                            $pass = false;
                        }
                        foreach ($value['question_answers']['tans'] as $keyCheck => $valueCheck) {
                            if (!in_array($valueCheck, $valueAnswer['question_answers']['answer'])) {
                                $pass = false;
                            }
                        }
                        if ($pass == true) {
                            $answers[$keyAnswer]['state'] = 1;
                            if (isset($value['question_mark'])) {
                                $score += $value['question_mark'];
                            } else {
                                $score++;
                            }
                        }
                        $examQuestionsAnswers[$value['id']]['state'] = $answers[$keyAnswer]['state'];
                        unset($pass);
                        continue;
                    }
                    if (isset($value['question_type']) AND $value['question_type'] == "text") {
                        $examQuestionsAnswers[$value['id']]['userAnswer'] = $valueAnswer['question_answers']['answer'];
                        $answers[$keyAnswer]['state'] = 0;
                        if (in_array($valueAnswer['question_answers']['answer'], $value['question_answers']['answers'])) {
                            $answers[$keyAnswer]['state'] = 1;
                            if (isset($value['question_mark'])) {
                                $score += $value['question_mark'];
                            } else {
                                $score++;
                            }
                        }
                        $examQuestionsAnswers[$value['id']]['state'] = $answers[$keyAnswer]['state'];
                        continue;
                    }
                }
            }
        }

        $istook->examQuestionsAnswers = json_encode($examQuestionsAnswers);
        $istook->examGrade = $score;
        $istook->save();

        if ($onlineExams['ExamShowGrade'] == 1) {
            if ($onlineExams['examDegreeSuccess'] != "0") {
                if (intval($onlineExams['examDegreeSuccess']) <= $score) {
                    $score .= " - Succeeded";
                } else {
                    $score .= " - Failed";
                }
            }
            $toReturn['grade'] = $score;
        }
        $toReturn['finish'] = true;
        return json_encode($toReturn);
    }

    function exportQuiz($id, $type) {

        if (!$this->panelInit->can("onlineExams.showMarks")) {
            exit;
        }

        if ($type == "excel") {
            $classArray = array();
            $classes = \classes::get();
            foreach ($classes as $class) {
                $classArray[$class->id] = $class->className;
            }

            $data = array(1 => array('Student Roll', 'Full Name', 'Date took', 'Exam Grade'));
            $grades = \DB::table('online_exams_grades')
                    ->where('examId', $id)
                    ->leftJoin('users', 'users.id', '=', 'online_exams_grades.studentId')
                    ->select('online_exams_grades.id as id',
                            'online_exams_grades.examGrade as examGrade',
                            'online_exams_grades.examDate as examDate',
                            'users.fullName as fullName',
                            'users.id as studentId',
                            'users.studentRollId as studentRollId')
                    ->get();
            foreach ($grades as $value) {
                $data[] = array($value->studentRollId, $value->fullName, $this->panelInit->unix_to_date($value->examDate), $value->examGrade);
            }

            \Excel::create('Exam-Grade-Sheet', function($excel) use($data) {

                // Set the title
                $excel->setTitle('Exam grades Sheet');

                // Chain the setters
                $excel->setCreator('OraSchool')->setCompany('SolutionsBricks');

                $excel->sheet('Exam-Grade', function($sheet) use($data) {
                    $sheet->freezeFirstRow();
                    $sheet->fromArray($data, null, 'A1', true, false);
                });
            })->download('xls');
        } elseif ($type == "pdf") {
            $classArray = array();
            $classes = \classes::get();
            foreach ($classes as $class) {
                $classArray[$class->id] = $class->className;
            }

            $header = array($this->panelInit->language['rollid'], $this->panelInit->language['FullName'], $this->panelInit->language['Date'], $this->panelInit->language['Grade']);
            $data = array();
            $grades = \DB::table('online_exams_grades')
                    ->where('examId', $id)
                    ->leftJoin('users', 'users.id', '=', 'online_exams_grades.studentId')
                    ->select('online_exams_grades.id as id',
                            'online_exams_grades.examGrade as examGrade',
                            'online_exams_grades.examDate as examDate',
                            'users.fullName as fullName',
                            'users.id as studentId',
                            'users.studentRollId as studentRollId')
                    ->get();

            foreach ($grades as $value) {
                $data[] = array($value->studentRollId, $value->fullName, $this->panelInit->unix_to_date($value->examDate), $value->examGrade);
            }

            $doc_details = array(
                "title" => "OnlineExam ",
                "author" => $this->data['panelInit']->settingsArray['siteTitle'],
                "topMarginValue" => 10
            );

            if ($this->panelInit->isRTL == "1") {
                $doc_details['is_rtl'] = true;
            }

            $pdfbuilder = new \PdfBuilder($doc_details);

            $content = "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">
		        <thead><tr>";
            foreach ($header as $value) {
                $content .= "<th style='width:15%;border: solid 1px #000000; padding:2px;'>" . $value . "</th>";
            }
            $content .= "</tr></thead><tbody>";

            foreach ($data as $row) {
                $content .= "<tr>";
                foreach ($row as $col) {
                    $content .= "<td>" . $col . "</td>";
                }
                $content .= "</tr>";
            }

            $content .= "</tbody></table>";

            $pdfbuilder->table($content, array('border' => '0', 'align' => ''));
            $pdfbuilder->output('OnlineExam.pdf');
        }
        exit;
    }

    function quizQuestions() {

        if (!$this->panelInit->can("onlineExams.QuestionsArch")) {
            exit;
        }

        $userId = $this->data['users']->id;

        $online_exams_questions = new \online_exams_questions();
        if ($this->data['users']->role != "admin") {
            $online_exams_questions = $online_exams_questions->where(function($query) use ($userId) {
                $query->where('employee_id', $userId)->orWhere('is_shared', '1');
            });
        }
        $online_exams_questions = $online_exams_questions->get();

        foreach ($online_exams_questions as $key => $value) {
            $online_exams_questions[$key]['question_text'] = strip_tags(htmlspecialchars_decode($online_exams_questions[$key]['question_text'], ENT_QUOTES));
        }

        return $online_exams_questions;
    }

    function createQuizQuestions() {

        if (!$this->panelInit->can("onlineExams.QuestionsArch")) {
            exit;
        }

        $answersList = array();
        if (\Input::get('question_type') == "radio") {
            $answersList['answers'] = \Input::get('radioAnswers');
            $answersList['tans'] = \Input::get('radioTrueAnswer');
        } elseif (\Input::get('question_type') == "check") {
            $answersList['answers'] = \Input::get('checkAnswers');
            $answersList['tans'] = \Input::get('checkTrueAnswer');
        } elseif (\Input::get('question_type') == "text") {
            $answersList['answers'] = \Input::get('textAnswers');
        }

        $online_exams_questions = new \online_exams_questions();
        $online_exams_questions->question_text = \Input::get('question_text');
        $online_exams_questions->question_type = \Input::get('question_type');
        $online_exams_questions->question_answers = json_encode($answersList);
        $online_exams_questions->question_mark = \Input::get('question_mark');

        if (\Input::hasFile('question_image')) {
            $fileInstance = \Input::file('question_image');

            if (!$this->panelInit->validate_upload($fileInstance)) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['addQuestion'], "Sorry, This File Type Is Not Permitted For Security Reasons ");
            }

            $newFileName = uniqid() . "." . $fileInstance->getClientOriginalExtension();
            $fileInstance->move('uploads/onlineExams/', $newFileName);

            $online_exams_questions->question_image = $newFileName;
        }

        $online_exams_questions->question_subject = \Input::get('question_subject');
        $online_exams_questions->employee_id = $this->data['users']->id;
        $online_exams_questions->is_shared = \Input::get('is_shared');
        $online_exams_questions->save();

        return $this->panelInit->apiOutput(true, $this->panelInit->language['addQuestion'], $this->panelInit->language['quesAdded'], $online_exams_questions->toArray());
    }

    function fetchQuizQuestions($id) {

        if (!$this->panelInit->can("onlineExams.QuestionsArch")) {
            exit;
        }

        $online_exams_questions = \online_exams_questions::where('id', $id)->first()->toArray();
        $online_exams_questions['question_answers'] = json_decode($online_exams_questions['question_answers'], true);

        if ($online_exams_questions['question_type'] == "radio") {
            $online_exams_questions['answersList'] = $online_exams_questions['question_answers']['answers'];
            $online_exams_questions['radioTrueAnswer'] = $online_exams_questions['question_answers']['tans'];
        } elseif ($online_exams_questions['question_type'] == "check") {
            $online_exams_questions['answersList'] = $online_exams_questions['question_answers']['answers'];
            $online_exams_questions['checkTrueAnswer'] = $online_exams_questions['question_answers']['tans'];
        } elseif ($online_exams_questions['question_type'] == "text") {
            $online_exams_questions['answersList'] = $online_exams_questions['question_answers']['answers'];
        }

        return $online_exams_questions;
    }

    function editQuizQuestions($id) {

        if (!$this->panelInit->can("onlineExams.QuestionsArch")) {
            exit;
        }

        $answersList = array();
        if (\Input::get('question_type') == "radio") {
            $answersList['answers'] = \Input::get('radioAnswers');
            $answersList['tans'] = \Input::get('radioTrueAnswer');
        } elseif (\Input::get('question_type') == "check") {
            $answersList['answers'] = \Input::get('checkAnswers');
            $answersList['tans'] = \Input::get('checkTrueAnswer');
        } elseif (\Input::get('question_type') == "text") {
            $answersList['answers'] = \Input::get('textAnswers');
        }

        $online_exams_questions = \online_exams_questions::find($id);
        $online_exams_questions->question_text = \Input::get('question_text');
        $online_exams_questions->question_type = \Input::get('question_type');
        $online_exams_questions->question_answers = json_encode($answersList);
        $online_exams_questions->question_mark = \Input::get('question_mark');

        if (\Input::hasFile('question_image')) {
            $fileInstance = \Input::file('question_image');

            if (!$this->panelInit->validate_upload($fileInstance)) {
                return $this->panelInit->apiOutput(false, $this->panelInit->language['editQuestion'], "Sorry, This File Type Is Not Permitted For Security Reasons ");
            }

            $newFileName = uniqid() . "." . $fileInstance->getClientOriginalExtension();
            $fileInstance->move('uploads/onlineExams/', $newFileName);

            $online_exams_questions->question_image = $newFileName;
        }

        $online_exams_questions->question_subject = \Input::get('question_subject');
        $online_exams_questions->employee_id = $this->data['users']->id;
        $online_exams_questions->is_shared = \Input::get('is_shared');
        $online_exams_questions->save();

        return $this->panelInit->apiOutput(true, $this->panelInit->language['editQuestion'], $this->panelInit->language['quesModif'], $online_exams_questions->toArray());
    }

    function deleteQuizQuestions($id) {

        if (!$this->panelInit->can("onlineExams.QuestionsArch")) {
            exit;
        }

        if ($postDelete = \online_exams_questions::where('id', $id)->where('employee_id', $this->data['users']->id)->first()) {
            if ($postDelete->question_image != "") {
                @unlink('uploads/onlineExams/' . $postDelete->question_image);
            }
            $postDelete->delete();
            return $this->panelInit->apiOutput(true, $this->panelInit->language['delQues'], $this->panelInit->language['quesDeleted']);
        } else {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['delQues'], $this->panelInit->language['quesNotExist']);
        }
    }

    function searchQuizQuestion($keyword) {

        if (!$this->panelInit->can(array("onlineExams.addExam", "onlineExams.editExam"))) {
            exit;
        }

        $userId = $this->data['users']->id;
        $questions = \online_exams_questions::where(function($query) use ($userId) {
                    $query->where('employee_id', $userId)->orWhere('is_shared', '1');
                })->where(function($query2) use ($keyword) {
                    $query2->where('question_text', 'LIKE', '%' . $keyword . '%')->orWhere('question_answers', 'LIKE', '%' . $keyword . '%');
                })->select('id', 'question_text', 'question_type')->get()->toArray();

        foreach ($questions as $key => $value) {
            $questions[$key]['question_text'] = strip_tags(htmlspecialchars_decode($questions[$key]['question_text']));
        }

        return $questions;
    }

    public function post($id) {
        $subject = \subject::where('id', $id)->get()->first();
        $classes = \classes::where('classSubjects', 'like', '%"' . $id . '"%')->where('classAcademicYear', $this->panelInit->selectAcYear)->get();
        $teachers = \User::where('role', 'teacher')->whereIn('id', json_decode($subject['teacherId']))->get();
        return [
            'subject' => $subject,
            'classes' => $classes,
            'teachers' => $teachers
        ];
    }

    public function listAll($id) {

        if (!$this->panelInit->can(array("Subjects.list", "Subjects.addSubject", "Subjects.editSubject", "Subjects.delSubject"))) {
            exit;
        }
        return ['true'];

        $toReturn = array();
        $toReturn['subjects'] = \DB::table('subject')
                ->leftJoin('users', 'users.id', '=', 'subject.teacherId')
                ->select('subject.id as id',
                'subject.subjectTitle as subjectTitle',
                'subject.passGrade as passGrade',
                'subject.finalGrade as finalGrade',
                'subject.teacherId as teacherId',
                'users.fullName as teacherName');

        if ($this->data['users']->role == "student") {

            if ($this->data['users']->studentClass == "") {
                return array();
            }

            $class = \classes::where('id', $this->data['users']->studentClass)->select('classSubjects')->first()->toArray();
            $class['classSubjects'] = json_decode($class['classSubjects'], true);

            if (count($class['classSubjects']) == 0) {
                return array();
            } else {
                $toReturn['subjects'] = $toReturn['subjects']->whereIn('subject.id', $class['classSubjects']);
            }
        } elseif ($this->data['users']->role == "parent") {

            if (!is_array($this->data['users']->parentOf)) {
                $parentOf = json_decode($this->data['users']->parentOf, true);
            } else {
                $parentOf = $this->data['users']->parentOf;
            }

            if (!is_array($parentOf) || count($parentOf) == 0) {
                return array();
            }

            $std_id = array();
            foreach ($parentOf as $key => $value) {
                $std_id[] = $value['id'];
            }

            $students = \User::whereIn('id', $std_id)->select('studentClass');

            if ($students->count() > 0) {

                $classes = array();
                $students = $students->get();
                foreach ($students as $key => $value) {
                    $classes[] = $value->studentClass;
                }

                $class = \classes::whereIn('id', $classes)->select('classSubjects')->first()->toArray();
                $class['classSubjects'] = json_decode($class['classSubjects'], true);

                if (count($class['classSubjects']) == 0) {
                    return array();
                } else {
                    $toReturn['subjects'] = $toReturn['subjects']->whereIn('subject.id', $class['classSubjects']);
                }
            } else {
                return array();
            }
        } elseif ($this->data['users']->role == "teacher" || $this->data['users']->role == "employee") {
            $toReturn['subjects'] = $toReturn['subjects']->where('subject.teacherId', 'like', '%"' . $this->data['users']->id . '"%');
        }

        if (\Input::has('searchInput')) {
            $keyword = \Input::get('searchInput');
            $toReturn['subjects'] = $toReturn['subjects']->where(function($query) use ($keyword) {
                $query->where('subjectTitle', 'like', '%' . $keyword . '%');
            });
        }

        $toReturn['totalItems'] = $toReturn['subjects']->count();
        $toReturn['subjects'] = $toReturn['subjects']->orderby('id', 'desc')->take('20')->skip(20 * ($page - 1))->get();

        $teachers = \User::where('role', 'teacher')->select('id', 'fullName')->get()->toArray();
        foreach ($teachers as $value) {
            $toReturn['teachers'][$value['id']] = $value;
        }
        return $toReturn;
    }

    public function delete($id) {

        if (!$this->panelInit->can("Subjects.delSubject")) {
            exit;
        }

        if ($postDelete = \subject::where('id', $id)->first()) {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true, $this->panelInit->language['delSubject'], $this->panelInit->language['subjectDel']);
        } else {
            return $this->panelInit->apiOutput(false, $this->panelInit->language['delSubject'], $this->panelInit->language['subjectNotExist']);
        }
    }

    public function create() {

        if (!$this->panelInit->can("Subjects.addSubject")) {
            exit;
        }

        $subject = new \subject();
        $subject->subjectTitle = \Input::get('subjectTitle');
        $subject->teacherId = json_encode(\Input::get('teacherId'));
        $subject->passGrade = \Input::get('passGrade');
        $subject->finalGrade = \Input::get('finalGrade');
        $subject->save();

        return $this->panelInit->apiOutput(true, $this->panelInit->language['addSubject'], $this->panelInit->language['subjectCreated'], $subject->toArray());
    }

    function fetch($id) {

        if (!$this->panelInit->can("Subjects.editSubject")) {
            exit;
        }

        $subject = \subject::where('id', $id)->first()->toArray();
        $subject['teacherId'] = json_decode($subject['teacherId'], true);
        return $subject;
    }

    function edit($id) {

        if (!$this->panelInit->can("Subjects.editSubject")) {
            exit;
        }

        $subject = \subject::find($id);
        $subject->subjectTitle = \Input::get('subjectTitle');
        $subject->teacherId = json_encode(\Input::get('teacherId'));
        $subject->passGrade = \Input::get('passGrade');
        $subject->finalGrade = \Input::get('finalGrade');
        $subject->save();

        return $this->panelInit->apiOutput(true, $this->panelInit->language['editSubject'], $this->panelInit->language['subjectEdited'], $subject->toArray());
    }

    function preview($id) {

        if (!$this->panelInit->can("Subjects.editSubject")) {
            exit;
        }
        $subject = \subject::find($id);
        return $this->panelInit->apiOutput(true, $this->panelInit->language['previewSubject'], $this->panelInit->language['subjectEdited'], $subject->toArray());
    }

    function fetchMaterial($id) {

        if (!$this->panelInit->can("studyMaterial.editMaterial")) {
            exit;
        }

        $studyMaterial = \study_material::where('id', $id)->first()->toArray();
        $DashboardController = new DashboardController();
        $studyMaterial['sections'] = $DashboardController->sectionsList(json_decode($studyMaterial['class_id'], true));
        $studyMaterial['subject'] = $DashboardController->subjectList(json_decode($studyMaterial['class_id'], true));
        $studyMaterial['class_id'] = json_decode($studyMaterial['class_id'], true);
        return $studyMaterial;
    }

    public function gradeItems2($id) {
        $teachers = \User::where('role', 'teacher')->whereIn('id', json_decode($subject['teacherId']))->get();

        foreach ($teachers as $teacher) {
            $teacher->photo = URL::asset('/dashboard/profileImage/' . $teacher->id);
        }
        return [
            'subject' => $subject,
            'classes' => $classes,
            'teachers' => $teachers,
        ];
    }

    public function gradeItems($id) {
        $subject = \subject::where('id', $id)->get()->first();
        $classes = \classes::where('classSubjects', 'like', '%"' . $id . '"%')->where('classAcademicYear', $this->panelInit->selectAcYear)->get();

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
                                foreach ($classes as $class) {
                                    $query->orWhere('itemClasses', 'LIKE', '%"' . $class['id'] . '"%');
                                }
                            })->get()->toArray();
                }
            }
        } else {
            $toReturn['items'] = grade_items::where('itemAcYear', $this->panelInit->selectAcYear)->get()->toArray();
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

    public function deleteGradeItem($id) {

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

    public function createGradeItem() {

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
//        $tokens_list = array();
//        $user_list = \User::where('role', 'student')->whereIn('studentClass', \Input::get('itemClasses'))->select('firebase_token')->get();
//
//        foreach ($user_list as $value) {
//            if ($value['firebase_token'] != "") {
//                $tokens_list[] = $value['firebase_token'];
//            }
//        }
//
//        if (count($tokens_list) > 0) {
//            $this->panelInit->send_push_notification($tokens_list, $this->panelInit->language['newItemNotif'] . " : " . \Input::get('itemTitle'), $this->panelInit->language['gradeItem'], "items", $gradeItem->id);
//        }

        $gradeItem->itemDate = \Input::get('itemDate');

        return $this->panelInit->apiOutput(true, $this->panelInit->language['addItem'], $this->panelInit->language['itemCreated'], $gradeItem->toArray());
    }

    function fetchGradeItem($id) {

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

    function editGradeItem($id) {

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

    function fetchGradeItemMarks() {

        if (!$this->panelInit->can(array("gradeItems.showMarks", "gradeItems.controlMarksItem"))) {
            exit;
        }

        $toReturn = array();

        $toReturn['item'] = grade_items::where('id', \Input::get('item'))->first()->toArray();
        $toReturn['subject'] = \subject::where('id', \Input::get('subjectId'))->first()->toArray();
        $toReturn['class'] = \classes::where('id', \Input::get('class_id'))->first()->toArray();

        $toReturn['item']['itemClasses'] = json_decode($toReturn['item']['itemClasses'], true);


        $toReturn['students'] = array();
        $studentArray = \User::where('role', 'student')->where('studentClass', \Input::get('class_id'));
//        if ($this->panelInit->settingsArray['enableSections'] == true) {
//            $studentArray = $studentArray->where('studentSection', \Input::get('sectionId'));
//        }
//        if ($this->data['panelInit']->settingsArray['studentsSort'] != "") {
//            $studentArray = $studentArray->orderByRaw($this->data['panelInit']->settingsArray['studentsSort']);
//        }

        $studentArray = $studentArray->get();

        $itemMarksArray = array();
        $itemMarks = item_marks::where('itemId', \Input::get('item'))
                ->where('classId', \Input::get('class_id'))
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

    function saveGradeItemMarks($item, $class, $subject) {

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

    function gradeITemNotifications($id) {

//        if (!$this->panelInit->can("gradeItems.itemDetailsNot")) {
//            exit;
//        }
//
//        if ($this->panelInit->settingsArray['itemDetailsNotif'] == "0") {
//            return json_encode(array("jsTitle" => $this->panelInit->language['itemDetailsNot'], "jsMessage" => $this->panelInit->language['adjustItemNot']));
//        }

        return json_encode(['true']);
        $gradeItem = \App\Models\grade_items::where('id', $id)->first()->toArray();

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

    public function lectures($id) {
        $lectures = \lectures::where('subjectId', $id)->get();
        foreach ($lectures as $lecture) {
            if (substr($lecture->url, 0, 4) !== "http") {
//                if (file_exists('/uploads/lectures/' . $lecture->subjectId . '/' . $lecture->url)) {
                $lecture->url = '/uploads/lectures/' . $lecture->subjectId . '/' . $lecture->url;
//                } else {
//                    $lecture->url = '/#';
//                }
            }
        }
        $toReturn = array(
            'lectures' => $lectures
        );


        return $toReturn;
    }

    public function playLecture($id) {
        $lecture = \lectures::where('id', $id)->first();
        $external = 'true';
        if (substr($lecture->url, 0, 4) !== "http") {
            $external = 'false';
            $lecture->url = '/school/uploads/lectures/' . $lecture->subjectId . '/' . $lecture->url;
        }
        $toReturn = array(
            'external' => $external,
            'url' => $lecture->url
        );


        return $toReturn;
    }

    public function createLecture() {

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
            $fileInstance->move('uploads/lectures/' . $lectures->subjectId, $newFileName);

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

    function fetchLecture($id) {

        if (!$this->panelInit->can(array("lectures.View", "lectures.editLecture"))) {
//			exit;
        }

        $data = \lectures::where('id', $id)->first()->toArray();
        $data['lectureDescription'] = htmlspecialchars_decode($data['lectureDescription'], ENT_QUOTES);
        $data['createAt'] = $data['createAt'];
        return json_encode($data);
    }

    function editLecture($id) {
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

    public function deleteLecture($id) {

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

}
