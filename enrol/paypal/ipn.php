<?php  // $Id$

/**
* Listens for Instant Payment Notification from PayPal
*
* This script waits for Payment notification from PayPal,
* then double checks that data by sending it back to PayPal.
* If PayPal verifies this then it sets up the enrolment for that
*
* Set the $user->timeaccess course array
*
* @param    user  referenced object, must contain $user->id already set
*/


    require("../../config.php");
    require("enrol.php");
    require_once($CFG->libdir.'/eventslib.php');

/// Keep out casual intruders
    if (empty($_POST) or !empty($_GET)) {
        print_error("Sorry, you can not use the script that way.");
    }

/// Read all the data from PayPal and get it ready for later;
/// we expect only valid UTF-8 encoding, it is the responsibility
/// of user to set it up properly in PayPal business acount,
/// it is documented in docs wiki.

    $req = 'cmd=_notify-validate';

    $data = new object();

    foreach ($_POST as $key => $value) {
        $req .= "&$key=".urlencode($value);
        $data->$key = $value;
    }

    $custom = explode('-', $data->custom);
    $data->userid           = (int)$custom[0];
    $data->courseid         = (int)$custom[1];
    $data->payment_gross    = $data->mc_gross;
    $data->payment_currency = $data->mc_currency;
    $data->timeupdated      = time();


/// get the user and course records

    if (! $user = $DB->get_record("user", array("id"=>$data->userid))) {
        message_paypal_error_to_admin("Not a valid user id", $data);
        die;
    }

    if (! $course = $DB->get_record("course", array("id"=>$data->courseid))) {
        message_paypal_error_to_admin("Not a valid course id", $data);
        die;
    }

    if (! $context = get_context_instance(CONTEXT_COURSE, $course->id)) {
        message_paypal_error_to_admin("Not a valid context id", $data);
        die;
    }

/// Open a connection back to PayPal to validate the data

    $header = '';
    $header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
    $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
    $paypaladdr = empty($CFG->usepaypalsandbox) ? 'www.paypal.com' : 'www.sandbox.paypal.com';
    $fp = fsockopen ($paypaladdr, 80, $errno, $errstr, 30);

    if (!$fp) {  /// Could not open a socket to PayPal - FAIL
        echo "<p>Error: could not access paypal.com</p>";
        message_paypal_error_to_admin("Could not access paypal.com to verify payment", $data);
        die;
    }

/// Connection is OK, so now we post the data to validate it

    fputs ($fp, $header.$req);

