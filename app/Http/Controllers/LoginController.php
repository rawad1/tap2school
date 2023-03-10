<?php

namespace App\Http\Controllers;

class LoginController extends Controller {

    var $data = array();

    public function __construct() {
        $this->panelInit = new \DashboardInit();
        $this->data['panelInit'] = $this->panelInit;
    }

    public function index() {
        return \View::make('login', $this->data);
    }

    public function attemp() {
        if (filter_var(\Input::get('email'), FILTER_VALIDATE_EMAIL)) {
            if (\Auth::attempt(array('email' => \Input::get('email'), 'password' => \Input::get('password'), 'activated' => 1, 'account_active' => 1), \Input::get('remember_me'))) {
                if (\Input::get('api')) {
                    echo "1";
                    exit;
                }
                return \Redirect::to('/portal');
            } else {
                if (\Input::get('api')) {
                    echo "0";
                    exit;
                }
                return \Redirect::to('/login')->withErrors(array($this->panelInit->language['chkUserPass']));
            }
        } else {
            if (\Auth::attempt(array('username' => \Input::get('email'), 'password' => \Input::get('password'), 'activated' => 1, 'account_active' => 1), \Input::get('remember_me'))) {
                if (\Input::get('api')) {
                    echo "1";
                    exit;
                }
                return \Redirect::to('/portal');
            } else {
                if (\Input::get('api')) {
                    echo "0";
                    exit;
                }
                return \Redirect::to('/login')->withErrors(array($this->panelInit->language['chkUserPass']));
            }
        }
    }

    public function logout() {
        \Auth::logout();
        return \Redirect::to('/');
    }

    public function forgetpwd() {
        return \View::make('forgetpwd', $this->data);
    }

    public function forgetpwdStepOne() {
        if (filter_var(\Input::get('email'), FILTER_VALIDATE_EMAIL)) {
            $ifUserExists = \User::where('email', \Input::get('email'))->where('account_active', '1');
        } else {
            $ifUserExists = \User::where('username', \Input::get('email'))->where('account_active', '1');
        }
        if ($ifUserExists->count() == 0) {
            return \Redirect::to('/forgetpwd')->withErrors(array($this->panelInit->language['chkUserMail']));
        } else {
            $uniqid = uniqid() . "." . time();

            $ifUserExistsGet = $ifUserExists->first();
            $ifUserExistsGet->restoreUniqId = $uniqid;
            $ifUserExistsGet->save();

            $restoreUrl = \URL::to('/forgetpwd/' . $uniqid);

            $messageBody = "Dear Sir, <br/><br/> Please follow the follwoing link to restore your password : <br/><br/>
			<a href='$restoreUrl'>$restoreUrl</a> <br/><br/>Regards,<br/> Management";

            $SmsHandler = new \MailSmsHandler();
            $SmsHandler->mail($ifUserExistsGet->email, $this->panelInit->settingsArray['siteTitle'] . " | Restore Password", $messageBody, $ifUserExistsGet->fullName);

            $this->data['success'] = $this->panelInit->language['chkMailRestore'];
            return \View::make('forgetpwd', $this->data);
        }
    }

    public function forgetpwdStepTwo($uniqid) {
        $ifUserExists = \User::where('restoreUniqId', $uniqid)->where('account_active', '1');
        if ($ifUserExists->count() > 0) {
            $uniqidExploded = explode(".", $uniqid);
            if ($uniqidExploded[1] + 86400 > time()) {
                if (\Input::get('password') || \Input::get('rePassword')) {
                    if (\Input::get('password') == "" || \Input::get('rePassword') == "" || \Input::get('password') != \Input::get('rePassword')) {
                        $this->data['errorsList'] = $this->panelInit->language['chkInputFields'];
                        return \View::make('forgetpwdContinue', $this->data);
                    } else {
                        $ifUserExistsGet = $ifUserExists->first();
                        $ifUserExistsGet->restoreUniqId = "";
                        $ifUserExistsGet->password = \Hash::make(\Input::get('password'));
                        $ifUserExistsGet->save();
                        $this->data['success'] = $this->panelInit->language['pwdChangedSuccess'];
                        return \View::make('forgetpwd', $this->data);
                    }
                } else {
                    return \View::make('forgetpwdContinue', $this->data);
                }
            } else {
                $this->data['success'] = $this->panelInit->language['expRestoreId'];
                return \View::make('forgetpwd', $this->data);
            }
        } else {
            $this->data['success'] = $this->panelInit->language['invRstoreId'];
            return \View::make('forgetpwd', $this->data);
        }
    }

