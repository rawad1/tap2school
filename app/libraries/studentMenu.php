<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$menu = array(
    "dashboard" => array("title" => "dashboard", "icon" => "mdi mdi-gauge", "url" => URL::to('portal#/')),
    "staticContent" => array("title" => "staticPages", "icon" => "mdi mdi-book-multiple", "activated" => "staticpagesAct",
        "children" => array(
            "controlStatic" => array("title" => "controlPages", "url" => URL::to('portal#/static'), "role_perm" => array("staticPages.list", "staticPages.delPage", "staticPages.editPage", "staticPages.addPage"))
        )
    ),
    "school" => array("title" => "School", "icon" => "mdi mdi-briefcase",
        "children" => array(
            "wel_office" => array("title" => "Welcome Office", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "lectures" => array("title" => "Lectures", "url" => URL::to('portal#/lectures'), "role_perm" => array("studyMaterial.list", "studyMaterial.addMaterial", "studyMaterial.editMaterial", "studyMaterial.delMaterial", "studyMaterial.Download"), "icon" => "mdi mdi-book"),
            "classes" => array("title" => "classes", "url" => URL::to('portal#/classes'), "role_perm" => array("classes.list", "classes.addClass", "classes.editClass", "classes.delClass")),
            "sections" => array("title" => "Sections", "url" => URL::to('portal#/sections'), "role_perm" => array("sections.list", "sections.addSection", "sections.editSection", "sections.delSection")),
            "certificates" => array("title" => "Certificates", "url" => URL::to("portal#/certificates"), "icon" => "mdi mdi-certificate", "role_perm" => array("Certificates.list", "Certificates.add_cert", "Certificates.edit_cert", "Certificates.del_cert", "Certificates.Download"), "activated" => "certAct"),
            "id_cards" => array("title" => "id_cards", "url" => URL::to("portal#/id_cards"), "icon" => "mdi mdi-certificate", "role_perm" => array("id_cards.list", "id_cards.add_card", "id_cards.edit_card", "id_cards.del_card"), "activated" => "cardAct"),
            "reports" => array("title" => "Reports", "url" => URL::to('portal#/reports'), "role_perm" => array("Reports.Reports"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "library" => array("title" => "Library", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "students" => array("title" => "Students", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "employees" => array("title" => "Employees", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "parents" => array("title" => "parents", "url" => URL::to('portal#/parents'), "role_perm" => array("parents.list", "parents.AddParent", "parents.editParent", "parents.delParent", "parents.Approve", "parents.Import", "parents.Export"), "icon" => "mdi mdi-account-multiple"),
            "gradelevels" => array("title" => "gradeLevels", "url" => URL::to('portal#/gradeLevels'), "role_perm" => array("gradeLevels.list", "gradeLevels.addLevel", "gradeLevels.editGrade", "gradeLevels.delGradeLevel"), "icon" => "mdi mdi-arrange-send-backward"),
            "acctounting" => array("title" => "Accounting", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "payroll" => array("title" => "Payroll", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "transport" => array("title" => "Transport", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "hostel" => array("title" => "Hostel", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "invemtory" => array("title" => "Inventory", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
        )
    ),
    "schedule" => array("title" => "Schedule", "icon" => "mdi mdi-calendar-clock",
        "children" => array(
            "agenda" => array("title" => "Agenda", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "calender" => array("title" => "Calender", "url" => URL::to('portal#/calender'), "icon" => "mdi mdi-calendar-text", "activated" => "calendarAct"),
            "classSchedule" => array("title" => "classSch", "url" => URL::to('portal#/classschedule'), "role_perm" => array("classSch.list", "classSch.addSch", "classSch.editSch", "classSch.delSch"), "icon" => "mdi mdi-timelapse", "activated" => "classSchAct"),
            "myAttendance" => array("title" => "myAttendance", "url" => URL::to('portal#/attendanceStats'), "role_perm" => array("myAttendance.myAttendance"), "icon" => "mdi mdi-check", "activated" => "attendanceAct"),
            "attendance" => array("title" => "Attendance", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "staffAttendance" => array("title" => "Staff Attendance", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
            "vacation" => array("title" => "Vacation", "url" => URL::to('portal#/#'), "role_perm" => array("Incomes.list"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
        )
    ),
    "communication" => array("title" => "Communication", "icon" => "mdi mdi-message-text-outline",
        "children" => array(
            "messages" => array("title" => "Messages", "url" => URL::to('portal#/messages'), "icon" => "mdi mdi-message-text-outline", "activated" => "messagesAct", "role_perm" => array("Messages.list")),
            "mailsms" => array("title" => "mailsms", "url" => URL::to('portal#/mailsms'), "role_perm" => array('mailsms.mailSMSSend'), "icon" => "mdi mdi-cellphone-iphone", "activated" => "mailSmsAct"),
            "mobNotif" => array("title" => "mobileNotifications", "url" => URL::to('portal#/mobileNotif'), "role_perm" => array("mobileNotifications.sendNewNotification"), "icon" => "mdi mdi-telegram", "activated" => "sendNotifAct"),
        )
    ),
    "agreement" => array("title" => "Agreement", "icon" => "mdi mdi-book-multiple",
        "children" => array(
            "health" => array("title" => "Health & Safety", "url" => URL::to('portal#/#'), "role_perm" => array("mediaCenter.View", "frontendCMSpages.addPage", "frontendCMSpages.editPage", "frontendCMSpages.delPage")),
            "dress_code" => array("title" => "Dress Code", "url" => URL::to('portal#/#'), "role_perm" => array("mediaCenter.View")),
            "rules" => array("title" => "School Rules", "url" => URL::to('portal#/#'), "role_perm" => array("mediaCenter.View", "mediaCenter.addAlbum", "mediaCenter.editAlbum", "mediaCenter.delAlbum", "mediaCenter.addMedia", "mediaCenter.editMedia", "mediaCenter.delMedia"), "icon" => "mdi mdi-folder-multiple-image", "activated" => "mediaAct"),
        )
    ),
);





//$menu = array(
//    "dashboard" => array("title" => "dashboard", "icon" => "mdi mdi-gauge", "url" => URL::to('portal#/')),
//    "wel_office" => array("title" => "wel_office", "icon" => "mdi mdi-book-multiple",
//        "children" => array(
//            "visitors" => array("title" => "visitors", "url" => URL::to("portal#/visitors"), "role_perm" => array("visitors.list", "visitors.View", "visitors.add_vis", "visitors.edit_vis", "visitors.del_vis", "visitors.Download", "visitors.Export"), "activated" => "visitorsAct"),
//            "phone_calls" => array("title" => "phn_calls", "url" => URL::to("portal#/phone_calls"), "role_perm" => array("phn_calls.list", "phn_calls.View", "phn_calls.add_call", "phn_calls.edit_call", "phn_calls.del_call", "phn_calls.Export"), "activated" => "phoneCallsAct"),
//            "postal" => array("title" => "postal", "url" => URL::to("portal#/postal"), "role_perm" => array("postal.list", "postal.add_postal", "postal.edit_postal", "postal.del_postal", "postal.Download", "postal.Export"), "activated" => "postalAct"),
//            "con_mess" => array("title" => "con_mess", "url" => URL::to("portal#/con_mess"), "role_perm" => array("con_mess.list", "con_mess.View", "con_mess.del_mess", "con_mess.Export"), "activated" => "conMessAct"),
//            "enquiries" => array("title" => "enquiries", "url" => URL::to("portal#/enquiries"), "role_perm" => array("enquiries.list", "enquiries.View", "enquiries.add_enq", "enquiries.edit_enq", "enquiries.del_enq", "enquiries.Download", "enquiries.Export"), "activated" => "enquiriesAct"),
//            "complaints" => array("title" => "complaints", "url" => URL::to("portal#/complaints"), "role_perm" => array("complaints.list", "complaints.View", "complaints.add_complaint", "complaints.edit_complaint", "complaints.del_complaint", "complaints.Download", "complaints.Export"), "activated" => "complainAct"),
//            "wel_office_cat" => array("title" => "wel_office_cat", "url" => URL::to("portal#/wel_office_cat"), "role_perm" => array("wel_office_cat.list", "wel_office_cat.add_cat", "wel_office_cat.edit_cat", "wel_office_cat.del_cat")),
//        )
//    ),
//    "staticContent" => array("title" => "staticPages", "icon" => "mdi mdi-book-multiple", "activated" => "staticpagesAct",
//        "children" => array(
//            "controlStatic" => array("title" => "controlPages", "url" => URL::to('portal#/static'), "role_perm" => array("staticPages.list", "staticPages.delPage", "staticPages.editPage", "staticPages.addPage"))
//        )
//    ),
//    "agenda" => array(
//        "title" => "Agenda",
//        "icon" => "mdi mdi-book",
//        "children" => array(
//            "materials" => array("title" => "studyMaterial", "url" => URL::to('portal#/materials'), "role_perm" => array("studyMaterial.list", "studyMaterial.addMaterial", "studyMaterial.editMaterial", "studyMaterial.delMaterial", "studyMaterial.Download"), "icon" => "mdi mdi-cloud-download", "activated" => "materialsAct"),
//            "homework" => array("title" => "Homework", "url" => URL::to('portal#/homework'), "role_perm" => array("Homework.list", "Homework.View", "Homework.addHomework", "Homework.editHomework", "Homework.delHomework", "Homework.Download"), "icon" => "mdi mdi-book", "activated" => "homeworkAct"),
//            "assignments" => array("title" => "Assignments", "url" => URL::to('portal#/assignments'), "role_perm" => array("Assignments.list", "Assignments.Download", "Assignments.AddAssignments", "Assignments.editAssignment", "Assignments.delAssignment", "Assignments.viewAnswers", "Assignments.applyAssAnswer"), "icon" => "mdi mdi-file-pdf", "activated" => "assignmentsAct"),
//        )
//    ),
//    "lectures" => array("title" => "Lectures", "url" => URL::to('portal#/lectures'), "role_perm" => array("studyMaterial.list", "studyMaterial.addMaterial", "studyMaterial.editMaterial", "studyMaterial.delMaterial", "studyMaterial.Download"), "icon" => "mdi mdi-book"),
//    "exams" => array(
//        "title" => "Exams",
//        "icon" => "mdi mdi-playlist-check",
//        "children" => array(
//            "examslist" => array("title" => "Pen & Paper Exams", "url" => URL::to('portal#/examsList'), "role_perm" => array("examsList.list", "examsList.addExam", "examsList.editExam", "examsList.delExam", "examsList.examDetailsNot", "examsList.showMarks", "examsList.controlMarksExam"), "icon" => "mdi mdi-playlist-check"),
//            "onlineexams" => array("title" => "onlineExams", "url" => URL::to('portal#/onlineExams'), "icon" => "mdi mdi-account-network", "role_perm" => array("onlineExams.list", "onlineExams.addExam", "onlineExams.editExam", "onlineExams.delExam", "onlineExams.takeExam", "onlineExams.showMarks", "onlineExams.QuestionsArch"), "activated" => "onlineexamsAct"),
//            "gradeItems" => array("title" => "Grade Items", "url" => URL::to('portal#/gradeItems'), "icon" => "mdi mdi-account-network", "role_perm" => array("gradeItems.list", "gradeItems.addItem", "gradeItems.editItem", "gradeItems.delItem", "gradeItems.showMarks"), "activated" => "onlineexamsAct"),
//        )
//    ),
//    "messages" => array("title" => "Messages", "url" => URL::to('portal#/messages'), "icon" => "mdi mdi-message-text-outline", "activated" => "messagesAct", "role_perm" => array("Messages.list")),
//    "mailsms" => array("title" => "mailsms", "url" => URL::to('portal#/mailsms'), "role_perm" => array('mailsms.mailSMSSend'), "icon" => "mdi mdi-cellphone-iphone", "activated" => "mailSmsAct"),
//    "mobNotif" => array("title" => "mobileNotifications", "url" => URL::to('portal#/mobileNotif'), "role_perm" => array("mobileNotifications.sendNewNotification"), "icon" => "mdi mdi-telegram", "activated" => "sendNotifAct"),
//    "calender" => array("title" => "Calender", "url" => URL::to('portal#/calender'), "icon" => "mdi mdi-calendar-text", "activated" => "calendarAct"),
//    "classSchedule" => array("title" => "classSch", "url" => URL::to('portal#/classschedule'), "role_perm" => array("classSch.list", "classSch.addSch", "classSch.editSch", "classSch.delSch"), "icon" => "mdi mdi-timelapse", "activated" => "classSchAct"),
//    "attendance" => array("title" => "Attendance", "icon" => "mdi mdi-check-all", "activated" => "attendanceAct",
//        "children" => array(
//            "takeAttendance" => array("title" => "takeAttendance", "role_perm" => array("Attendance.takeAttendance"), "url" => URL::to('portal#/attendance')),
//            "attReport" => array("title" => "attReport", "role_perm" => array("Attendance.attReport"), "url" => URL::to('portal#/attendance_report')),
//        )
//    ),
//    "staffAttendance" => array("title" => "staffAttendance", "icon" => "mdi mdi-check", "activated" => "staffAttendanceAct",
//        "children" => array(
//            "takeAttendance" => array("title" => "takeAttendance", "role_perm" => array("staffAttendance.takeAttendance"), "url" => URL::to('portal#/staffAttendance')),
//            "attReport" => array("title" => "attReport", "role_perm" => array("staffAttendance.attReport"), "url" => URL::to('portal#/staffAttendance_report')),
//        )
//    ),
//    "vacation" => array("title" => "Vacation", "icon" => "mdi mdi-airplane", "activated" => "vacationAct",
//        "children" => array(
//            "reqVacation" => array("title" => "reqVacation", "url" => URL::to('portal#/vacation'), "role_perm" => array("Vacation.reqVacation")),
//            "appVacation" => array("title" => "appVacation", "url" => URL::to('portal#/vacation/approve'), "role_perm" => array("Vacation.appVacation")),
//            "myVacation" => array("title" => "myVacation", "url" => URL::to('portal#/vacation/mine'), "role_perm" => array("Vacation.myVacation")),
//        )
//    ),
//    "myAttendance" => array("title" => "myAttendance", "url" => URL::to('portal#/attendanceStats'), "role_perm" => array("myAttendance.myAttendance"), "icon" => "mdi mdi-check", "activated" => "attendanceAct"),
//    "library" => array("title" => "Library", "icon" => "mdi mdi-library", "activated" => "bookslibraryAct",
//        "children" => array(
//            "library_issues" => array("title" => "issue_book", "url" => URL::to("portal#/library_issues"), "role_perm" => array("issue_book.list", "issue_book.add_issue", "issue_book.edit_issue", "issue_book.del_issue", "issue_book.Export")),
//            "book_return" => array("title" => "book_return", "url" => URL::to("portal#/library_return"), "role_perm" => array("issue_book.book_return")),
//            "listBooks" => array("title" => "listBooks", "url" => URL::to('portal#/library'), "role_perm" => array("Library.list", "Library.addBook", "Library.editBook", "Library.delLibrary", "Library.Download")),
//            "subscription" => array("title" => "mngSub", "url" => URL::to('portal#/lib_subscription'), "role_perm" => array("Library.mngSub")),
//        )
//    ),
//            "media" => array("title" => "mediaCenter", "url" => URL::to('portal#/media'), "role_perm" => array("mediaCenter.View", "mediaCenter.addAlbum", "mediaCenter.editAlbum", "mediaCenter.delAlbum", "mediaCenter.addMedia", "mediaCenter.editMedia", "mediaCenter.delMedia"), "icon" => "mdi mdi-folder-multiple-image", "activated" => "mediaAct"),
//    "employees" => array("title" => "employees", "icon" => "mdi mdi-briefcase", "activated" => "employeesAct",
//        "children" => array(
//            "employees" => array("title" => "employees", "url" => URL::to('portal#/employees'), "role_perm" => array("employees.list", "employees.addEmployee", "employees.editEmployee", "employees.editEmployee")),
//            "teachers" => array("title" => "teachers", "url" => URL::to('portal#/teachers'), "role_perm" => array("teachers.list", "teachers.addTeacher", "teachers.EditTeacher", "teachers.delTeacher", "teachers.Approve", "teachers.teacLeaderBoard", "teachers.Import", "teachers.Export")),
//            "departments" => array("title" => "depart", "url" => URL::to("portal#/departments"), "role_perm" => array("depart.list", "depart.add_depart", "depart.edit_depart", "depart.del_depart")),
//            "designations" => array("title" => "desig", "url" => URL::to("portal#/designations"), "role_perm" => array("desig.list", "desig.add_desig", "desig.edit_desig", "desig.del_desig")),
//        )
//    ),
//    "students" => array("title" => "students", "icon" => "mdi mdi-account-multiple-outline",
//        "children" => array(
//            "students" => array("title" => "students", "url" => URL::to("portal#/students"), "role_perm" => array("students.list", "students.editStudent", "students.delStudent", "students.listGradStd", "students.Approve", "students.stdLeaderBoard", "students.Import", "students.Export", "students.Attendance", "students.Marksheet", "students.medHistory")),
//            "admission" => array("title" => "admission", "url" => URL::to("portal#/students/admission"), "role_perm" => array("students.admission")),
//            "student_categories" => array("title" => "std_cat", "url" => URL::to("portal#/student/categories"), "role_perm" => array("students.std_cat")),
//        )
//    ),
//    "parents" => array("title" => "parents", "url" => URL::to('portal#/parents'), "role_perm" => array("parents.list", "parents.AddParent", "parents.editParent", "parents.delParent", "parents.Approve", "parents.Import", "parents.Export"), "icon" => "mdi mdi-account-multiple"),
//    "studentsMarksheet" => array("title" => "Marksheet", "url" => URL::to('portal#/students/marksheet'), "role_perm" => array("Marksheet.Marksheet"), "icon" => "mdi mdi-grid"),
//    "gradelevels" => array("title" => "gradeLevels", "url" => URL::to('portal#/gradeLevels'), "role_perm" => array("gradeLevels.list", "gradeLevels.addLevel", "gradeLevels.editGrade", "gradeLevels.delGradeLevel"), "icon" => "mdi mdi-arrange-send-backward"),
//            "materials" => array("title" => "studyMaterial", "url" => URL::to('portal#/materials'), "role_perm" => array("studyMaterial.list", "studyMaterial.addMaterial", "studyMaterial.editMaterial", "studyMaterial.delMaterial", "studyMaterial.Download"), "icon" => "mdi mdi-cloud-download", "activated" => "materialsAct"),
//            "homework" => array("title" => "Homework", "url" => URL::to('portal#/homework'), "role_perm" => array("Homework.list", "Homework.View", "Homework.addHomework", "Homework.editHomework", "Homework.delHomework", "Homework.Download"), "icon" => "mdi mdi-book", "activated" => "homeworkAct"),
//            "assignments" => array("title" => "Assignments", "url" => URL::to('portal#/assignments'), "role_perm" => array("Assignments.list", "Assignments.Download", "Assignments.AddAssignments", "Assignments.editAssignment", "Assignments.delAssignment", "Assignments.viewAnswers", "Assignments.applyAssAnswer"), "icon" => "mdi mdi-file-pdf", "activated" => "assignmentsAct"),
//            "examslist" => array("title" => "examsList", "url" => URL::to('portal#/examsList'), "role_perm" => array("examsList.list", "examsList.addExam", "examsList.editExam", "examsList.delExam", "examsList.examDetailsNot", "examsList.showMarks", "examsList.controlMarksExam"), "icon" => "mdi mdi-playlist-check"),
//            "onlineexams" => array("title" => "onlineExams", "url" => URL::to('portal#/onlineExams'), "icon" => "mdi mdi-account-network", "role_perm" => array("onlineExams.list", "onlineExams.addExam", "onlineExams.editExam", "onlineExams.delExam", "onlineExams.takeExam", "onlineExams.showMarks", "onlineExams.QuestionsArch"), "activated" => "onlineexamsAct"),
//    "newsandevents" => array("title" => "News & Events", "icon" => "mdi mdi-bullhorn",
//        "children" => array(
//            "newsboard" => array("title" => "newsboard", "url" => URL::to('portal#/newsboard'), "role_perm" => array("newsboard.list", "newsboard.View", "newsboard.addNews", "newsboard.editNews", "newsboard.delNews"), "icon" => "mdi mdi-bullhorn", "activated" => "newsboardAct"),
//            "events" => array("title" => "events", "url" => URL::to('portal#/events'), "role_perm" => array("events.list", "events.View", "events.addEvent", "events.editEvent", "events.delEvent"), "icon" => "mdi mdi-calendar-clock", "activated" => "eventsAct"),
//        )
//    ),
//            "newsboard" => array("title" => "newsboard", "url" => URL::to('portal#/newsboard'), "role_perm" => array("newsboard.list", "newsboard.View", "newsboard.addNews", "newsboard.editNews", "newsboard.delNews"), "icon" => "mdi mdi-bullhorn", "activated" => "newsboardAct"),
//            "events" => array("title" => "events", "url" => URL::to('portal#/events'), "role_perm" => array("events.list", "events.View", "events.addEvent", "events.editEvent", "events.delEvent"), "icon" => "mdi mdi-calendar-clock", "activated" => "eventsAct"),
//    "mypayroll" => array("title" => "MyPayroll", "icon" => "mdi mdi-bank", "url" => URL::to('portal#/payroll/mine'), "role_perm" => array("Payroll.MyPayroll"), "activated" => "payrollAct"),
//    "payroll" => array("title" => "Payroll", "icon" => "mdi mdi-bank", "activated" => "payrollAct",
//        "children" => array(
//            "makePay" => array("title" => "makePayment", "url" => URL::to('portal#/payroll/dopayment'), "role_perm" => array('Payroll.makeUsrPayment')),
//            "usersSalary" => array("title" => "userSalary", "url" => URL::to('portal#/payroll/users_salary'), "role_perm" => array('Payroll.userSalary')),
//            "salaryBase" => array("title" => "salaryBase", "url" => URL::to('portal#/payroll/salary_base'), "role_perm" => array('Payroll.salaryBase')),
//            "hourlyBase" => array("title" => "hourSalary", "url" => URL::to('portal#/payroll/hourly_base'), "role_perm" => array('Payroll.hourSalary')),
//        )
//    ),
//    "accounting" => array("title" => "accounting", "icon" => "mdi mdi-currency-usd", "activated" => "paymentsAct",
//        "children" => array(
//            "Invoices" => array("title" => "Invoices", "url" => URL::to('portal#/invoices'), "role_perm" => array("Invoices.list", "Invoices.View", "Invoices.addPayment", "Invoices.editPayment", "Invoices.delPayment", "Invoices.collInvoice", "Invoices.payRevert", "Invoices.Export")),
//            "DueInvoices" => array("title" => "dueInvoices", "url" => URL::to('portal#/invoices/due'), "role_perm" => array("Invoices.dueInvoices")),
//            "controlFeeGroups" => array("title" => "FeeGroups", "url" => URL::to('portal#/feeGroup'), "role_perm" => array("FeeGroups.list", "FeeGroups.addFeeGroup", "FeeGroups.editFeeGroup", "FeeGroups.delFeeGroup")),
//            "controlFeeTypes" => array("title" => "FeeTypes", "url" => URL::to('portal#/feeType'), "role_perm" => array("FeeTypes.list", "FeeTypes.addFeeType", "FeeTypes.editFeeType", "FeeTypes.delFeeType")),
//            "controlFeeAllocation" => array("title" => "FeeAllocation", "url" => URL::to('portal#/feeAllocation'), "role_perm" => array("FeeAllocation.list", "FeeAllocation.addFeeAllocation", "FeeAllocation.editFeeAllocation", "FeeAllocation.delFeeAllocation")),
//            "feeDiscount" => array("title" => "FeeDiscount", "url" => URL::to('portal#/feeDiscount'), "role_perm" => array("FeeDiscount.list", "FeeDiscount.addFeeDiscount", "FeeDiscount.editFeeDiscount", "FeeDiscount.delFeeDiscount", "FeeDiscount.assignUser")),
//        )
//    ),
//    "expensesList" => array("title" => "Expenses", "icon" => "mdi mdi-currency-usd", "activated" => "expensesAct",
//        "children" => array(
//            "expenses" => array("title" => "Expenses", "url" => URL::to('portal#/expenses'), "role_perm" => array("Expenses.list", "Expenses.addExpense", "Expenses.editExpense", "Expenses.delExpense")),
//            "expensesCat" => array("title" => "expCategory", "url" => URL::to('portal#/expensesCat'), "role_perm" => array("Expenses.expCategory")),
//        )
//    ),
//    "incomeList" => array("title" => "Incomes", "icon" => "fa fa-money", "activated" => "incomeAct",
//        "children" => array(
//            "income" => array("title" => "Incomes", "url" => URL::to('portal#/incomes'), "role_perm" => array("Incomes.list", "Incomes.addIncome", "Incomes.editIncome", "Incomes.delIncome")),
//            "incomeCat" => array("title" => "incomeCategory", "url" => URL::to('portal#/incomesCat'), "role_perm" => array("Incomes.incomeCategory")),
//        )
//    ),
//    "inventory" => array("title" => "inventory", "icon" => "mdi mdi-sitemap", "activated" => "inventoryAct",
//        "children" => array(
//            "inv_issue" => array("title" => "iss_ret", "url" => URL::to("portal#/inv_issue"), "role_perm" => array("iss_ret.list", "iss_ret.issue_item", "iss_ret.edit_item", "iss_ret.del_item", "iss_ret.Download", "iss_ret.Export")),
//            "items_stock" => array("title" => "items_stock", "url" => URL::to("portal#/items_stock"), "role_perm" => array("items_stock.list", "items_stock.add_item", "items_stock.edit_item", "items_stock.del_item", "items_stock.Download", "items_stock.Export")),
//            "inv_cat" => array("title" => "inv_cat", "url" => URL::to("portal#/inv_cat"), "role_perm" => array("inv_cat.list", "inv_cat.add_cat", "inv_cat.edit_cat", "inv_cat.del_cat")),
//            "suppliers" => array("title" => "suppliers", "url" => URL::to("portal#/suppliers"), "role_perm" => array("suppliers.list", "suppliers.add_supp", "suppliers.edit_supp", "suppliers.del_supp")),
//            "stores" => array("title" => "stores", "url" => URL::to("portal#/stores"), "role_perm" => array("stores.list", "stores.add_store", "stores.edit_store", "stores.del_store")),
//            "items_code" => array("title" => "items_code", "url" => URL::to("portal#/items_code"), "role_perm" => array("items_code.list", "items_code.add_item", "items_code.edit_item", "items_code.del_item", "items_code.Export")),
//        )
//    ),
//    "classes" => array("title" => "classes", "icon" => "mdi mdi-sitemap",
//        "children" => array(
//            "classes" => array("title" => "classes", "url" => URL::to('portal#/classes'), "role_perm" => array("classes.list", "classes.addClass", "classes.editClass", "classes.delClass")),
//            "sections" => array("title" => "Sections", "url" => URL::to('portal#/sections'), "role_perm" => array("sections.list", "sections.addSection", "sections.editSection", "sections.delSection")),
//        )
//    ),
//    "subjects" => array("title" => "Subjects", "url" => URL::to('portal#/subjects'), "icon" => "mdi mdi-book-open-page-variant", "role_perm" => array("Subjects.list", "Subjects.addSubject", "Subjects.editSubject", "Subjects.delSubject")),
//    "hostel" => array("title" => "HostelManage", "icon" => "mdi mdi-home-map-marker", "activated" => "hostelAct",
//        "children" => array(
//            "controlHostel" => array("title" => "Hostel", "url" => URL::to('portal#/hostel'), "role_perm" => array("Hostel.list", "Hostel.AddHostel", "Hostel.EditHostel", "Hostel.delHostel", "Hostel.listSubs")),
//            "hostelCat" => array("title" => "HostelCat", "url" => URL::to('portal#/hostelCat'), "role_perm" => array("Hostel.HostelCat")),
//        )
//    ),
//    "Transportation" => array("title" => "Transportation", "icon" => "mdi mdi-bus", "activated" => "transportAct",
//        "children" => array(
//            "transportations" => array("title" => "Transportation", "url" => URL::to('portal#/transports'), "role_perm" => array("Transportation.list", "Transportation.addTransport", "Transportation.editTransport", "Transportation.delTrans")),
//            "transport_vehicles" => array("title" => "trans_vehicles", "url" => URL::to("portal#/transport_vehicles"), "role_perm" => array("trans_vehicles.list", "trans_vehicles.add_vehicle", "trans_vehicles.edit_vehicle", "trans_vehicles.del_vehicle")),
//            "members" => array("title" => "members", "url" => URL::to("portal#/transport_members"), "role_perm" => array("Transportation.members")),
//        )
//    ),
//    "certificates" => array("title" => "Certificates", "url" => URL::to("portal#/certificates"), "icon" => "mdi mdi-certificate", "role_perm" => array("Certificates.list", "Certificates.add_cert", "Certificates.edit_cert", "Certificates.del_cert", "Certificates.Download"), "activated" => "certAct"),
//    "id_cards" => array("title" => "id_cards", "url" => URL::to("portal#/id_cards"), "icon" => "mdi mdi-certificate", "role_perm" => array("id_cards.list", "id_cards.add_card", "id_cards.edit_card", "id_cards.del_card"), "activated" => "cardAct"),
//    "reports" => array("title" => "Reports", "url" => URL::to('portal#/reports'), "role_perm" => array("Reports.Reports"), "icon" => "mdi mdi-chart-areaspline", "activated" => "reportsAct"),
//            "frontEnd" => array("title" => "frontendCMS", "icon" => "mdi mdi-book-multiple",
//                "children" => array(
//                    "pages" => array("title" => "ctrlPages", "url" => URL::to('portal#/frontend/pages'), "role_perm" => array("frontendCMSpages.list", "frontendCMSpages.addPage", "frontendCMSpages.editPage", "frontendCMSpages.delPage")),
//                    "settings" => array("title" => "CMSSettings", "url" => URL::to('portal#/frontend/settings'), "role_perm" => array("adminTasks.frontendCMS")),
//                )
//            ),
//            "adminTasks" => array("title" => "adminTasks", "icon" => "mdi mdi-settings",
//                "children" => array(
//                    "academicyear" => array("title" => "academicyears", "url" => URL::to('portal#/academicYear'), "role_perm" => array("academicyears.list", "academicyears.addAcademicyear", "academicyears.editAcademicYears", "academicyears.delAcademicYears")),
//                    "promotion" => array("title" => "Promotion", "url" => URL::to('portal#/promotion'), "role_perm" => array("Promotion.promoteStudents")),
//                    "mailsmsTemplates" => array("title" => "mailsmsTemplates", "url" => URL::to('portal#/mailsmsTemplates'), "role_perm" => array('mailsms.mailsmsTemplates')),
//                    "polls" => array("title" => "Polls", "url" => URL::to('portal#/polls'), "role_perm" => array("Polls.list", "Polls.addPoll", "Polls.editPoll", "Polls.delPoll"), "activated" => "pollsAct"),
//                    "dormitories" => array("title" => "Dormitories", "url" => URL::to('portal#/dormitories'), "role_perm" => array("Dormitories.list", "Dormitories.addDormitories", "Dormitories.editDorm", "Dormitories.delDorm")),
//                    "siteSettings" => array("title" => "generalSettings", "url" => URL::to('portal#/settings'), "role_perm" => array("adminTasks.globalSettings", "adminTasks.activatedModules", "adminTasks.paymentsSettings", "adminTasks.mailSmsSettings", "adminTasks.vacationSettings", "adminTasks.mobileSettings", "adminTasks.frontendCMS", "adminTasks.bioItegration", "adminTasks.lookFeel")),
//                    "languages" => array("title" => "Languages", "url" => URL::to('portal#/languages'), "role_perm" => array("Languages.list", "Languages.addLanguage", "Languages.editLanguage", "Languages.delLanguage")),
//                    "admins" => array("title" => "Administrators", "url" => URL::to('portal#/admins'), "role_perm" => array("Administrators.list", "Administrators.addAdministrator", "Administrators.editAdministrator", "Administrators.delAdministrator")),
//                    "roles" => array("title" => "roles", "url" => URL::to('portal#/roles'), "role_perm" => array("roles.list", "roles.add_role", "roles.modify_role", "roles.delete_role")),
//                    "terms" => array("title" => "schoolTerms", "url" => URL::to('portal#/terms'), "role_perm" => array("adminTasks.globalSettings")),
//                    "exportimport" => array("title" => "dbExport", "url" => URL::to('portal#/dbexports'), "role_perm" => array("dbExport.dbExport")),
//                )
//            )
//);
