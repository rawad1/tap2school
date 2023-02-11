<?php
namespace App\Http\Controllers;

class biometricController extends Controller {

	var $data = array();
	var $panelInit ;
	var $layout = 'dashboard';

	public function __construct(){
		if(app('request')->header('Authorization') != "" || \Input::has('token')){
			$this->middleware('jwt.auth');
		}else{
			$this->middleware('authApplication');
		}

		$this->panelInit = new \DashboardInit();
		$this->data['panelInit'] = $this->panelInit;
		$this->data['breadcrumb']['Settings'] = \URL::to('/dashboard/languages');
		$this->data['users'] = $this->panelInit->getAuthUser();
	}

	public function get_devices()
	{
		if($this->data['users']->role != "admin") exit;

		$devices = array();
		if($this->panelInit->settingsArray['biometric_device_ip'] != ""){
			$devices[] = $this->panelInit->settingsArray['biometric_device_ip'];
		}

		return $devices;
	}

	public function sync_devices(){

		if($this->data['users']->role != "admin") exit;
		
		$devices = json_decode(\Input::get('devices'),true);
		$attendance = json_decode(\Input::get('attendance'),true);

		if(!isset($this->panelInit->settingsArray['timezone'])){
			$this->panelInit->settingsArray['timezone'] = "Europe/London";
		}

		if(is_array($devices)){

			$settings = \settings::where('fieldName','biometric_device_status')->first();
			$settings->fieldValue = json_encode($devices);
			$settings->save();
			
		}

		//Get attendance array
		$user_bio_ids = array();
		$user_ids = array();
		$user_att = array();
		$user_list = array();
		if(is_array($attendance)){
			foreach ($attendance as $key => $value) {
				$user_bio_ids[] = $value['userId'];

				//2018-6-30 22:33:30
				$value['date'] = $this->adjust_data_format($value['date']);
				$value['original_date'] = $value['date'];
				$splitted_date = explode(" ", $value['date']);
				$value['date'] = $splitted_date[0];
				$timezone = new \DateTimeZone($this->panelInit->settingsArray['timezone']);
				$d = \DateTime::createFromFormat('Y-m-d', $value['date'] ,$timezone);
				$d->setTime(0,0,0);

				$value['timestamp'] = $d->getTimestamp();
				$user_att[ $value['userId'] ] = $value;
			}
		}

		//Get users list
		$users_list = \User::whereIn('biometric_id',$user_bio_ids)->select('id','fullName','role','biometric_id','studentClass','firebase_token')->get()->toArray();
		foreach ($users_list as $key => $value) {
			$user_list[ $value['biometric_id'] ] = $value;
		}

		//Filter attendance
		$send_notifications = array();
		foreach ($user_att as $key => $value) {
			if(!isset($user_list[ $value['userId'] ])){
				continue;
			}
			if($user_list[ $value['userId'] ]['role'] == "student" && $this->panelInit->settingsArray['attendanceModel'] == "subject"){
				continue;
			}

			$attendanceN = \attendance::where('date', $value['timestamp'] )->where('studentId', $user_list[ $value['userId'] ]['id'] );
			if($attendanceN->count() > 0){
				$attendanceN = $attendanceN->first();
				$attendanceN->status = 1;
				$attendanceN->save();
			}else{
				$attendanceN = new \attendance();
				if( $user_list[ $value['userId'] ]['studentClass'] != "" AND $user_list[ $value['userId'] ]['studentClass'] != 0){
					$attendanceN->classId = $user_list[ $value['userId'] ]['studentClass'];
				}else{
					$attendanceN->classId = 0;
				}
				$attendanceN->date = $value['timestamp'];
				$attendanceN->studentId = $user_list[ $value['userId'] ]['id'];
				$attendanceN->status = 1;
				$attendanceN->subjectId = 0;
				$attendanceN->save();
			}

			if( $this->data['panelInit']->settingsArray['sAttendanceInOut'] == 1 AND isset( $value['state'] ) AND ( $user_list[ $value['userId'] ]['role'] = "teacher" || $user_list[ $value['userId'] ]['role'] == "employee" ) ){
				
				$timezone = new \DateTimeZone($this->panelInit->settingsArray['timezone']);
				$d = \DateTime::createFromFormat('Y-m-d H:i:s', $value['original_date'] ,$timezone );
				
				if( $value['state'] == "0" ){
					$attendanceN->in_time = $d->format('g:i a');
				}
				if( $value['state'] == "1" ){
					$attendanceN->out_time = $d->format('g:i a');
				}
			}
			$attendanceN->save();
			
			$send_notifications = array( "role" => $user_list[ $value['userId'] ]['role'],"id"=> $user_list[ $value['userId'] ]['id'],"fullName"=> $user_list[ $value['userId'] ]['fullName'],"firebase_token"=>$user_list[ $value['userId'] ]['firebase_token'],"date"=>$value['date'],"attendance"=>1 );

			if( $user_list[ $value['userId'] ]['role'] == "student" ){
				$this->notify_std_users($send_notifications);
			}else{
				$this->notify_stf_users($send_notifications);
			}

		}
		
		echo "Updated Attendance for : ".count($send_notifications);

	}

