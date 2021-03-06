<?php
// LDAP plugin
$string['auth_ldap_ad_create_req'] = 'Cannot create the new account in Active Directory. Make sure you meet all the requirements for this to work (LDAPS connection, bind user with adequate rights, etc.)';
$string['auth_ldap_attrcreators'] = 'List of groups or contexts whose members are allowed to create attributes. Separate multiple groups with \';\'. Usually something like \'cn=teachers,ou=staff,o=myorg\'';
$string['auth_ldap_attrcreators_key'] = 'Attribute creators';
$string['auth_ldap_bind_dn'] = 'If you want to use bind-user to search users, specify it here. Something like \'cn=ldapuser,ou=public,o=org\'';
$string['auth_ldap_bind_pw'] = 'Password for bind-user.';
$string['auth_ldap_bind_settings'] = 'Bind settings';
$string['auth_ldap_contexts'] = 'List of contexts where users are located. Separate different contexts with \';\'. For example: \'ou=users,o=org; ou=others,o=org\'';
$string['auth_ldap_create_context'] = 'If you enable user creation with email confirmation, specify the context where users are created. This context should be different from other users to prevent security issues. You don\'t need to add this context to ldap_context-variable, Moodle will search for users from this context automatically.<br /><b>Note!</b> You have to modify the method user_create() in file auth/ldap/auth.php to make user creation work';
$string['auth_ldap_create_error'] = 'Error creating user in LDAP.';
$string['auth_ldap_creators'] = 'List of groups or contexts whose members are allowed to create new courses. Separate multiple groups with \';\'. Usually something like \'cn=teachers,ou=staff,o=myorg\'';
$string['auth_ldap_expiration_desc'] = 'Select No to disable expired password checking or LDAP to read passwordexpiration time directly from LDAP';
$string['auth_ldap_expiration_warning_desc'] = 'Number of days before password expiration warning is issued.';
$string['auth_ldap_expireattr_desc'] = 'Optional: overrides ldap-attribute that stores password expiration time';
$string['auth_ldap_graceattr_desc'] = 'Optional: Overrides  gracelogin attribute';
$string['auth_ldap_gracelogins_desc'] = 'Enable LDAP gracelogin support. After password has expired user can login until gracelogin count is 0. Enabling this setting displays grace login message if password is expired.';
$string['auth_ldap_groupecreators'] = 'List of groups or contexts whose members are allowed to create groups. Separate multiple groups with \';\'. Usually something like \'cn=teachers,ou=staff,o=myorg\'';
$string['auth_ldap_groupecreators_key'] = 'Group creators';
$string['auth_ldap_host_url'] = 'Specify LDAP host in URL-form like \'ldap://ldap.myorg.com/\' or \'ldaps://ldap.myorg.com/\' Separate multipleservers with \';\' to get failover support.';
$string['auth_ldap_ldap_encoding'] = 'Specify encoding used by LDAP server. Most probably utf-8, MS AD v2 uses default platform encoding such as cp1252, cp1250, etc.';
$string['auth_ldap_login_settings'] = 'Login settings';
$string['auth_ldap_memberattribute'] = 'Optional: Overrides user member attribute, when users belongs to a group. Usually \'member\'';
$string['auth_ldap_memberattribute_isdn'] = 'Optional: Overrides handling of member attribute values, either 0 or 1';
$string['auth_ldap_no_mbstring'] = 'You need the mbstring extension to create users in Active Directory.';
$string['auth_ldap_objectclass'] = 'Optional: Overrides objectClass used to name/search users on ldap_user_type. Usually you dont need to chage this.';
$string['auth_ldap_opt_deref'] = 'Determines how aliases are handled during search. Select one of the following values: \"No\" (LDAP_DEREF_NEVER) or \"Yes\" (LDAP_DEREF_ALWAYS)';
$string['auth_ldap_passtype'] = 'Specify the format of new or changed passwords in LDAP server.';
$string['auth_ldap_passwdexpire_settings'] = 'LDAP password expiration settings.';
$string['auth_ldap_preventpassindb'] = 'Select yes to prevent passwords from being stored in Moodle\'s DB.';
$string['auth_ldap_search_sub'] = 'Search users from subcontexts.';
$string['auth_ldap_server_settings'] = 'LDAP server settings';
$string['auth_ldap_update_userinfo'] = 'Update user information (firstname, lastname, address..) from LDAP to Moodle.  Specify \"Data mapping\" settings as you need.';
$string['auth_ldap_user_exists'] = 'LDAP username already exists.';
$string['auth_ldap_user_attribute'] = 'Optional: Overrides the attribute used to name/search users. Usually \'cn\'.';
$string['auth_ldap_user_settings'] = 'User lookup settings';
$string['auth_ldap_user_type'] = 'Select how users are stored in LDAP. This setting also specifies how login expiration, grace logins and user creation will work.';
$string['auth_ldap_version'] = 'The version of the LDAP protocol your server is using.';
$string['auth_ldapdescription'] = 'This method provides authentication against an external LDAP server.

                                  If the given username and password are valid, Moodle creates a new user

                                  entry in its database. This module can read user attributes from LDAP and prefill

                                  wanted fields in Moodle.  For following logins only the username and

                                  password are checked.';