    public function register() {
        if (isset($this->panelInit->settingsArray['allowPublicReg']) AND $this->panelInit->settingsArray['allowPublicReg'] == "1") {
            return \View::make('register', $this->data);
        } else {
            return \Redirect::to('/login');
        }
    }

    public function registerPost() {
        if (\Input::get('role') == "") {
            return json_encode(array("jsTitle" => $this->panelInit->language['registerAcc'], "jsStatus" => "0", "jsMessage" => $this->panelInit->language['mustSelAccType']));
            exit;
        }
        if (\Input::get('username') == "") {
            return json_encode(array("jsTitle" => $this->panelInit->language['registerAcc'], "jsStatus" => "0", "jsMessage" => $this->panelInit->language['mustSelUsername']));
            exit;
        }
        if (\Input::get('password') == "") {
            return json_encode(array("jsTitle" => $this->panelInit->language['registerAcc'], "jsStatus" => "0", "jsMessage" => $this->panelInit->language['mustTypePwd']));
            exit;
        }
        if (\Input::get('fullName') == "") {
            return json_encode(array("jsTitle" => $this->panelInit->language['registerAcc'], "jsStatus" => "0", "jsMessage" => $this->panelInit->language['mustTypeFullName']));
            exit;
        }
        if (!filter_var(\Input::get('email'), FILTER_VALIDATE_EMAIL) AND \Input::get('email') != "") {
            return json_encode(array("jsTitle" => $this->panelInit->language['registerAcc'], "jsStatus" => "0", "jsMessage" => $this->panelInit->language['invEmailAdd']));
            exit;
        }
        if (\User::where('username', trim(\Input::get('username')))->count() > 0) {
            return json_encode(array("jsTitle" => $this->panelInit->language['registerAcc'], "jsStatus" => "0", "jsMessage" => $this->panelInit->language['usernameUsed']));
            exit;
        }
        if (\Input::get('role') == "student" AND isset($this->panelInit->settingsArray['emailIsMandatory']) AND $this->panelInit->settingsArray['emailIsMandatory'] == 1) {
            if (\User::where('email', \Input::get('email'))->count() > 0) {
                return json_encode(array("jsTitle" => $this->panelInit->language['registerAcc'], "jsStatus" => "0", "jsMessage" => $this->panelInit->language['mailUsed']));
                exit;
            }
        }
        if (\Input::has('studentRollId') AND \User::where('studentRollId', trim(\Input::get('studentRollId')))->count() > 0) {
            return json_encode(array("jsTitle" => $this->panelInit->language['registerAcc'], "jsStatus" => "0", "jsMessage" => "Student roll id already used before."));
        }

        //Select the default role
        $def_role = \roles::where('def_for', \Input::get('role'))->select('id');
        if ($def_role->count() == 0) {
            return $this->panelInit->apiOutput(false, 'Import', 'No default role assigned for teachers, Please contact administartor');
        }
        $def_role = $def_role->first();

        $user = new \User();
        $user->username = \Input::get('username');
        if (\Input::get('email') == "") {
            $user->email = "";
        } else {
            $user->email = \Input::get('email');
        }
        $user->password = \Hash::make(\Input::get('password'));
        $user->fullName = \Input::get('fullName');
        $user->role = \Input::get('role');
        $user->activated = 0;
        $user->studentRollId = \Input::get('studentRollId');
        if (\Input::has('birthday')) {
            $user->birthday = $this->panelInit->date_to_unix(\Input::get('birthday'));
        }
        $user->gender = \Input::get('gender');
        $user->address = \Input::get('address');
        $user->phoneNo = \Input::get('phoneNo');
        $user->mobileNo = \Input::get('mobileNo');
        $user->account_active = 1;
        if (\Input::has('studentClass')) {
            $user->studentAcademicYear = $this->panelInit->selectAcYear;
            $user->studentClass = \Input::get('studentClass');
            if ($this->panelInit->settingsArray['enableSections'] == true) {
                $user->studentSection = \Input::get('studentSection');
            }
        }
        $user->parentProfession = \Input::get('parentProfession');

        if (\Input::get('studentInfo') != "") {
            $user->parentOf = json_encode(\Input::get('studentInfo'));
        }

        $user->role_perm = $def_role->id;
        $user->comVia = '["mail","sms","phone"]';
        $user->save();

        if (\Input::get('role') == "student" AND \Input::has('studentClass')) {
            $studentAcademicYears = new \student_academic_years();
            $studentAcademicYears->studentId = $user->id;
            $studentAcademicYears->academicYearId = $this->panelInit->selectAcYear;
            $studentAcademicYears->classId = \Input::get('studentClass');
            if ($this->panelInit->settingsArray['enableSections'] == true) {
                $studentAcademicYears->sectionId = \Input::get('studentSection');
            }
            $studentAcademicYears->save();
        }

        if ($user->role == "student") {
            $this->panelInit->collectFeesRegister($user);
        }

        $array = array("id" => $user->id);
        return $array;
        exit;
    }

