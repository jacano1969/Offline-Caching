<?php
/**
 * Created on 05/03/2008
 *
 * users webservice api
 *
 * @author Jerome Mouneyrac
 */
require_once(dirname(dirname(__FILE__)) . '/lib/moodlewsapi.php');
require_once(dirname(dirname(__FILE__)) . '/user/api.php');

/**
 * WORK IN PROGRESS, DO NOT USE IT
 */
final class user_ws_api extends moodle_ws_api {

    /**
     * Constructor - We set the description of this API in order to be access by Web service
     */
    function __construct () {
          $this->descriptions = array();
       ///The desciption of the web service
       ///
       ///'wsparams' and 'return' are used to described the web services to the end user (can build WSDL file from these information)
       ///'paramorder' is used internally by developers implementing a new protocol. It contains the params of the called function in a good order and with default value
       ///
       ///Note: web services param names have not importance. However 'paramorder' must match the function params order.
       ///And all web services param names defined into 'wsparams' should be included into 'paramorder' (otherwise they will not be used)
       ///
       ///How to define an object/array attribut web service parameter: 'any object/array name' + ':' + 'attribut/key name'. 'attribut/key name' must match the real attribut name.
       ///e.g: a function has a parameter that is an object with a attribut named 'username'. You will need to declare 'anyobjectname:username' into 'wsparams'.
       ///     Then 'paramorder'=> array('anyobjectname' => array('username' => ...));
       ///
       ///TODO: manage object->object parameter
          $this->descriptions['tmp_get_users']   = array( 'wsparams' => array('search'=> PARAM_ALPHA),
                                                      'return' => array('user', array('id' => PARAM_RAW, 'auth' => PARAM_RAW, 'confirmed' => PARAM_RAW, 'username' => PARAM_RAW, 'idnumber' => PARAM_RAW,
                                                                                    'firstname' => PARAM_RAW, 'lastname' => PARAM_RAW, 'email' => PARAM_RAW, 'emailstop' => PARAM_RAW,
                                                                                    'lang' => PARAM_RAW, 'theme' => PARAM_RAW, 'timezone' => PARAM_RAW, 'mailformat' => PARAM_RAW)));

          $this->descriptions['tmp_create_user'] = array( 'wsparams' => array('username'=> PARAM_RAW, 'firstname'=> PARAM_RAW, 'lastname'=> PARAM_RAW, 'email'=> PARAM_RAW, 'password'=> PARAM_RAW),
                                                      'return' => array('userid', PARAM_RAW));


          $this->descriptions['tmp_namedparams_get_users']   = array( 'wsparams' => array('search'=> PARAM_RAW),
                                                      'return' => array('user', array('id' => PARAM_RAW, 'auth' => PARAM_RAW, 'confirmed' => PARAM_RAW, 'username' => PARAM_RAW, 'idnumber' => PARAM_RAW,
                                                                                    'firstname' => PARAM_RAW, 'lastname' => PARAM_RAW, 'email' => PARAM_RAW, 'emailstop' => PARAM_RAW,
                                                                                    'lang' => PARAM_RAW, 'theme' => PARAM_RAW, 'timezone' => PARAM_RAW, 'mailformat' => PARAM_RAW)));
    }

    /**
     *
     * @param <type> $search
     * @return <type>
     */
    static function tmp_get_users($search) {
        return user_api::tmp_get_users( true, $search, false, null, 'firstname ASC','', '', '', '',
                                        'id, auth, confirmed, username, idnumber, firstname, lastname, email, emailstop, lang, theme, timezone, mailformat');
    }

    /**
     *
     * @param <type> $username
     * @param <type> $firstname
     * @param <type> $lastname
     * @param <type> $email
     * @param <type> $password
     * @return <type> 
     */
    static function tmp_create_user($username, $firstname, $lastname, $email, $password) {
        $user = array();
        $user['username'] = $username;
        $user['firstname'] = $firstname;
        $user['lastname'] = $lastname;
        $user['email'] = $email;
        $user['password'] = $password;
        return user_api::tmp_create_user($user);
    
    }

}

?>