/// Now read the response and check if everything is OK.

    while (!feof($fp)) {
        $result = fgets($fp, 1024);
        if (strcmp($result, "VERIFIED") == 0) {          // VALID PAYMENT!


            // check the payment_status and payment_reason

            // If status is not completed or pending then unenrol the student if already enrolled
            // and notify admin

            if ($data->payment_status != "Completed" and $data->payment_status != "Pending") {
                role_unassign(0, $data->userid, 0, $context->id);
                message_paypal_error_to_admin("Status not completed or pending. User unenrolled from course", $data);
                die;
            }

            // If status is pending and reason is other than echeck then we are on hold until further notice
            // Email user to let them know. Email admin.

            if ($data->payment_status == "Pending" and $data->pending_reason != "echeck") {
                $eventdata = new object();
                $eventdata->modulename        = 'moodle';
                $eventdata->userfrom          = get_admin();
                $eventdata->userto            = $user;
                $eventdata->subject           = "Moodle: PayPal payment";
                $eventdata->fullmessage       = "Your PayPal payment is pending.";
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml   = '';
                $eventdata->smallmessage      = '';
                events_trigger('message_send', $eventdata);

                message_paypal_error_to_admin("Payment pending", $data);
                die;
            }

            // If our status is not completed or not pending on an echeck clearance then ignore and die
            // This check is redundant at present but may be useful if paypal extend the return codes in the future

            if (! ( $data->payment_status == "Completed" or
                   ($data->payment_status == "Pending" and $data->pending_reason == "echeck") ) ) {
                die;
            }

            // At this point we only proceed with a status of completed or pending with a reason of echeck



            if ($existing = $DB->get_record("enrol_paypal", array("txn_id"=>$data->txn_id))) {   // Make sure this transaction doesn't exist already
                message_paypal_error_to_admin("Transaction $data->txn_id is being repeated!", $data);
                die;

            }

            if ($data->business != $CFG->enrol_paypalbusiness) {   // Check that the email is the one we want it to be
                message_paypal_error_to_admin("Business email is $data->business (not $CFG->enrol_paypalbusiness)", $data);
                die;

            }

            if (!$user = $DB->get_record('user', array('id'=>$data->userid))) {   // Check that user exists
                message_paypal_error_to_admin("User $data->userid doesn't exist", $data);
                die;
            }

            if (!$course = $DB->get_record('course', array('id'=>$data->courseid))) { // Check that course exists
                message_paypal_error_to_admin("Course $data->courseid doesn't exist", $data);;
                die;
            }

            // Check that amount paid is the correct amount
            if ( (float) $course->cost < 0 ) {
                $cost = (float) $CFG->enrol_cost;
            } else {
                $cost = (float) $course->cost;
            }

            if ($data->payment_gross < $cost) {
                $cost = format_float($cost, 2);
                message_paypal_error_to_admin("Amount paid is not enough ($data->payment_gross < $cost))", $data);
                die;

            }

            // ALL CLEAR !

            $DB->insert_record("enrol_paypal", $data);

            if (!enrol_into_course($course, $user, 'paypal')) {
                message_paypal_error_to_admin("Error while trying to enrol ".fullname($user)." in '$course->fullname'", $data);
                die;
            } else {
                $teacher = get_teacher($course->id);

                if (!empty($CFG->enrol_mailstudents)) {
                    $a->coursename = $course->fullname;
                    $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";
                    
                    $eventdata = new object();
                    $eventdata->modulename        = 'moodle';
                    $eventdata->userfrom          = $teacher;
                    $eventdata->userto            = $user;
                    $eventdata->subject           = get_string("enrolmentnew", '', $course->shortname);
                    $eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                    $eventdata->fullmessagehtml   = '';
                    $eventdata->smallmessage      = '';
                    events_trigger('message_send', $eventdata);
                    
                }

                if (!empty($CFG->enrol_mailteachers)) {
                    $a->course = $course->fullname;
                    $a->user = fullname($user);
                    
                    $eventdata = new object();
                    $eventdata->modulename        = 'moodle';
                    $eventdata->userfrom          = $user;
                    $eventdata->userto            = $teacher;
                    $eventdata->subject           = get_string("enrolmentnew", '', $course->shortname);
                    $eventdata->fullmessage       = get_string('enrolmentnewuser', '', $a);
                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                    $eventdata->fullmessagehtml   = '';
                    $eventdata->smallmessage      = '';			    
                    events_trigger('message_send', $eventdata); 
                }

                if (!empty($CFG->enrol_mailadmins)) {
                    $a->course = $course->fullname;
                    $a->user = fullname($user);
                    $admins = get_admins();
                    foreach ($admins as $admin) {                        
                        $eventdata = new object();
                        $eventdata->modulename        = 'moodle';
                        $eventdata->userfrom          = $user;
                        $eventdata->userto            = $admin;
                        $eventdata->subject           = get_string("enrolmentnew", '', $course->shortname);
                        $eventdata->fullmessage       = get_string('enrolmentnewuser', '', $a);
                        $eventdata->fullmessageformat = FORMAT_PLAIN;
                        $eventdata->fullmessagehtml   = '';
                        $eventdata->smallmessage      = '';
                        events_trigger('message_send', $eventdata);
                    }
                }

            }


        } else if (strcmp ($result, "INVALID") == 0) { // ERROR
            $DB->insert_record("enrol_paypal", $data, false);
            message_paypal_error_to_admin("Received an invalid payment notification!! (Fake payment?)", $data);
        }
    }

    fclose($fp);
    exit;



/// FUNCTIONS //////////////////////////////////////////////////////////////////


function message_paypal_error_to_admin($subject, $data) {
    $admin = get_admin();
    $site = get_site();

    $message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";

    foreach ($data as $key => $value) {
        $message .= "$key => $value\n";
    }

    $eventdata = new object();
    $eventdata->modulename        = 'moodle';
    $eventdata->userfrom          = $admin;
    $eventdata->userto            = $admin;
    $eventdata->subject           = "PAYPAL ERROR: ".$subject;
    $eventdata->fullmessage       = $message;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';
    $eventdata->smallmessage      = '';
    events_trigger('message_send', $eventdata);
}

?>
