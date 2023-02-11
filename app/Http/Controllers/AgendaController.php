<?php

namespace App\Http\Controllers;

class AgendaController extends Controller {

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

    public function list($date) {
        $toReturn = array();
        $role = $this->data['users']->role;
        //done tasks
        $tasks = \App\models\agenda_done_tasks::where('std_id', $this->data['users']->id)->get();
        $tasks_arr = [];
        foreach ($tasks as $task) {
            $tasks_arr[$task->task_id] = $task->update_date;
        }
        if ($this->data['users']->role == "admin") {
            $assignments = \assignments::where('AssignDeadLine', $date)->get();
        } elseif ($this->data['users']->role == "teacher") {
            $assignments = \assignments::where('AssignDeadLine', $date)->where('teacherId', $this->data['users']->id)->get();
        } elseif ($this->data['users']->role == "student") {
            $assignments = \assignments::where('AssignDeadLine', $date)->where('classId', 'like', '%"' . $this->data['users']->studentClass . '"%')->get();
        }
        if (isset($assignments)) {
            foreach ($assignments as $event) {
                $eventsArray['id'] = "ass" . $event->id;
                $eventsArray['status'] = isset($tasks_arr[$eventsArray['id']]);
                $eventsArray['update_date'] = isset($tasks_arr[$eventsArray['id']]) ? $tasks_arr[$eventsArray['id']] : 0;
                $eventsArray['title'] = $event->AssignTitle;
                $eventsArray['start'] = $this->panelInit->unix_to_date($event->AssignDeadLine, 'd-m-Y');
                $eventsArray['date'] = $this->panelInit->unix_to_date($event->AssignDeadLine);
                $eventsArray['backgroundColor'] = '#3c763d';
                $eventsArray['textColor'] = '#3c763d';
                $eventsArray['url'] = "portal#assignments";
                $eventsArray['allDay'] = true;
                $toReturn['data'][$event->subjectId][] = $eventsArray;
            }
        }

        if ($this->data['users']->role == "admin") {
            $homeworks = \homeworks::where('homeworkSubmissionDate', $date)->get();
        } elseif ($this->data['users']->role == "teacher") {
            $homeworks = \homeworks::where('homeworkSubmissionDate', $date)->where('teacherId', $this->data['users']->id)->get();
        } elseif ($this->data['users']->role == "student") {
            $homeworks = \homeworks::where('homeworkSubmissionDate', $date)->where('classId', 'like', '%"' . $this->data['users']->studentClass . '"%')->get();
        }
        if (isset($homeworks)) {
            foreach ($homeworks as $event) {
                $eventsArray['id'] = "hw" . $event->id;
                $eventsArray['status'] = isset($tasks_arr[$eventsArray['id']]);
                $eventsArray['update_date'] = isset($tasks_arr[$eventsArray['id']]) ? $tasks_arr[$eventsArray['id']] : 0;
                $eventsArray['title'] = $event->homeworkTitle;
                $eventsArray['start'] = $this->panelInit->unix_to_date($event->homeworkSubmissionDate, 'd-m-Y');
                $eventsArray['date'] = $this->panelInit->unix_to_date($event->homeworkSubmissionDate);
                $eventsArray['backgroundColor'] = '#009efb';
                $eventsArray['textColor'] = '#fff';
                $eventsArray['url'] = "portal#homework";
                $eventsArray['allDay'] = true;
                $toReturn['data'][$event->subjectId][] = $eventsArray;
            }
        }

        $events = \events::where(function($query) use ($role) {
                    $query = $query->where('eventFor', $role)->orWhere('eventFor', 'all');
                })->where('eventDate', $date)->get();
        foreach ($events as $event) {
            $eventsArray['id'] = "eve" . $event->id;
            $eventsArray['status'] = isset($tasks_arr[$eventsArray['id']]);
            $eventsArray['update_date'] = isset($tasks_arr[$eventsArray['id']]) ? $tasks_arr[$eventsArray['id']] : 0;
            $eventsArray['title'] = $event->eventTitle;
            $eventsArray['start'] = $this->panelInit->unix_to_date($event->eventDate, 'd-m-Y');
            $eventsArray['date'] = $this->panelInit->unix_to_date($event->eventDate);
            $eventsArray['backgroundColor'] = '#7460ee';
            $eventsArray['url'] = "portal#events/" . $event->id;
            $eventsArray['textColor'] = '#fff';
            $eventsArray['allDay'] = true;
            $toReturn['data']['events'][] = $eventsArray;
        }

        $examsList = \exams_list::where('examSchedule', 'LIKE', '%"' . $date . '"%');
        if ($this->data['users']->role == "student") {
            $examsList = $examsList->where('examClasses', 'LIKE', '%"' . $this->data['users']->studentClass . '"%');
        }
        $examsList = $examsList->get();
        foreach ($examsList as $event) {
            $eventsArray['id'] = "exam" . $event->id;
            $eventsArray['status'] = isset($tasks_arr[$eventsArray['id']]);
            $eventsArray['update_date'] = isset($tasks_arr[$eventsArray['id']]) ? $tasks_arr[$eventsArray['id']] : 0;
            $eventsArray['title'] = $event->examTitle;
            $eventsArray['start'] = $this->panelInit->unix_to_date($event->examDate, 'd-m-Y');
            $eventsArray['date'] = $this->panelInit->unix_to_date($event->examDate);
            $eventsArray['backgroundColor'] = '#f62d51';
            $eventsArray['url'] = "portal#examsList";
            $eventsArray['textColor'] = '#f62d51';
            $eventsArray['allDay'] = true;
            $subjects = json_decode($event->examSchedule);
            foreach ($subjects as $subject) {
                if ($subject['stDate'] == $date) {
                    $toReturn['data'][$subject['subject']][] = $eventsArray;
                }
            }
        }

        $newsboard = \newsboard::where(function($query) use ($role) {
                    $query = $query->where('newsFor', $role)->orWhere('newsFor', 'all');
                })->where('newsDate', $date)->get();
        foreach ($newsboard as $event) {
            $eventsArray['id'] = "news" . $event->id;
            $eventsArray['status'] = isset($tasks_arr[$eventsArray['id']]);
            $eventsArray['update_date'] = isset($tasks_arr[$eventsArray['id']]) ? $tasks_arr[$eventsArray['id']] : 0;
            $eventsArray['title'] = $event->newsTitle;
            $eventsArray['start'] = $this->panelInit->unix_to_date($event->newsDate, 'd-m-Y');
            $eventsArray['date'] = $this->panelInit->unix_to_date($event->newsDate);
            $eventsArray['url'] = "portal#newsboard/" . $event->id;
            $eventsArray['backgroundColor'] = '#009688';
            $eventsArray['textColor'] = '#fff';
            $eventsArray['allDay'] = true;
            $toReturn['data']['newsborad'][] = $eventsArray;
        }

        if ($this->data['users']->role == "admin") {
            $onlineExams = \online_exams::where('examDate', $date)->get();
        } elseif ($this->data['users']->role == "teacher") {
            $onlineExams = \online_exams::where('examDate', $date)->where('examTeacher', $this->data['users']->id)->get();
        } elseif ($this->data['users']->role == "student") {
            $onlineExams = \online_exams::where('examDate', $date)->where('examClass', 'like', '%"' . $this->data['users']->studentClass . '"%')->get();
        }
        if (isset($onlineExams)) {
            foreach ($onlineExams as $event) {
                $eventsArray['id'] = "onl" . $event->id;
                $eventsArray['status'] = isset($tasks_arr[$eventsArray['id']]);
                $eventsArray['update_date'] = isset($tasks_arr[$eventsArray['id']]) ? $tasks_arr[$eventsArray['id']] : 0;
                $eventsArray['title'] = $event->examTitle;
                $eventsArray['start'] = $this->panelInit->unix_to_date($event->examDate, 'd-m-Y');
                $eventsArray['date'] = $this->panelInit->unix_to_date($event->examDate);
                $eventsArray['backgroundColor'] = '#ff5722';
                $eventsArray['url'] = "portal#onlineExams";
                $eventsArray['textColor'] = '#ff5722';
                $eventsArray['allDay'] = true;
                $toReturn['data'][$event->examSubject][] = $eventsArray;
            }
        }


        $officialVacation = json_decode($this->panelInit->settingsArray['officialVacationDay']);
        foreach ($officialVacation as $date) {
            if ($date == $date) {
                $eventsArray['id'] = "vac" . $date;
                $eventsArray['status'] = isset($tasks_arr[$eventsArray['id']]);
                $eventsArray['update_date'] = isset($tasks_arr[$eventsArray['id']]) ? $tasks_arr[$eventsArray['id']] : 0;
                $eventsArray['title'] = $this->panelInit->language['nationalVacDays'];
                $eventsArray['start'] = $this->panelInit->unix_to_date($date, 'd-m-Y');
                $eventsArray['date'] = $this->panelInit->unix_to_date($date);
                $eventsArray['backgroundColor'] = 'maroon';
                $eventsArray['url'] = "portal#calender";
                $eventsArray['textColor'] = '#fff';
                $eventsArray['allDay'] = true;
                $toReturn['data']['nationalVacDays'][] = $eventsArray;
            }
        }
        $subjects = \subject::get();
        $subjects_arr = [];
        foreach ($subjects as $subject) {
            $subjects_arr[$subject->id] = $subject->subjectTitle;
        }
        $toReturn['date'] = $this->panelInit->unix_to_date($date);
        $toReturn['subjects'] = $subjects_arr;

        return $toReturn;
    }

    public function markasdone($id) {
        $model = \App\models\agenda_done_tasks::where('std_id', $this->data['users']->id)->where('task_id', $id)->get()->first();
        if ($model) {
            $model->delete();
            return $this->panelInit->apiOutput(false, 'Task', "Undone");
        } else {
            $model = new \App\models\agenda_done_tasks();
            $model->std_id = $this->data['users']->id;
            $model->task_id = $id;
            $model->save();
            return $this->panelInit->apiOutput(true, 'Task', "Done");
        }
    }

}