    public function sectionsList($classes = "") {
        $sectionsList = array();

        if ($classes == "") {
            if (!\Input::has('classes')) {
                return $sectionsList;
            }
            $classes = \Input::get('classes');
        }

        if (is_array($classes)) {
            return \sections::whereIn('classId', $classes)->get()->toArray();
        } else {
            return \sections::where('classId', $classes)->get()->toArray();
        }
    }

    public function registerClasses() {
        return \classes::where('classAcademicYear', $this->panelInit->selectAcYear)->get();
    }
	
	public function error() {
		//License
        if(file_exists('license.txt')){
            $txt = file_get_contents('license.txt');
            list($txt, $enc_iv) = explode("::", $txt);
            $cipher_method = 'aes-128-ctr';
            $enc_key = openssl_digest(php_uname(), 'SHA256', TRUE);
            $decrypted_response = openssl_decrypt($txt, $cipher_method, $enc_key, 0, hex2bin($enc_iv));
        }else{
            die('Licesnse file not exist');
        }
		
        $this->data['terms'] = htmlspecialchars_decode(json_decode($decrypted_response)->data->message, ENT_QUOTES);
        return \View::make('terms', $this->data);
    }
	
    public function terms() {
        $settings = \settings::where('fieldName', 'schoolTerms')->first()->toArray();
        $this->data['terms'] = htmlspecialchars_decode($settings['fieldValue'], ENT_QUOTES);
        return \View::make('terms', $this->data);
    }

    public function license() {
        $ip = $_SERVER['SERVER_ADDR'];

// PHP code to get the MAC address of Client 

        $macCommandString = "cat /sys/class/net/ens192/address";
        $mac = shell_exec($macCommandString);
		$url =  sprintf("%s://%s",isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',	$_SERVER['SERVER_NAME']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://licensing.tecomsa.me/api/v1/licenses/check");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
                http_build_query([
            'product_key' => 'my_product_key_test',
            'url' => $url,
            'real_ip' => $ip,
            'mac_address' => trim($mac),
            'users' => '2',
        ]));
		
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);

        //Encrypt License
        $cipher_method = 'aes-128-ctr';
        $enc_key = openssl_digest(php_uname(), 'SHA256', TRUE);
        $enc_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher_method));
        $crypted_response = openssl_encrypt($server_output, $cipher_method, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);
        file_put_contents('license.txt', $crypted_response);
				
        //Decrypt License
        //$txt = file_get_contents('license.txt');
        //list($txt, $enc_iv) = explode("::", $txt);
        //$cipher_method = 'aes-128-ctr';
        //$enc_key = openssl_digest(php_uname(), 'SHA256', TRUE);
        //$decrypted_response = openssl_decrypt($txt, $cipher_method, $enc_key, 0, hex2bin($enc_iv));
	
        //$this->data['terms'] = htmlspecialchars_decode($decrypted_response, ENT_QUOTES);
        //return \View::make('terms', $this->data);

    }

    public function searchStudents($student) {
        $students = \User::where('role', 'student')->where('fullName', 'like', '%' . $student . '%')->orWhere('username', 'like', '%' . $student . '%')->orWhere('email', 'like', '%' . $student . '%')->get();
        $retArray = array();
        foreach ($students as $student) {
            $retArray[$student->id] = array("id" => $student->id, "name" => $student->fullName, "email" => $student->email);
        }
        return json_encode($retArray);
    }

    public function searchUsers($userType, $query) {
        $userType = explode(",", $userType);
        $students = \User::where('fullName', 'like', '%' . $query . '%')->orWhere('username', 'like', '%' . $query . '%')->orWhere('email', 'like', '%' . $query . '%');
        if (!in_array("all", $userType)) {
            //	$students->whereIn('role',$userType);
        }
        $students = $students->get();
        $retArray = array();
        foreach ($students as $student) {
            $retArray[$student->id] = array("id" => $student->id, "name" => $student->fullName, "role" => $student->role, "email" => $student->email);
        }
        return json_encode($retArray);
    }



}