	public function notify_std_users($value){
		if($this->panelInit->settingsArray['absentNotif'] == "mail" || $this->panelInit->settingsArray['absentNotif'] == "mailsms"){
			$mail = true;
		}
		if($this->panelInit->settingsArray['absentNotif'] == "sms" || $this->panelInit->settingsArray['absentNotif'] == "mailsms"){
			$sms = true;
		}
		if(isset($mail) || isset($sms)){
			$mailTemplate = \mailsms_templates::where('templateTitle','Student Absent')->first();
		}

		if(!is_array($this->panelInit->settingsArray['absentNotifWhen'])){
			$this->panelInit->settingsArray['absentNotifWhen'] = json_decode($this->panelInit->settingsArray['absentNotifWhen'],true);
		}

		if( $this->panelInit->settingsArray['absentNotif'] != "0" AND isset($this->panelInit->settingsArray['absentNotifWhen']) AND is_array($this->panelInit->settingsArray['absentNotifWhen']) AND in_array($value['attendance'] , $this->panelInit->settingsArray['absentNotifWhen']) ) {

			$parents = \User::where('parentOf','like','%"'.$value['id'].'"%')->orWhere('parentOf','like','%:'.$value['id'].'}%')->get();
			$student = \User::where('id',$value['id'])->first();

			$absentStatus = "";
			switch ($value['attendance']) {
				case '0':
					$absentStatus = $this->panelInit->language['Absent'];
					break;
				case '1':
					$absentStatus = $this->panelInit->language['Present'];
					break;
				case '2':
					$absentStatus = $this->panelInit->language['Late'];
					break;
				case '3':
					$absentStatus = $this->panelInit->language['LateExecuse'];
					break;
				case '4':
					$absentStatus = $this->panelInit->language['earlyDismissal'];
					break;
				case '9':
					$absentStatus = $this->panelInit->language['acceptedVacation'];
					break;
			}
			$MailSmsHandler = new \MailSmsHandler();

			//Send to parents
			foreach ($parents as $parent) {
				if(isset($mail) AND strpos($parent->comVia, 'mail') !== false){
					$studentTemplate = $mailTemplate->templateMail;
					$examGradesTable = "";
					$searchArray = array("{studentName}","{studentRoll}","{studentEmail}","{studentUsername}","{parentName}","{parentEmail}","{absentDate}","{absentStatus}","{schoolTitle}");
					$replaceArray = array($student->fullName,$student->studentRollId,$student->email,$student->username,$parent->fullName,$parent->email,\Input::get('attendanceDay'),$absentStatus,$this->panelInit->settingsArray['siteTitle']);
					$studentTemplate = str_replace($searchArray, $replaceArray, $studentTemplate);
					$MailSmsHandler->mail($parent->email,$this->panelInit->language['absentReport'],$studentTemplate);
				}
				if(isset($sms) AND $parent->mobileNo != "" AND strpos($parent->comVia, 'sms') !== false){
					$origin_template = $mailTemplate->templateSMS;
					$examGradesTable = "";
					$searchArray = array("{studentName}","{studentRoll}","{studentEmail}","{studentUsername}","{parentName}","{parentEmail}","{absentDate}","{absentStatus}","{schoolTitle}");
					$replaceArray = array($student->fullName,$student->studentRollId,$student->email,$student->username,$parent->fullName,$parent->email,\Input::get('attendanceDay'),$absentStatus,$this->panelInit->settingsArray['siteTitle']);
					$studentTemplate = str_replace($searchArray, $replaceArray, $origin_template);
					$MailSmsHandler->sms($parent->mobileNo,$studentTemplate);
				}

				//Send Push Notifications
				if($parent->firebase_token != ""){
					$this->panelInit->send_push_notification($parent->firebase_token,$this->panelInit->language['attNNotif']." : " . $student->fullName . " ".$this->panelInit->language['is']." " . $absentStatus . " - ".$this->panelInit->language['Date']." : " . \Input::get('attendanceDay'),$this->panelInit->language['Attendance'],"attendance");					
				}
			}

			//Send to students
			if(isset($mail) AND strpos($student->comVia, 'mail') !== false){
				$studentTemplate = $mailTemplate->templateMail;
				$examGradesTable = "";
				$searchArray = array("{studentName}","{studentRoll}","{studentEmail}","{studentUsername}","{parentName}","{parentEmail}","{absentDate}","{absentStatus}","{schoolTitle}");
				$replaceArray = array($student->fullName,$student->studentRollId,$student->email,$student->username,$student->fullName,$student->email,\Input::get('attendanceDay'),$absentStatus,$this->panelInit->settingsArray['siteTitle']);
				$studentTemplate = str_replace($searchArray, $replaceArray, $studentTemplate);
				$MailSmsHandler->mail($student->email,$this->panelInit->language['absentReport'],$studentTemplate);
			}
			if(isset($sms) AND $student->mobileNo != "" AND strpos($student->comVia, 'sms') !== false){
				$origin_template = $mailTemplate->templateSMS;
				$examGradesTable = "";
				$searchArray = array("{studentName}","{studentRoll}","{studentEmail}","{studentUsername}","{parentName}","{parentEmail}","{absentDate}","{absentStatus}","{schoolTitle}");
				$replaceArray = array($student->fullName,$student->studentRollId,$student->email,$student->username,$student->fullName,$student->email,\Input::get('attendanceDay'),$absentStatus,$this->panelInit->settingsArray['siteTitle']);
				$studentTemplate = str_replace($searchArray, $replaceArray, $origin_template);
				$MailSmsHandler->sms($student->mobileNo,$studentTemplate);
			}
			if($student->firebase_token != ""){
				$this->panelInit->send_push_notification($student->firebase_token,$this->panelInit->language['attNNotif']." : " . $student->fullName . " ".$this->panelInit->language['is']." " . $absentStatus . " - ".$this->panelInit->language['Date']." : " . \Input::get('attendanceDay'),$this->panelInit->language['Attendance'],"attendance");					
			}

		}

	}

	public function notify_stf_users($user){
		$this->panelInit->send_push_notification($user['firebase_token'],"Your attendance is : ".$this->panelInit->language['Present']." - Date :".$user['date'],$this->panelInit->language['staffAttendance']);
	}

	public function adjust_data_format($date){
		$date = explode(" ", $date);
		$date[1] = explode(":", $date[1]);

		if($date[1][0] < 10){
			$date[1][0] = "0".$date[1][0];
		}

		if($date[1][1] < 10){
			$date[1][1] = "0".$date[1][1];
		}

		if($date[1][2] < 10){
			$date[1][2] = "0".$date[1][2];
		}

		return $date[0] . " ".$date[1][0].":".$date[1][1].":".$date[1][2];
	}
}