$string['auth_ldap_ldap_encoding_key'] = 'LDAP encoding';
$string['auth_ldap_host_url_key'] = 'Host URL';
$string['auth_ldap_version_key'] = 'Version';
$string['auth_ldap_preventpassindb_key'] = 'Hide passwords';
$string['auth_ldap_bind_dn_key'] = 'Distinguished Name';
$string['auth_ldap_bind_pw_key'] = 'Password';
$string['auth_ldap_user_type_key'] = 'User type';
$string['auth_ldap_contexts_key'] = 'Contexts';
$string['auth_ldap_search_sub_key'] = 'Search subcontexts';
$string['auth_ldap_opt_deref_key'] = 'Dereference aliases';
$string['auth_ldap_user_attribute_key'] = 'User attribute';
$string['auth_ldap_memberattribute_key'] = 'Member attribute';
$string['auth_ldap_memberattribute_isdn_key'] = 'Member attribute uses dn';
$string['auth_ldap_objectclass_key'] = 'Object class';
$string['auth_ldap_passtype_key'] = 'Password format';
$string['auth_ldap_changepasswordurl_key'] = 'Password-change URL';
$string['auth_ldap_expiration_key'] = 'Expiration';
$string['auth_ldap_expiration_warning_key'] = 'Expiration warning';
$string['auth_ldap_expireattr_key'] = 'Expiration attribute';
$string['auth_ldap_gracelogins_key'] = 'Grace logins';
$string['auth_ldap_gracelogin_key'] = 'Grace login attribute';
$string['auth_ldap_auth_user_create_key'] = 'Create users externally';
$string['auth_ldap_create_context_key'] = 'Context for new users';
$string['auth_ldap_creators_key'] = 'Creators';
$string['auth_ldap_noconnect'] = 'LDAP-module cannot connect to server: $a';
$string['auth_ldap_noconnect_all'] = 'LDAP-module cannot connect to any servers: $a';
$string['auth_ldap_unsupportedusertype'] = 'auth: ldap user_create() does not support selected usertype: $a (..yet)';
$string['auth_ldap_usertypeundefined'] = 'config.user_type not defined or function ldap_expirationtime2unix does not support selected type!';
$string['auth_ldap_usertypeundefined2'] = 'config.user_type not defined or function ldap_unixi2expirationtime does not support selected type!';
$string['auth_ldap_noextension'] = 'Warning: The PHP LDAP module does not seem to be present. Please ensure it is installed and enabled.';

$string['auth_ldapextrafields'] = 'These fields are optional.  You can choose to pre-fill some Moodle user fields with information from the <b>LDAP fields</b> that you specify here. <p>If you leave these fields blank, then nothing will be transferred from LDAP and Moodle defaults will be used instead.</p><p>In either case, the user will be able to edit all of these fields after they log in.</p>';
$string['auth_ldaptitle'] = 'LDAP server';
$string['auth_ldapnotinstalled'] = 'Cannot use LDAP authentication. The PHP LDAP module is not installed.';
$string['auth_ntlmsso'] = 'NTLM SSO';
$string['auth_ntlmsso_enabled_key'] = 'Enable';
$string['auth_ntlmsso_enabled'] = 'Set to yes to attempt Single Sign On with the NTLM domain. <strong>Note:</strong> this requires additional setup on the webserver to work, see <a href=\"http://docs.moodle.org/en/NTLM_authentication\">http://docs.moodle.org/en/NTLM_authentication</a>';
$string['auth_ntlmsso_ie_fastpath'] = 'Set to yes to enable the NTLM SSO fast path (bypasses certain steps and only works if the client\'s browser is MS Internet Explorer).';
$string['auth_ntlmsso_ie_fastpath_key'] = 'MS IE fast path?';
$string['auth_ntlmsso_subnet_key'] = 'Subnet';
$string['auth_ntlmsso_subnet'] = 'If set, it will only attempt SSO with clients in this subnet. Format: xxx.xxx.xxx.xxx/bitmask';
$string['ntlmsso_attempting'] = 'Attempting Single Sign On via NTLM...';
$string['ntlmsso_failed'] = 'Auto-login failed, try the normal login page...';
$string['ntlmsso_isdisabled'] = 'NTLM SSO is disabled.';

?>