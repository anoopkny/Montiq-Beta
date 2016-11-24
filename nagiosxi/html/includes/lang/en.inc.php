<?php
// English (U.S.) language file
// $Id$

global $lstr;

include_once("en-perfgraphs.inc.php");

$lstr['language_translation_complete'] = true;

///////////////////////////////////////////////////////////////
// PAGE TITLES
///////////////////////////////////////////////////////////////
$lstr['MainPageTitle'] = "";
$lstr['MissingPageTitle'] = _("Missing Page");
$lstr['MissingFeaturePageTitle'] = _("Unimplemented Feature");
$lstr['LoginPageTitle'] = _("Login");
$lstr['ResetPasswordPageTitle'] = _("Reset Password");
$lstr['PasswordSentPageTitle'] = _("Password Sent");
$lstr['InstallPageTitle'] = _("Install");
$lstr['InstallErrorPageTitle'] = _("Error");


///////////////////////////////////////////////////////////////
// PAGE HEADERS (H1 TAGS)
///////////////////////////////////////////////////////////////
$lstr['MissingPageHeader'] = _("What the...");
$lstr['MissingFeaturePageHeader'] = _("Wouldn't that be nice...");
$lstr['ForcedPasswordChangePageHeader'] = _("Password Change Required");
$lstr['ResetPasswordPageHeader'] = _("Reset Password");
$lstr['MainPageHeader'] = _("Nagios Reports&trade;");
$lstr['LoginPageHeader'] = _("Login");
$lstr['PasswordSentPageHeader'] = _("Password Sent");
$lstr['CreditsPageHeader'] = _("Credits");
$lstr['LegalInfoPageHeader'] = _("Legal Information");


///////////////////////////////////////////////////////////////
// H2 TAGS
///////////////////////////////////////////////////////////////
$lstr['FeedbackSendingHeader'] = _("Sending Feedback...");
$lstr['FeedbackSuccessHeader'] = _("Thank You!");
$lstr['FeedbackErrorHeader'] = _("Error");

///////////////////////////////////////////////////////////////
// MENU ITEMS
///////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////
// SUBMENU ITEMS
///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////
// FORM LEGENDS
///////////////////////////////////////////////////////////////
$lstr['UpdateUserPrefsFormLegend'] = _("Account Preferences");
$lstr['GeneralOptionsFormLegend'] = _("General Options");
$lstr['UserAccountInfoFormLegend'] = _("User Account Information");
$lstr['LoginPageLegend'] = _("Login");


///////////////////////////////////////////////////////////////
// FORM/PAGE SECTION TITLES
///////////////////////////////////////////////////////////////
$lstr['GeneralProgramSettingsSectionTitle'] = _("General Program Settings");
$lstr['DefaultUserSettingsSectionTitle'] = _("Default User Settings");
$lstr["AdvancedProgramSettingsSectionTitle"] = _("Advanced Settings");


///////////////////////////////////////////////////////////////
// BUTTONS
///////////////////////////////////////////////////////////////
$lstr['LoginButton'] = _("Login");
$lstr['ResetPasswordButton'] = _("Reset Password");
$lstr['UpgradeButton'] = _("Upgrade");
$lstr['InstallButton'] = _("Install");
$lstr['ChangePasswordButton'] = _("Change Password");
$lstr['UpdateButton'] = _("Update");
$lstr['UpdateSettingsButton'] = _("Update Settings");
$lstr['CancelButton'] = _("Cancel");
$lstr['ContinueButton'] = _("Continue");
$lstr['OkButton'] = _("Ok");
$lstr['AddUserButton'] = _("Add User");
$lstr['UpdateUserButton'] = _("Update User");
$lstr['SubmitButton'] = _("Submit");
$lstr['GoButton'] = _("Go");
$lstr['UpdatePermsButton'] = _("Update Permissions");
$lstr['UpdateDataSourceButton'] = _("Update Settings");
$lstr['UploadFileButton'] = _("Upload File");
$lstr['UploadPluginButton'] = _("Upload Plugin");
$lstr['CheckForUpdatesButton'] = _("Check For Updates Now");


///////////////////////////////////////////////////////////////
// INPUT TEXT TITLE
///////////////////////////////////////////////////////////////
$lstr['UsernameBoxTitle'] = _("Username");
$lstr['Password1BoxTitle'] = _("Password");
$lstr['NewPassword1BoxTitle'] = _("New Password");
$lstr['NewPassword2BoxTitle'] = _("Repeat New Password");
$lstr['Password2BoxTitle'] = _("Repeat Password");
$lstr['AdminEmailBoxText'] = _("Admin Email Address");
$lstr['EmailBoxTitle'] = _("Email Address");
$lstr['DefaultLanguageBoxTitle'] = _("Language");
$lstr['DefaultThemeBoxTitle'] = _("Theme");
$lstr['NameBoxTitle'] = _("Name");
$lstr['AuthorizationLevelBoxTitle'] = _("Authorization Level");
$lstr['ForcePasswordChangeNextLoginBoxTitle'] = _("Force Password Change at Next Login");
$lstr['SendAccountInfoEmailBoxTitle'] = _("Email User Account Information");
$lstr['SendAccountPasswordEmailBoxTitle'] = _("Email User New Password");
$lstr['DefaultDateFormatBoxTitle'] = _("Date Format");
$lstr['DefaultNumberFormatBoxTitle'] = _("Number Format");
$lstr['FeedbackCommentBoxText'] = _("Comments");
$lstr['FeedbackNameBoxTitle'] = _("Your Name (Optional)");
$lstr['FeedbackEmailBoxTitle'] = _("Your Email Address (Optional)");

///////////////////////////////////////////////////////////////
// ERROR MESSAGES
///////////////////////////////////////////////////////////////
$lstr['InvalidUsernamePasswordError'] = _("Invalid username or password.");
$lstr['NoUsernameError'] = _("No username specified.");
$lstr['NoMatchingAccountError'] = _("No account was found by that name.");
$lstr['UnableAccountEmailError'] = _("Unable to get account email address.");
$lstr['UnableAdminEmailError'] = _("Unable to get admin email address.");
$lstr['InvalidEmailAddressError'] = _("Email address is invalid.");
$lstr['BlankUsernameError'] = _("Username is blank.");
$lstr['BlankEmailError'] = _("Email address is blank.");
$lstr['InvalidEmailError'] = _("Email address is invalid.");
$lstr['BlankPasswordError'] = _("Password is blank.");
$lstr['BlankSecurityLevelError'] = _("Security level is blank.");
$lstr['AccountNameCollisionError'] = _("An account with that username already exists.");
$lstr['AddAccountFailedError'] = _("Failed to add account");
$lstr['AddAccountPrivilegesFailedError'] = _("Unable to assign account privileges.");
$lstr['BlankURLError'] = _("URL is blank.");
$lstr['MismatchedPasswordError'] = _("Passwords do not match.");
$lstr['BlankDefaultLanguageError'] = _("Default language not specified.");
$lstr['BlankDefaultThemeError'] = _("Default theme not specified.");
$lstr['BlankNameError'] = _("Name is blank.");
$lstr['InvalidURLError'] = _("Invalid URL.");
$lstr['BadUserAccountError'] = _("User account was not found.");
$lstr['BlankAuthLevelError'] = _("Blank authorization level.");
$lstr['InvalidAuthLevelError'] = _("Invalid authorization level.");
$lstr['BlankUserAccountError'] = _("User account was not specified.");
$lstr['CannotDeleteOwnAccountError'] = _("You cannot delete your own account.");
$lstr['NoUserAccountSelectedError'] = _("No account selected.");
$lstr['InvalidUserAccountError'] = _("Invalid account.");
$lstr["NoAdminNameError"] = _("No admin name specified.");
$lstr["NoAdminEmailError"] = _("No admin email address specified.");
$lstr["InvalidAdminEmailError"] = _("Admin email address is invalid.");

///////////////////////////////////////////////////////////////
// SHORT LINK TEXT
///////////////////////////////////////////////////////////////
$lstr['LegalLinkText'] = _("Legal Info");
$lstr['CreditsLinkText'] = _("Credits");
$lstr['AboutLinkText'] = _("About");
$lstr['PrivacyPolicyLinkText'] = _("Privacy Policy");
$lstr['CheckForUpdatesLinkText'] = _("Check for Updates");

$lstr['FirstPageText'] = _("First Page");
$lstr['LastPageText'] = _("Last Page");
$lstr['NextPageText'] = _("Next Page");
$lstr['PreviousPageText'] = _("Previous Page");
$lstr['PageText'] = _("Page");


///////////////////////////////////////////////////////////////
// TABLE HEADERS
///////////////////////////////////////////////////////////////
$lstr['UsernameTableHeader'] = _("Username");
$lstr['NameTableHeader'] = _("Name");
$lstr['EmailTableHeader'] = _("Email");
$lstr['ActionsTableHeader'] = _("Actions");
$lstr['DateTableHeader'] = _("Date");
$lstr['ResultTableHeader'] = _("Result");
$lstr['FileTableHeader'] = _("File");
$lstr['OutputTableHeader'] = _("Output");
$lstr['SnapshotResultTableHeader'] = _("Snapshot Result");
$lstr['VersionTableHeader'] = _("Version");
$lstr['StatusTableHeader'] = _("Status");


///////////////////////////////////////////////////////////////
// SHORT TEXT
///////////////////////////////////////////////////////////////
$lstr['MissingPageText'] = _("The page that went missing was: ");
$lstr['MissingFeatureText'] = _("We're currently working on this feature.  Until it's completed, you can't have it!  Seriously though - just sit tight for a while and we'll get it done.");
$lstr['LoginText'] = _("Login");
$lstr['LogoutText'] = _("Logout");
$lstr['ForgotPasswordText'] = _("Forgot your password?");
$lstr['LoggedOutText'] = _("You have logged out.");
$lstr['TryInstallAgainText'] = _("Try Again");
$lstr['UsernameText'] = _("Username");
$lstr['PasswordText'] = _("Password");
$lstr['AdminPasswordText'] = _("Administrator Password");
$lstr['ErrorText'] = _("Error");
$lstr['QueryText'] = _("Query");
$lstr['LanguageText'] = _("Language");
$lstr['ThemeText'] = _("Theme");
$lstr['LoggedInAsText'] = _("Logged in as");
$lstr['MenuText'] = _("Menu");
$lstr['UserPrefsUpdatedText'] = _("Settings Updated.");
$lstr['YesText'] = _("Yes");
$lstr['NoText'] = _("No");
$lstr['GeneralOptionsUpdatedText'] = _("Options Updated.");
$lstr['UserUpdatedText'] = _("User Updated.");
$lstr['UserAddedText'] = _("User Added.");
$lstr['UserDeletedText'] = _("User Deleted.");
$lstr['UsersDeletedText'] = _("Users Deleted.");
$lstr['AddNewUserText'] = _("Add New User");
$lstr['SessionTimedOut'] = _("Your session has timed out.");
$lstr['SearchBoxText'] = _("Search...");
$lstr['WithSelectedText'] = _("With Selected:");
$lstr['CheckForUpdateNowText'] = _("Check Now");
$lstr['YourVersionIsUpToDateText'] = _("Your version is up to date.");
$lstr['AnUpdateIsAvailableText'] = _("An update is available.");
$lstr['NewVersionInformationText'] = _("New version information");
$lstr['CurrentVersionInformationText'] = _("Your current version");
$lstr['NoticesText'] = _("Notices");
$lstr['AdminLevelText'] = _("Admin");
$lstr['UserLevelText'] = _("User");
$lstr['ContinueText'] = _("Continue");
$lstr['CancelText'] = _("Cancel");
$lstr['PerPageText'] = _("Per Page");

$lstr['NeverText'] = _("N/A");
$lstr['NotApplicableText'] = _("N/A");


///////////////////////////////////////////////////////////////
// PARTING/SUBSTRING TEXT
///////////////////////////////////////////////////////////////
$lstr['TotalRecordsSubText'] = _("total records");
$lstr['TotalMatchesForSubText'] = _("total matches for");
$lstr['ShowingSubText'] = _("Showing");
$lstr['OfSubText'] = _("of");
$lstr['YourAreRunningVersionText'] = _("You are currently running");
$lstr['WasReleasedOnText'] = _("was released on");


///////////////////////////////////////////////////////////////
// LONGER TEXT/NOTES
///////////////////////////////////////////////////////////////
$lstr['MissingPageNote'] = _("The page you requested seems to be missing.  It is theoretically possible - though highly unlikely - that we are to blame for this.  It is far more likely that something is wrong with the Universe.  Run for it!");
$lstr['ResetPasswordNote'] = _("Enter your username to have your password reset and emailed to you.");
$lstr['PasswordSentNote'] = _("Your account password has been reset and emailed to you.");
$lstr['AlreadyInstalledNote'] = _("Nagios Reports is already installed and up-to-date.");
$lstr['UpgradeRequiredNote'] = _("Your installation requires an upgrade.  Click the button below to begin.");
$lstr['UpgradeErrorNote'] = _("One or more errors were encountered:");
$lstr['InstallRequiredNote'] = _("Nagios Reports has not yet been setup.  Complete the form below to install it.");
$lstr['InstallErrorNote'] = _("One or more errors were encountered:");
$lstr['InstallFatalErrorNote'] = _("One or more fatal errors were encountered during the installation process:");
$lstr['UpgradeCompleteNote'] = _("Upgrade is complete!");
$lstr['InstallCompleteNote'] = _("Installation is complete!  You can now login with the following credentials:");
$lstr['SQLQueryErrorNote'] = _("An error occurred while executing the following SQL query.");
$lstr['UnableConnectDBErrorNote'] = _("Unable to connect to database");
$lstr['NDOUtilsMissingNote'] = _("The database you specified does not contain tables from the NDOUtils addon.  You must use the same database for both NDOUtils and Reports.  Check your configuration file.");
$lstr['ForceChangePasswordNote'] = _("You are required to change your password before proceeding.");
$lstr['FeedbackSendIntroText'] = _("We love input!  Tell us what you think about this product and you'll directly drive future innovation!");
$lstr['FeedbackSendingMessage'] = _("Please wait...");
$lstr['FeedbackSuccessMessage'] = _("Thanks for helping to make this product better!  We'll review your comments as soon as we get a chance.  Until then, kudos to you for being awesome and helping drive innovation!<br><br>   - The Dedicated Team @ Nagios Enterprises");
$lstr['FeedbackErrorMessage'] = _("An error occurred.  Please try again later.");


///////////////////////////////////////////////////////////////
// EMAIL 
///////////////////////////////////////////////////////////////
//$lstr['AdminEmailFromName']=_("Nagios XI Admin");

$lstr['PasswordResetEmailSubject'] = _("Nagios XI Password Reset");
$lstr['PasswordChangedEmailSubject'] = _("Nagios XI Password Changed");
$lstr['AccountCreatedEmailSubject'] = _("Nagios XI Account Created");

$lstr['PasswordResetEmailMessage'] = _("Your Nagios XI account password has been reset to:\n\n%s\n\nYou can login to Nagios XI at the following URL:\n\n%s\n\n");

$lstr['PasswordChangedEmailMessage'] = _("Your Nagios XI account password has been changed by an administrator.  You can login using the following information:\n\nUsername: %s\nPassword: %s\nURL: %s\n\n");

$lstr['AccountCreatedEmailMessage'] = _("An account has been created for you to access Nagios XI.  You can login using the following information:\n\nUsername: %s\nPassword: %s\nURL: %s\n\n");


///////////////////////////////////////////////////////////////
// TOOLTIP TEXT
///////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////
// IMAGE ALT/TITLE TEXT
///////////////////////////////////////////////////////////////
$lstr['EditAlt'] = _("Edit");
$lstr['DeleteAlt'] = _("Delete");
$lstr['ClearSearchAlt'] = _("Clear Search Criteria");
$lstr['CloseAlt'] = _("Close");
$lstr['PermissionsAlt'] = _("Permissions");
$lstr['CustomizePermsAlt'] = _("Customize Permissions");
$lstr['MasqueradeAlt'] = _("Masquerade As");
$lstr['ViewAlt'] = _("View");
$lstr['PopoutAlt'] = _("Popout");
$lstr['AddToMyViewsAlt'] = _("Add to My Views");
$lstr['AddViewAlt'] = _("Add View");
$lstr['EditViewAlt'] = _("Edit View");
$lstr['DeleteViewAlt'] = _("Delete View");
$lstr['SendFeedbackAlt'] = _("Send Us Feedback");
$lstr['GetPermalinkAlt'] = _("Get Permalink");
$lstr['DownloadAlt'] = _("Download");
$lstr['ViewOutputAlt'] = _("View Output");
$lstr['ViewHostNotificationsAlt'] = _("View Host Notifications");
$lstr['ViewHostStatusAlt'] = _("View Current Host Status");
$lstr['ViewHostServiceStatusAlt'] = _("View Current Status of Host Services");
$lstr['ViewServiceNotificationsAlt'] = _("View Service Notifications");
$lstr['ViewServiceStatusAlt'] = _("View Current Service Status");
$lstr['ViewHostServiceStatusAlt'] = _("View Current Status For Host Services");
$lstr['ViewHostHistoryAlt'] = _("View Host History");
$lstr['ViewServiceHistoryAlt'] = _("View Service History");
$lstr['ViewHostTrendsAlt'] = _("View Host Trends");
$lstr['ViewServiceTrendsAlt'] = _("View Service Trends");
$lstr['ViewHostAvailabilityAlt'] = _("View Host Availability");
$lstr['ViewServiceAvailabilityAlt'] = _("View Service Availability");
$lstr['ViewHostHistogramAlt'] = _("View Host Alert Histogram");
$lstr['ViewServiceHistogramAlt'] = _("View Service Alert Histogram");
$lstr['RefreshAlt'] = _("Refresh");
$lstr['ForceRefreshAlt'] = _("Force Refresh");
$lstr['ClearFilterAlt'] = _("Clear Filter");
$lstr['EditSettingsAlt'] = _("Edit Settings");


///////////////////////////////////////////////////////////////
// DATE FORMAT TYPES
///////////////////////////////////////////////////////////////
$lstr['DateFormatISO8601Text'] = _("YYYY-MM-DD HH:MM:SS");
$lstr['DateFormatUSText'] = _("MM/DD/YYYY HH:MM:SS");
$lstr['DateFormatEuroText'] = _("DD/MM/YYYY HH:MM:SS");


///////////////////////////////////////////////////////////////
// NUMBER FORMAT TYPES
///////////////////////////////////////////////////////////////
$lstr['NumberFormat1Text'] = _("1000.00");
$lstr['NumberFormat2Text'] = _("1,000.00");
$lstr['NumberFormat3Text'] = _("1.000,00");
$lstr['NumberFormat4Text'] = _("1 000,00");
$lstr['NumberFormat5Text'] = _("1'000,00");


///////////////////////////////////////////////////////////////
// OBJECT TYPES
///////////////////////////////////////////////////////////////
$lstr['HostObjectText'] = _("Host");
$lstr['HostGroupObjectText'] = _("Host Group");
$lstr['ServiceObjectText'] = _("Service");
$lstr['ServiceGroupObjectText'] = _("Service Group");
$lstr['HostEscalationObjectText'] = _("Host Escalation");
$lstr['ServiceEscalationObjectText'] = _("Service Escalation");
$lstr['HostDependencyObjectText'] = _("Host Dependency");
$lstr['ServiceDependencyObjectText'] = _("Service Dependency");
$lstr['TimeperiodObjectText'] = _("Timeperiod");
$lstr['ContactObjectText'] = _("Contact");
$lstr['ContactGroupObjectText'] = _("Contact Group");
$lstr['CommandObjectText'] = _("Command");

$lstr['HostObjectPluralText'] = _("Hosts");
$lstr['HostGroupObjectPluralText'] = _("Host Groups");
$lstr['ServiceObjectPluralText'] = _("Services");
$lstr['ServiceGroupObjectPluralText'] = _("Service Groups");
$lstr['HostEscalationObjectPluralText'] = _("Host Escalations");
$lstr['ServiceEscalationObjectPluralText'] = _("Service Escalations");
$lstr['HostDependencyObjectPluralText'] = _("Host Dependencies");
$lstr['ServiceDependencyObjectPluralText'] = _("Service Dependencies");
$lstr['TimeperiodObjectPluralText'] = _("Timeperiods");
$lstr['ContactObjectPluralText'] = _("Contacts");
$lstr['ContactGroupObjectPluralText'] = _("Contact Groups");
$lstr['CommandObjectPluralText'] = _("Commands");


///////////////////////////////////////////////////////////////
// STATE AND CHECK TYPES
///////////////////////////////////////////////////////////////
$lstr['HostStatePendingText'] = _("Pending");
$lstr['HostStateUnknownText'] = _("Unknown");
$lstr['HostStateUpText'] = _("Up");
$lstr['HostStateDownText'] = _("Down");
$lstr['HostStateUnreachableText'] = _("Unreachable");

$lstr['ServiceStatePendingText'] = _("Pending");
$lstr['ServiceStateOkText'] = _("Ok");
$lstr['ServiceStateWarningText'] = _("Warning");
$lstr['ServiceStateUnknownText'] = _("Unknown");
$lstr['ServiceStateCriticalText'] = _("Critical");

$lstr['HardStateText'] = _("Hard");
$lstr['SoftStateText'] = _("Soft");

$lstr['PassiveCheckText'] = _("Passive");
$lstr['ActiveCheckText'] = _("Active");


///////////////////////////////////////////////////////////////
// UNSORTED MISC
///////////////////////////////////////////////////////////////
$lstr['FeedbackPopupTitle'] = _("Send Us Feedback");
$lstr['AjaxErrorHeader'] = _("Error");
$lstr['AjaxErrorMessage'] = _("An error occurred processing your request. :-(");
$lstr['AjaxSendingHeader'] = _("Please Wait");
$lstr['AjaxSendingMessage'] = _("Processing...");

$lstr['AddToMyViewsHeader'] = _("Add View");
$lstr['AddToMyViewsMessage'] = _("Use this to add what you see on the screen to your <b>Views</b> page.");
$lstr['AddToMyViewsSuccessHeader'] = _("View Added");
$lstr['AddToMyViewsSuccessMessage'] = _("Success! Your view was added to your <b>Views</b> page.");
$lstr['AddToMyViewsTitleBoxTitle'] = _("View Title");

$lstr['AddViewHeader'] = _("Add View");
$lstr['AddViewMessage'] = "";
$lstr['AddViewSuccessHeader'] = _("View Added");
$lstr['AddViewSuccessMessage'] = _("Success! Your view was added to your <b>Views</b> page.");
$lstr['AddViewURLBoxTitle'] = _("URL");
$lstr['AddViewTitleBoxTitle'] = _("View Title");

$lstr['EditViewHeader'] = _("Edit View");
$lstr['EditViewMessage'] = "";
$lstr['EditViewSuccessHeader'] = _("View Changed");
$lstr['EditViewSuccessMessage'] = _("Success! Your view was updated successfully.");
$lstr['EditViewURLBoxTitle'] = _("URL");
$lstr['EditViewTitleBoxTitle'] = _("View Title");

$lstr['PermalinkHeader'] = _("Permalink");
$lstr['PermalinkMessage'] = _("Copy the URL below to retain a direct link to your current view.");
$lstr['PermalinkURLBoxTitle'] = _("URL");

$lstr['MyViewsPageTitle'] = _("My Views");
$lstr['NoViewsDefinedPageHeader'] = _("No Views Defined");
$lstr['NoViewsDefinedText'] = _("You have no views defined.");

$lstr['MyDashboardsPageTitle'] = _("My Dashboards");
$lstr['NoDashboardsDefinedPageHeader'] = _("No Dashboards Defined");
$lstr['NoDashboardsDefinedText'] = _("You have no dashboards defined.");

$lstr['AddDashboardAlt'] = _("Add A New Dashboard");
$lstr['EditDashboardAlt'] = _("Edit Dashboard");
$lstr['DeleteDashboardAlt'] = _("Delete Dashboard");

$lstr['PauseAlt'] = _("Pause");

$lstr['AvailableDashletsPageTitle'] = _("Available Dashlets");
$lstr['AvailableDashletsPageHeader'] = _("Available Dashlets");
$lstr['AvailableDashletsPageText'] = _("The following dashlets can be added to any one or more of your dashboards.  How awesome!");

$lstr['AddDashboardHeader'] = _("Add Dashboard");
$lstr['AddDashboardMessage'] = _("Use this to add a new dashboard to your <b>Dashboards</b> page.");
$lstr['AddDashboardTitleBoxTitle'] = _("Dashboard Title");
$lstr['AddDashboardSuccessHeader'] = _("Dashboard Added");
$lstr['AddDashboardSuccessMessage'] = _("Success! Your new dashboard has been added.");

$lstr['EditDashboardHeader'] = _("Edit Dashboard");
$lstr['EditDashboardMessage'] = "";
$lstr['EditDashboardSuccessHeader'] = _("Dashboard Changed");
$lstr['EditDashboardSuccessMessage'] = _("Success! Your dashboard was updated successfully.");
$lstr['EditDashboardTitleBoxTitle'] = _("Dashboard Title");

$lstr['DeleteDashboardHeader'] = _("Confirm Dashboard Deletion");
$lstr['DeleteDashboardMessage'] = _("Are you sure you want to delete this dashboard and all dashlets it contains?");
$lstr['DeleteDashboardSuccessHeader'] = _("Dashboard Deleted");
$lstr['DeleteDashboardSuccessMessage'] = _("The requested dashboard has been deleted.  Good riddance!");

$lstr['DeleteButton'] = _("Delete");

$lstr['BadDashboardPageTitle'] = _("Bad Dashboard");
$lstr['BadDashboardPageHeader'] = _("Bad Dashboard");
$lstr['BadDashboardText'] = _("Unfortunately for you, that dashboard is not valid...  Too bad.");

$lstr['ViewDeletedHeader'] = _("View Deleted");
$lstr['ViewDeletedMessage'] = _("Good riddance!");

$lstr['AddToDashboardHeader'] = _("Add To Dashboard");
$lstr['AddToDashboardMessage'] = _("Add this powerful little dashlet to one of your dashboards for visual goodness.");
$lstr['AddToDashboardTitleBoxTitle'] = _("Dashlet Title");

$lstr['AddToDashboardSuccessHeader'] = _("Dashlet Added");
$lstr['AddToDashboardSuccessMessage'] = _("The little dashlet that could will now be busy at work on your dashboard...");
$lstr['AddToDashboardDashboardSelectTitle'] = _("Which Dashboard?");

$lstr['ViewsPageTitle'] = _("Views");
$lstr['AdminPageTitle'] = _("Admin");
$lstr['DashboardsPageTitle'] = _("Dashboards");
$lstr['SubcomponentsPageTitle'] = _("Addons");
$lstr['SubcomponentsPageHeader'] = _("Addons");

$lstr['NoViewsToDeleteHeader'] = _("No View");
$lstr['NoViewsToDeleteMessage'] = _("There is no active view to delete.");
$lstr['NoViewsToEditHeader'] = _("No View");
$lstr['NoViewsToEditMessage'] = _("There is no active view to edit.");

$lstr['NoDashboardsToDeleteHeader'] = _("No Dashboard");
$lstr['NoDashboardsToDeleteMessage'] = _("There is no active dashboard to delete.");
$lstr['NoDashboardsToEditHeader'] = _("No Dashboard");
$lstr['NoDashboardsToEditMessage'] = _("There is no active dashboard to edit.");

$lstr['AddItButton'] = _("Add It");

$lstr["DashletDeletedHeader"] = _("Dashlet Deleted");
$lstr["DashletDeletedMessage"] = _("Good riddance!");

$lstr["PinFloatDashletAlt"] = _("Pin / Float Dashlet");
$lstr["ConfigureDashletAlt"] = _("Configure Dashlet");
$lstr["DeleteDashletAlt"] = _("Delete Dashlet");
$lstr["DashboardBackgroundColorTitle"] = _("Background Color");


$lstr["AccountSettingsPageTitle"] = _("Account Information");
$lstr["AccountSettingsPageHeader"] = _("Account Information");
$lstr["MyAccountSettingsSectionTitle"] = _("General Account Settings");
$lstr["MyAccountPreferencesSectionTitle"] = _("Account Preferences");

$lstr["NotificationPrefsPageTitle"] = _("Notification Preferences");
$lstr["NotificationPrefsPageHeader"] = _("Notification Preferences");


$lstr["IngoreUpdateNotices"] = _("Ignore Update Notices");
$lstr["DemoModeChangeError"] = _("Changes are disabled while in demo mode.");

$lstr["GlobalConfigPageTitle"] = _("System Settings");
$lstr["AutoUpdateCheckBoxTitle"] = _("Automatically Check for Updates");

$lstr["ManageUsersPageTitle"] = _("Manage Users");
$lstr["ManageUsersPageHeader"] = _("Manage Users");

$lstr['NoMatchingRecordsFoundText'] = _("Not Matching Records Found.");

$lstr['CloneAlt'] = _("Clone");

$lstr['MasqueradeAlertHeader'] = _("Masquerade Notice");
$lstr['MasqueradeMessageText'] = _("You are about to masquerade as another user.  If you choose to continue you will be logged out of your current account and logged in as the selected user.  In the process of doing so, you may lose your admin privileges.");

$lstr['AddUserPageTitle'] = _("Add New User");
$lstr['AddUserPageHeader'] = _("Add New User");
$lstr['EditUserPageTitle'] = _("Edit User");
$lstr['EditUserPageHeader'] = _("Edit User");

$lstr['UserAccountGeneralSettingsSectionTitle'] = _("General Settings");
$lstr['UserAccountPreferencesSectionTitle'] = _("Preferences");
$lstr['UserAccountSecuritySettingsSectionTitle'] = _("Security Settings");

$lstr['ProgramURLText'] = _("Program URL");

$lstr['GlobalConfigUpdatedText'] = _("Settings Updated.");

$lstr['AdminNameText'] = _("Administrator Name");
$lstr['AdminEmailText'] = _("Administrator Email Address");

$lstr['ForcePasswordChangePageTitle'] = _("Password Change Required");

$lstr['CloneUserPageTitle'] = _("Clone User");
$lstr['CloneUserPageHeader'] = _("Clone User");
$lstr['CloneUserButton'] = _("Clone User");

$lstr['UserClonedText'] = _("User cloned.");

$lstr['CloneUserDescription'] = _("Use this functionality to create a new user account that is an exact clone of another account on the system.  The cloned account will inherit all preferences, views, and dashboards of the original user.");

$lstr['SystemStatusPageTitle'] = _("System Status");
$lstr['SystemStatusPageHeader'] = _("System Status");
$lstr['MonitoringEngineStatusPageTitle'] = _("Monitoring Engine Status");
$lstr['MonitoringEngineStatusPageHeader'] = _("Monitoring Engine Status");

$lstr['CannotDeleteHomepageDashboardHeader'] = _("Error");
$lstr['CannotDeleteHomepageDashboardMessage'] = _("You cannot delete your home page dashboard.");

$lstr['CloneDashboardAlt'] = _("Clone Dashboard");

$lstr['CloneButton'] = _("Clone");

$lstr['CloneDashboardHeader'] = _("Clone Dashboard");
$lstr['CloneDashboardMessage'] = _("Use this to make an exact clone of the current dashboard and all its wonderful dashlets.");
$lstr['CloneDashboardSuccessHeader'] = _("Dashboard Cloned");
$lstr['CloneDashboardSuccessMessage'] = _("Dashboard successfully cloned.");
$lstr['CloneDashboardTitleBoxTitle'] = _("New Title");

$lstr['CannotDeleteHomepageDashletHeader'] = _("Error");
$lstr['CannotDeleteHomepageDashletMessage'] = _("Deleting dashlets from the home page dashboard is disabled while in demo mode.");

$lstr['PerformanceGraphsPageTitle'] = _("Performance Graphs");
$lstr['PerformanceGraphsPageHeader'] = _("Performance Graphs");

$lstr['NoPerformanceGraphDataSourcesMessage'] = _("There are no datasources to display for this service.");

$lstr['ClearDateAlt'] = _("Clear Date");
$lstr['NotAuthorizedErrorText'] = _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");

$lstr['ReportsPageTitle'] = _("Reports");
$lstr['ReportsPageHeader'] = _("Reports");
$lstr['HelpPageTitle'] = _("Help");
$lstr['HelpPageHeader'] = _("Help");

$lstr['NagiosCoreReportsPageTitle'] = _("Reports");
$lstr['NagiosCoreReportsPageHeader'] = _("Reports");
//$lstr['NagiosCoreReportsMessage']=_("Legacy Nagios&reg; Core&trade; reports are provided for historical purposes.  Please note that legacy report do not offer they same flexibility or options as newer XI reports.");
$lstr['NagiosCoreReportsMessage'] = "";

$lstr['NagiosXIReportsPageTitle'] = _("Reports");
$lstr['NagiosXIReportsPageHeader'] = _("Reports");
$lstr['NagiosXIReportsMessage'] = _("Reports allow you to see how well your network and system have performed over a period of time.  Available reports are listed below.");

$lstr['LegacyReportAvailabilityTitle'] = _("Availability Report");
$lstr['LegacyReportAvailabilityDescription'] = _("Provides an availability report of uptime for hosts and services.  Useful for determining SLA requirements and compliance.");
$lstr['LegacyReportTrendsTitle'] = _("Trends Report");
$lstr['LegacyReportTrendsDescription'] = _("Provides a graphical, timeline breakdown of the state of a particular host or service.");
$lstr['LegacyReportHistoryTitle'] = _("Alert History Report");
$lstr['LegacyReportHistoryDescription'] = _("Provides a record of historical alerts for hosts and services.");
$lstr['LegacyReportSummaryTitle'] = _("Alert Summary Report");
$lstr['LegacyReportSummaryDescription'] = _("Provides a report of top alert producers.  Useful for determining the biggest trouble-makers in your IT infrastructure.");
$lstr['LegacyReportHistogramTitle'] = _("Alert Histogram Report");
$lstr['LegacyReportHistogramDescription'] = _("Provides a frequency graph of host and service alerts.  Useful for seeing when alerts most often occur for a particular host or service.");
$lstr['LegacyReportNotificationsTitle'] = _("Notifications Report");
$lstr['LegacyReportNotificationsDescription'] = _("Provides a historical record of host and service notifications that have been sent to contacts.");


$lstr['SubcomponentsMessage'] = _("Nagios XI includes several proven, enterprise-grade Open Source addons.  You may access these addons directly using the links below.");

$lstr['SubcomponentNagiosCoreDescription'] = _("Nagios&reg; Core&trade; provides the primary monitoring and alerting engine.");

$lstr['SubcomponentNagiosCoreConfigDescription'] = _("Nagios Core Config Manager provides an advanced graphical interface for configuring the Nagios Core monitoring and alerting engine. Recommended for advanced users only.");

$lstr['ApplyNagiosCoreConfigPageTitle'] = _("Apply Configuration");
$lstr['ApplyNagiosCoreConfigPageHeader'] = _("Apply Configuration");

$lstr['ApplyingNagiosCoreConfigPageTitle'] = _("Applying Configuration");
$lstr['ApplyingNagiosCoreConfigPageHeader'] = _("Applying Configuration");
$lstr['ApplyNagiosCoreConfigMessage'] = _("Use this feature to apply any outstanding configuration changes to Nagios Core.  Changes will be applied and the monitoring engine will be restarted with the updated configuration.");

$lstr['ApplyConfigText'] = _("Apply Configuration");
$lstr['TryAgainText'] = _("Try Again");
$lstr['ApplyConfigSuccessMessage'] = _("Success!  Nagios Core was restarted with an updated configuration.");
$lstr['ApplyConfigErrorMessage'] = _("An error occurred while attempting to apply your configuration to Nagios Core.  Monitoring engine configuration files have been rolled back to their last known good checkpoint.");
$lstr['ViewConfigSuccessSnapshotMessage'] = _("View configuration snapshots");
$lstr['ViewConfigErrorSnapshotMessage'] = _("View a snapshot of this configuration error");

$lstr['AjaxSubmitCommandHeader'] = _("Please Wait");
$lstr['AjaxSubmitCommandMessage'] = _("Submitting command");

$lstr['HelpPageTitle'] = _("Help for Nagios XI");
$lstr['HelpPageHeader'] = _("Help for Nagios XI");
$lstr['HelpPageGeneralSectionTitle'] = _("Get Help Online");
$lstr['HelpPageMoreOptionsSectionTitle'] = _("More Options");

$lstr['AboutPageTitle'] = _("Montiq Beta");
$lstr['AboutPageHeader'] = _("About");

$lstr['LegalPageTitle'] = _("Legal Information");
$lstr['LegalPageHeader'] = _("Legal Information");

$lstr['LicensePageTitle'] = _("License Information");
$lstr['LicensePageHeader'] = _("License Information");


$lstr['AdminPageTitle'] = _("Administration");
$lstr['AdminPageHeader'] = _("Administration");


$lstr['HostStatusDetailPageTitle'] = _("Host Status Detail");
$lstr['HostStatusDetailPageHeader'] = _("Host Status Detail");
$lstr['ServiceStatusDetailPageTitle'] = _("Service Status Detail");
$lstr['ServiceStatusDetailPageHeader'] = _("Service Status Detail");
$lstr['ServiceGroupStatusPageTitle'] = _("Service Group Status");
$lstr['ServiceGroupStatusPageHeader'] = _("Service Group Status");
$lstr['HostGroupStatusPageTitle'] = _("Host Group Status");
$lstr['HostGroupStatusPageHeader'] = _("Host Group Status");
$lstr['HostStatusPageTitle'] = _("Host Status");
$lstr['HostStatusPageHeader'] = _("Host Status");
$lstr['ServiceStatusPageTitle'] = _("Service Status");
$lstr['ServiceStatusPageHeader'] = _("Service Status");
$lstr['TacticalOverviewPageTitle'] = _("Tactical Overview");
$lstr['TacticalOverviewPageHeader'] = _("Tactical Overview");
$lstr['OpenProblemsPageTitle'] = _("Open Problems");
$lstr['OpenProblemsPageHeader'] = _("Open Problems");
$lstr['HostProblemsPageTitle'] = _("Host Problems");
$lstr['HostProblemsPageHeader'] = _("Host Problems");
$lstr['ServiceProblemsPageTitle'] = _("Service Problems");
$lstr['ServiceProblemsPageHeader'] = _("Service Problems");

$lstr['HostNameTableHeader'] = _("Host");
$lstr['ServiceNameTableHeader'] = _("Service");
$lstr['StatusTableHeader'] = _("Status");
$lstr['LastCheckTableHeader'] = _("Last Check");
$lstr['CheckAttemptTableHeader'] = _("Attempt");
$lstr['DurationTableHeader'] = _("Duration");
$lstr['StatusInformationTableHeader'] = _("Status Information");

$lstr['LicensePageTitle'] = _("License Information");
$lstr['LicensePageHeader'] = _("License Information");
$lstr['LicensePageMessage'] = "";

$lstr['LicenseKeySectionTitle'] = _("License Key");
$lstr['LicenseTypeText'] = _("License Type");
$lstr['LicenseTypeFreeText'] = _("Free");
$lstr['LicenseTypeFreeNotes'] = _("(Limited edition without support)");
$lstr['LicenseTypeLicensedText'] = _("Licensed");
$lstr['LicenseInformationSectionTitle'] = _("License Information");
$lstr['LicenseKeyText'] = _("Your License Key");
$lstr['UpdateLicenseButton'] = _("Update License");
$lstr['InvalidLicenseKeyError'] = _("The license key you entered is not valid.");
$lstr['LicenseInformationUpdatedText'] = _("License key updated successfully.");
$lstr['LicenseExceededPageTitle'] = _("License Exceeded");
$lstr['LicenseExceededPageHeader'] = _("License Exceeded");
$lstr['LicenseExceededMessage'] = _("You have exceeded your license, so this feature is not available.");
$lstr['LicenseOptionsSectionTitle'] = _("License Options");

$lstr['AccountInfoPageTitle'] = _("Account Information");

$lstr['NotificationMethodsSectionTitle'] = _("Notification Methods");
$lstr['NotificationMethodsMessage'] = _("Specify the methods by which you'd like to receive alert messages.  <br><b>Note:</b>These methods are only used if you have <a href='notifyprefs.php'>enabled notifications</a> for your account.");

$lstr['ReceiveNotificationsByEmail'] = _("Email");
$lstr['ReceiveNotificationsByMobileTextMessage'] = _("Mobile Phone Text Message");
$lstr['EnableNotifications'] = _("Enable Notifications");
$lstr['EnableNotificationsMessage'] = _("Choose whether or not you want to receive alert messages.  <br><b>Note:</b> You must specify which notification methods to use in the <a href='notifymethods.php'>notification methods</a> page.");
$lstr['EnableNotificationsSectionTitle'] = _("Notification Status");
$lstr['MobileNumberBoxTitle'] = _("Mobile Phone Number");
$lstr['MobileProviderBoxTitle'] = _("Mobile Phone Carrier");
$lstr['InvalidMobileNumberError'] = _("Invalid mobile phone number.");
$lstr['BlankMobileNumberError'] = _("Missing mobile phone number.");
$lstr['NotificationsPrefsUpdatedText'] = _("Notification preferences updated.");
$lstr['NotificationTypesSectionTitle'] = _("Notification Types");
$lstr['NotificationTypesMessage'] = _("Select the types of alerts you'd like to receive.");
$lstr['NotificationTimesSectionTitle'] = _("Notification Times");
$lstr['NotificationTimesMessage'] = _("Specify the times of day you'd like to receive alerts.");

$lstr['HostRecoveryNotificationsBoxTitle'] = _("Host Recovery");
$lstr['HostDownNotificationsBoxTitle'] = _("Host Down");
$lstr['HostUnreachableNotificationsBoxTitle'] = _("Host Unreachable");
$lstr['HostFlappingNotificationsBoxTitle'] = _("Host Flapping");
$lstr['HostDowntimeNotificationsBoxTitle'] = _("Host Downtime");
$lstr['ServiceWarningNotificationsBoxTitle'] = _("Service Warning");
$lstr['ServiceRecoveryNotificationsBoxTitle'] = _("Service Recovery");
$lstr['ServiceUnknownNotificationsBoxTitle'] = _("Service Unknown");
$lstr['ServiceCriticalNotificationsBoxTitle'] = _("Service Critical");
$lstr['ServiceFlappingNotificationsBoxTitle'] = _("Service Flapping");
$lstr['ServiceDowntimeNotificationsBoxTitle'] = _("Service Downtime");

$lstr['NoNotificationMethodsSelectedError'] = _("No notification methods selected.");
$lstr['InvalidTimeRangesError'] = _("One or more time ranges is invalid.");
$lstr['BlankMobileProviderError'] = _("No mobile carrier selected.");


$lstr['WeekdayBoxTitle'] = array(
    0 => "Sunday",
    1 => "Monday",
    2 => "Tuesday",
    3 => "Wednesday",
    4 => "Thursday",
    5 => "Friday",
    6 => "Saturday",
);

$lstr['FromBoxTitle'] = _("From");
$lstr['ToBoxTitle'] = _("To");

$lstr['AuthorizedForAllObjectsBoxTitle'] = _("Can see all hosts and services");
$lstr['AuthorizedToConfigureObjectsBoxTitle'] = _("Can (re)configure hosts and services");
$lstr['AuthorizedForAllObjectCommandsBoxTitle'] = _("Can control all hosts and services");
$lstr['AuthorizedForMonitoringSystemBoxTitle'] = _("Can see/control monitoring engine");
$lstr['AdvancedUserBoxTitle'] = _("Can access advanced features");
$lstr['ReadonlyUserBoxTitle'] = _("Has read-only access");

$lstr['NotAuthorizedPageTitle'] = _("Not Authorized");
$lstr['NotAuthorizedPageHeader'] = _("Not Authorized");
$lstr['NotAuthorizedForObjectMessage'] = _("You are not authorized to view the requested object, or the object does not exist.");

$lstr['NotificationMessagesPageTitle'] = _("Notification Messages");
$lstr['NotificationMessagesPageHeader'] = _("Notification Messages");
$lstr['NotificationMessagesMessage'] = _("Use this feature to customize the content of the notification messages you receive.");

$lstr['EmailNotificationMessagesSectionTitle'] = _("Email Notifications");
$lstr['EmailNotificationMessagesMessage'] = _("Specify the format of the host and service alert messages you receive via email.");

$lstr['MobileTextNotificationMessagesSectionTitle'] = _("Mobile Text Notifications");
$lstr['MobileTextNotificationMessagesMessage'] = _("Specify the format of the host and service alert messages you receive via mobile text message.");

$lstr['HostNotificationMessageSubjectBoxTitle'] = _("Host Alert Subject");
$lstr['HostNotificationMessageBodyBoxTitle'] = _("Host Alert Message");
$lstr['ServiceNotificationMessageSubjectBoxTitle'] = _("Service Alert Subject");
$lstr['ServiceNotificationMessageBodyBoxTitle'] = _("Service Alert Message");

$lstr['AgreeLicenseError'] = _("You must agree to the license before using this software.");
$lstr['AgreeToLicenseBoxText'] = _("I have read, understood, and agree to be bound by the terms of the license above.");

$lstr['AgreeLicensePageTitle'] = _("License Agreement");
$lstr['AgreeLicensePageHeader'] = _("License Agreement");
$lstr['AgreeLicenseNote'] = _("You must agree to the Nagios Software License Terms and Conditions before continuing using this software.");

$lstr['InstallPageTitle'] = _("Nagios XI Installer");
$lstr['InstallPageHeader'] = _("Nagios XI Installer");
$lstr['InstallPageMessage'] = _("Welcome to the Nagios XI installation.  Just answer a few simple questions and you'll be ready to go.");

$lstr['InstallCompletePageTitle'] = _("Installation Complete");
$lstr['InstallCompletePageHeader'] = _("Installation Complete");
$lstr['InstallCompletePageMessage'] = _("Congratulations! You have successfully installed Nagios XI.");

$lstr['ConfigPageTitle'] = _("Configuration"); // used twice
$lstr['ConfigOverviewPageTitle'] = _("Configuration Options");
$lstr['ConfigOverviewPageHeader'] = _("Configuration Options");
$lstr['ConfigOverviewPageNotes'] = _("What would you like to configure?");

$lstr['MonitoringWizardPageHeader'] = _("Monitoring Wizard");
$lstr['MonitoringWizardPageTitle'] = _("Monitoring Wizard");


$lstr['NextButton'] = _("Next");
$lstr['BackButton'] = _("Back");

$lstr['MonitoringWizardStep1PageTitle'] = _("Monitoring Wizard");
$lstr['MonitoringWizardStep1PageHeader'] = _("Monitoring Wizard - Step 1");
$lstr['MonitoringWizardStep1SectionTitle'] = "";
$lstr['MonitoringWizardStep1Notes'] = _("Monitoring wizards guide you through the process of monitoring devices, servers, applications, services, and more.  Select the appropriate wizard below to get started.");

$lstr['MonitoringWizardStep2PageTitle'] = _("Monitoring Wizard");
$lstr['MonitoringWizardStep2PageHeader'] = _("Monitoring Wizard - Step 2");

$lstr['MonitoringWizardStep3PageTitle'] = _("Monitoring Wizard");
$lstr['MonitoringWizardStep3PageHeader'] = _("Monitoring Wizard - Step 3");

$lstr['MonitoringWizardStep4PageTitle'] = _("Monitoring Wizard");
$lstr['MonitoringWizardStep4PageHeader'] = _("Monitoring Wizard - Step 4");

$lstr['MonitoringWizardStep5PageTitle'] = _("Monitoring Wizard");
$lstr['MonitoringWizardStep5PageHeader'] = _("Monitoring Wizard - Step 5");

$lstr['MonitoringWizardStep6PageTitle'] = _("Monitoring Wizard");
$lstr['MonitoringWizardStep6PageHeader'] = _("Monitoring Wizard - Step 6");

$lstr['MonitoringWizardStepFinalPageTitle'] = _("Monitoring Wizard");
$lstr['MonitoringWizardStepFinalPageHeader'] = _("Monitoring Wizard - Final Step");

$lstr['MonitoringWizardCommitCompletePageTitle'] = _("Monitoring Wizard");
$lstr['MonitoringWizardCommitCompletePageHeader'] = _("Monitoring Wizard");

$lstr['MonitoringWizardCommitSuccessSectionTitle'] = _("Configuration Request Successful");
$lstr['MonitoringWizardCommitSuccessNotes'] = _("Your configuration changes have been successfully applied to the monitoring engine.");

$lstr['MonitoringWizardCommitErrorSectionTitle'] = _("Configuration Error");
$lstr['MonitoringWizardCommitErrorNotes'] = _("An error occurred while attempting to apply your configuration to the monitoring engine.  Contact your Nagios administrator if this problem persists.");

$lstr['MonitoringWizardPermissionsErrorPageTitle'] = _("Monitoring Wizard");
$lstr['MonitoringWizardPermissionsErrorPageHeader'] = _("Monitoring Wizard - An Error Occurrred");

$lstr['MonitoringWizardPermissionsErrorSectionTitle'] = _("Configuration Request Error");
$lstr['MonitoringWizardPermissionsErrorNotes'] = _("An error occurred while attempting to modify the monitoring engine.  This error occurred because the wizard attempted to modify hosts or services that you do not have permission for.  Contact your Nagios XI administrator for more information.");

$lstr['NoConfigWizardSelectedError'] = _("No wizard selected.");

$lstr['ApplyButton'] = _("Apply");
$lstr['RunThisWizardAgainButton'] = _("Run this monitoring wizard again");
$lstr['RunWizardAgainButton'] = _("Run another monitoring wizard");

$lstr['ApplySettingsButton'] = _("Apply Settings");

$lstr['QuickFind'] = _("Quick Find");

$lstr['AdminPageNotes'] = _("<p>Manage your XI installation with the administrative options available to you in this section.  Make sure you complete any setup tasks that are shown below before using your XI installation.</p>");

$lstr['SecurityCredentialsPageTitle'] = _("Security Credentials");
$lstr['SecurityCredentialsPageHeader'] = _("Security Credentials");

$lstr['SecurityCredentialsPageNotes'] = _("<p>Use this form to reset various internal security credentials used by your XI system.  This is an important step to ensure your XI system does not use default passwords or tokens, which may leave it open to a security breach.</p>");

$lstr['ComponentCredentialsSectionTitle'] = _("Component Credentials");

$lstr['ComponentCredentialsNote'] = _("The credentials listed below are used to manage various aspects of the XI system.  Remember these passwords!");

$lstr['SubsystemCredentialsSectionTitle'] = _("Sub-System Credentials");

$lstr['SubsystemCredentialsNote'] = _("<p>You do not need to remember the credentials below, as they are only used internally by the XI system.</p>");

$lstr['SubsystemTicketText'] = _("XI Subsystem Ticket");
$lstr['UpdateCredentialsButton'] = _("Update Credentials");
$lstr['CurrentText'] = _("Current");
$lstr['ConfigManagerBackendPasswordText'] = _("Config Manager Backend Password");
$lstr['ConfigManagerAdminPasswordText'] = _("New Config Manager Admin Password");
$lstr['ConfigManagerAdminUsernameText'] = _("Admin Username");

$lstr["NoSubsystemTicketError"] = _("No subsystem ticket.");
$lstr["NoConfigBackendPasswordError"] = _("No config backend password.");

$lstr['SecurityCredentialsUpdatedText'] = _("Security credentials updated successfully.");

$lstr['NagiosCoreBackendPasswordText'] = _("Nagios Core Backend Password");

$lstr["NoNagiosCoreBackendPasswordError"] = _("No Nagios Core backend password.");

$lstr['AuditLogPageTitle'] = _("Audit Log");
$lstr['AuditLogPageHeader'] = _("Audit Log");
$lstr['AuditLogPageNotes'] = _("The audit log provides admins with a record of changes that occur on the XI system, which is useful for ensuring your organization meets compliance requirements.");

$lstr['CoreConfigSnapshotsPageTitle'] = _("Monitoring Configuration Snapshots");
$lstr['CoreConfigSnapshotsPageHeader'] = _("Monitoring Configuration Snapshots");
$lstr['CoreConfigSnapshotsPageNotes'] = _("The latest configuration snapshots of the XI monitoring engine are shown below.  Download the most recent snapshots as backups, or get vital information for troubleshooting configuration errors.");

$lstr['MonitoringPluginsPageTitle'] = _("Monitoring Plugins");
$lstr['MonitoringPluginsPageHeader'] = _("Monitoring Plugins");
$lstr['MonitoringPluginsPageNotes'] = _("Manage the monitoring plugins and scripts that are installed on this system.  Use caution when deleting plugins or scripts, as they may cause your monitoring system to generate errors.");

$lstr["SelectFileBoxText"] = _("Browse File");
$lstr["UploadNewPluginBoxText"] = _("Upload A New Plugin");

$lstr['PluginUploadedText'] = _("New plugin was installed successfully.");
$lstr['PluginUploadFailedText'] = _("Plugin could not be installed - directory permissions may be incorrect.");

$lstr['PluginDeletedText'] = _("Plugin deleted.");
$lstr['PluginDeleteFailedText'] = _("Plugin delete failed - directory permissions may be incorrect.");
$lstr['NoPluginUploadedText'] = _("No plugin selected for upload.");

$lstr['FilePermsTableHeader'] = _("Permissions");
$lstr['FileOwnerTableHeader'] = _("Owner");
$lstr['FileGroupTableHeader'] = _("Group");

$lstr['ManageConfigWizardsPageTitle'] = _("Manage Configuration Wizards");
$lstr['ManageConfigWizardsPageHeader'] = _("Manage Configuration Wizards");
$lstr['ManageConfigWizardsPageNotes'] = _("Manage the configuration wizards that are installed on this system and available to users under the <a href='../config/'>configuration</a> menu.  Need a custom configuration wizard created for your organization?  <a href='https://www.nagios.com/contact/' target='_blank'>Contact us</a> for pricing information.");

$lstr["UploadNewWizardBoxText"] = _("Upload A New Wizard");
$lstr['UploadWizardButton'] = _("Upload Wizard");

$lstr['WizardNameTableHeader'] = _("Wizard");
$lstr['WizardTypeTableHeader'] = _("Wizard Type");

$lstr['NoWizardUploadedText'] = _("No wizard selected for upload.");
$lstr['WizardUploadFailedText'] = _("Wizard upload failed.");
$lstr['WizardScheduledForInstallText'] = _("Wizard scheduled for installation.");
$lstr['WizardInstalledText'] = _("Wizard installed.");
$lstr['WizardInstallFailedText'] = _("Wizard installation failed.");
$lstr['WizardPackagingTimedOutText'] = _("Wizard packaging timed out.");
$lstr['WizardScheduledForInstallationText'] = _("Wizard scheduled for installation.");
$lstr['WizardDeletedText'] = _("Wizard deleted.");
$lstr['WizardScheduledForDeletionText'] = _("Wizard scheduled for deletion.");

$lstr['ManageDashletsPageTitle'] = _("Manage Dashlets");
$lstr['ManageDashletsPageHeader'] = _("Manage Dashlets");
$lstr['ManageDashletsPageNotes'] = _("Manage the dashlets that are installed on this system and available to users.  Need a custom dashlet created for your organization?  <a href='https://www.nagios.com/contact/' target='_blank'>Contact us</a> for pricing information.");


$lstr['UploadNewDashletBoxText'] = _("Upload a New Dashlet");
$lstr['UploadDashletButton'] = _("Upload Dashlet");

$lstr['DashletNameTableHeader'] = _("Dashlet");

$lstr['DashletScheduledForInstallationText'] = _("Dashlet scheduled for installation.");
$lstr['DashletUploadFailedText'] = _("Dashlet upload failed.");
$lstr['DashletPackagingTimedOutText'] = _("Dashlet packaging timed out.");
$lstr['DashletDeletedText'] = _("Dashlet deleted.");
$lstr['DashletScheduledForDeletionText'] = _("Dashlet scheduled for deletion.");
$lstr['DashletInstalledText'] = _("Dashlet installed.");
$lstr['DashletInstallFailedText'] = _("Dashlet installation failed.");

$lstr['ManageComponentsPageTitle'] = _("Manage Components");
$lstr['ManageComponentsPageHeader'] = _("Manage Components");
$lstr['ManageComponentsPageNotes'] = _("Manage the components that are installed on this system.  Need a custom component created to extend Nagios XI's capabilities?  <a href='https://www.nagios.com/contact/' target='_blank'>Contact us</a> for pricing information.");

$lstr['ComponentDeletedText'] = _("Component deleted.");
$lstr['ComponentScheduledForDeletionText'] = _("Component scheduled for delettion.");
$lstr['ComponentUploadFailedText'] = _("Component upload failed.");
$lstr['ComponentScheduledForInstallationText'] = _("Component scheduled for installation.");
$lstr['ComponentInstalledText'] = _("Component installed.");
$lstr['ComponentInstallFailedText'] = _("Component installation failed.");
$lstr['ComponentPackagingTimedOutText'] = _("Component packaging timed out.");

$lstr['ConfigSnapshotDeletedText'] = _("Config snapshot deleted.");
$lstr['ConfigSnapshotScheduledForDeletionText'] = _("Config snapshot deleted.");

$lstr["UploadNewComponentBoxText"] = _("Upload a New Component");
$lstr['UploadComponentButton'] = _("Upload Component");

$lstr['ComponentNameTableHeader'] = _("Component");
$lstr['ComponentTypeTableHeader'] = _("Type");
$lstr['ComponentSettingsTableHeader'] = _("Settings");

$lstr['ConfigureComponentPageTitle'] = _("Component Configuration");
$lstr['ConfigureComponentPageHeader'] = _("Component Configuration");

$lstr['ComponentSettingsUpdatedText'] = _("Component settings updated.");

$lstr['ErrorSubmittingCommandText'] = _("Error submitting command.");

$lstr['NotificationTestPageTitle'] = _("Send Test Notifications");
$lstr['NotificationTestPageHeader'] = _("Send Test Notifications");
$lstr['NotificationTestPageNotes'] = _("Click the button below to send test notifications to your email and/or mobile phone.");

$lstr['SendTestNotificationsButton'] = _("Send Test Notifications");

$lstr['MailSettingsPageTitle'] = _("Mail Settings");
$lstr['MailSettingsPageHeader'] = _("Mail Settings");
$lstr['MailSettingsPageMessage'] = _("Modify the settings used by your Nagios XI system for sending email alerts and informational messages.<br><b>Note:</b> Mail messages may fail to be delivered if your XI server does not have a valid DNS name.");

$lstr['MailSettingsUpdatedText'] = _("Mail settings updated.");

$lstr['GeneralMailSettingsSectionTitle'] = _("General Mail Settings");
$lstr['MailMethodBoxText'] = _("Mail Method");
$lstr['MailFromAddressBoxText'] = _("Send Mail From");

$lstr['SMTPSettingsSectionTitle'] = _("SMTP Settings");

$lstr['SMTPHostBoxText'] = _("Host");
$lstr['SMTPPortBoxText'] = _("Port");
$lstr['SMTPUsernameBoxText'] = _("Username");
$lstr['SMTPPasswordBoxText'] = _("Password");
$lstr['SMTPSecurityBoxText'] = _("Security");

$lstr['NoFromAddressError'] = _("No from address specified.");
$lstr['NoSMTPHostError'] = _("No SMTP host specified.");
$lstr['NoSMTPPortError'] = _("No SMTP port specified.");

$lstr['EmailTestPageTitle'] = _("Test Email Settings");
$lstr['EmailTestPageHeader'] = _("Test Email Settings");
$lstr['EmailTestPageMessage'] = _("Use this to test your mail settings.");
$lstr['SendTestEmailButton'] = _("Send Test Email");

$lstr['NoPerformanceGraphsFoundForServiceText'] = _("No performance graphs were found for this service. If you have just started monitoring this object then it may take up to 15 minutes for the performance graphs to appear.");
$lstr['NoPerformanceGraphsFoundForHostText'] = _("No performance graphs were found for this host.");

$lstr['ServiceDetailsOverviewTab'] = _("Overview");
$lstr['ServiceDetailsAdvancedTab'] = _("Advanced");
$lstr['ServiceDetailsConfigureTab'] = _("Configure");
$lstr['ServiceDetailsPerformanceGraphsTab'] = _("Performance Graphs");

$lstr['HostDetailsOverviewTab'] = _("Overview");
$lstr['HostDetailsAdvancedTab'] = _("Advanced");
$lstr['HostDetailsConfigureTab'] = _("Configure");
$lstr['HostDetailsPerformanceGraphsTab'] = _("Performance Graphs");

$lstr['MonitoringProcessPageTitle'] = _("Monitoring Process");
$lstr['MonitoringProcessPageHeader'] = _("Monitoring Process");

$lstr['MonitoringPerformancePageTitle'] = _("Monitoring Performance");
$lstr['MonitoringPerformancePageHeader'] = _("Monitoring Performance");


$lstr['AcknowledgementCommentBoxText'] = _("Your Comment");

$lstr['NetworkOutagesPageTitle'] = _("Network Outages");
$lstr['NetworkOutagesPageHeader'] = _("Network Outages");

$lstr['ViewHostgroupOverviewAlt'] = _("View Hostgroup Overview");
$lstr['ViewHostgroupSummaryAlt'] = _("View Hostgroup Summary");
$lstr['ViewHostgroupGridAlt'] = _("View Hostgroup Grid");
$lstr['ViewHostgroupServiceStatusAlt'] = _("View Hostgroup Service Details");
$lstr['ViewHostgroupCommandsAlt'] = _("View Hostgroup Commands");

$lstr['ViewServicegroupOverviewAlt'] = _("View Servicegroup Overview");
$lstr['ViewServicegroupSummaryAlt'] = _("View Servicegroup Summary");
$lstr['ViewServicegroupGridAlt'] = _("View Servicegroup Grid");
$lstr['ViewServicegroupServiceStatusAlt'] = _("View Servicegroup Service Details");
$lstr['ViewServicegroupCommandsAlt'] = _("View Servicegroup Commands");

$lstr['StatusMapPageTitle'] = _("Network Status Map");
$lstr['StatusMapPageHeader'] = _("Network Status Map");

$lstr['ViewStatusMapTreeAlt'] = _("View Tree Map");
$lstr['ViewStatusMapBalloonAlt'] = _("View Balloon Map");

$lstr['CommentsPageTitle'] = _("Acknowledgements and Comments");
$lstr['CommentsPageHeader'] = _("Acknowledgements and Comments");

$lstr['ConfirmDeleteServicePageTitle'] = _("Confirm Service Deletion");
$lstr['ConfirmDeleteServicePageHeader'] = _("Confirm Service Deletion");
$lstr['ConfirmDeleteServicePageNotes'] = _("Are you sure you want to delete this service and remove it from the monitoring configuration?");

$lstr['DeleteServiceErrorPageTitle'] = _("Service Deletion Error");
$lstr['DeleteServiceErrorPageHeader'] = _("Service Deletion Error");

$lstr['ServiceDeleteScheduledPageTitle'] = _("Service Deletion Scheduled");
$lstr['ServiceDeleteScheduledPageHeader'] = _("Service Deletion Scheduled");

$lstr['ConfirmDeleteHostPageTitle'] = _("Confirm Host Deletion");
$lstr['ConfirmDeleteHostPageHeader'] = _("Confirm Host Deletion");
$lstr['ConfirmDeleteHostPageNotes'] = _("Are you sure you want to delete this host and remove it from the monitoring configuration?");

$lstr['DeleteHostErrorPageTitle'] = _("Host Deletion Error");
$lstr['DeleteHostErrorPageHeader'] = _("Host Deletion Error");

$lstr['HostDeleteScheduledPageTitle'] = _("Host Deletion Scheduled");
$lstr['HostDeleteScheduledPageHeader'] = _("Host Deletion Scheduled");

$lstr['CreateUserAsContactBoxTitle'] = _("Create as Monitoring Contact");

$lstr['UserIsNotContactNotificationPrefsErrorMessage'] = _("Management of notification preferences is not available because your account is not configured to be a monitoring contact.  Contact your Nagios XI administrator for details.");
$lstr['UserIsNotContactNotificationMessagesErrorMessage'] = _("Management of notification preferences is not available for your account.  Contact your Nagios XI administrator for details.");
$lstr['UserIsNotContactNotificationTestErrorMessage'] = _("Testing notification messages is not available for your account.  Contact your Nagios XI administrator for details.");

$lstr['ReconfigureServicePageTitle'] = _("Configure Service");
$lstr['ReconfigureServicePageHeader'] = _("Configure Service");

$lstr['ReconfigureServiceCompletePageTitle'] = _("Configure Service");
$lstr['ReconfigureServiceCompletePageHeader'] = _("Configure Service");

$lstr['ReconfigureHostPageTitle'] = _("Configure Host");
$lstr['ReconfigureHostPageHeader'] = _("Configure Host");

$lstr['ReconfigureHostCompletePageTitle'] = _("Configure Host");
$lstr['ReconfigureHostCompletePageHeader'] = _("Configure Host");

$lstr['ReconfigureServiceSuccessSectionTitle'] = _("Service Re-Configuration Successful");
$lstr['ReconfigureServiceSuccessNotes'] = _("The service has successfully been re-configured with the new settings.");

$lstr['ReconfigureServiceErrorSectionTitle'] = _("Service Re-Configuration Failed");
$lstr['ReconfigureServiceErrorNotes'] = _("A failure occurred while attempting to re-configure the service with the new settings.");


$lstr['ReconfigureHostSuccessSectionTitle'] = _("Host Re-Configuration Successful");
$lstr['ReconfigureHostSuccessNotes'] = _("The host has successfully been re-configured with the new settings.");

$lstr['ReconfigureHostErrorSectionTitle'] = _("Host Re-Configuration Failed");
$lstr['ReconfigureHostErrorNotes'] = _("A failure occurred while attempting to re-configure the host with the new settings.");

$lstr['UpdatesPageTitle'] = _("Updates");
$lstr['UpdatesPageHeader'] = _("Updates");
$lstr['UpdatesPageNotes'] = _("Ensure your IT infrastructure is monitored effectively by keeping up with the latest updates to Nagios XI.  Visit <a href='https://www.nagios.com/products/nagiosxi/' target='_blank'>www.nagios.com</a> to get the latest versions of Nagios XI.");

$lstr['NotificationMethodsPageTitle'] = _("Notification Methods");
$lstr['NotificationMethodsPageHeader'] = _("Notification Methods");
$lstr['NotificationMethodsMessage'] = _("Select the methods by which you'd like to receive host and service alerts.");

$lstr['NotificationsMethodsUpdatedText'] = _("Notification methods updated.");

$lstr['BuiltInNotificationMethodsSectionTitle'] = _("Built-In Notification Methods");
$lstr['AdditionalNotificationMethodsSectionTitle'] = _("Additional Notification Methods");

$lstr['NotificationMethodEmailTitle'] = _("Email");
$lstr['NotificationMethodEmailDescription'] = _("Receive alerts via email.");

$lstr['NotificationMobileTextMessageTitle'] = _("Mobile Phone Text Message");
$lstr['NotificationMobileTextMessageDescription'] = _("Receive text alerts to your cellphone.");

$lstr['NoAdditionalNotificationMethodsInstalledNote'] = _("No additional notification methods have been installed or enabled by the administrator.");


$lstr['UpgradeButton'] = _("Finish Upgrade");

$lstr['UpgradePageTitle'] = _("Upgrade");
$lstr['UpgradePageHeader'] = _("Upgrade");
$lstr['UpgradePageMessage'] = _("Your Nagios XI instance requires some modifications to complete the upgrade process.  Don't worry - its easy.");

$lstr['UpgradeCompletePageTitle'] = _("Upgrade Complete");
$lstr['UpgradeCompletePageHeader'] = _("Upgrade Complete");
$lstr['UpgradeCompletePageMessage'] = _("Congratulations!  Your Nagios XI upgrade has completed successfully.");

$lstr['RecurringDowntimePageTitle'] = _("Recurring Scheduled Downtime");
$lstr['RecurringDowntimePageHeader'] = _("Recurring Scheduled Downtime");
$lstr['RecurringDowntimePageNotes'] = _("Scheduled downtime definitions that are designed to repeat (recur) at set intervals are shown below.  The next schedule for each host/service are added to the monitoring engine when the cron runs at the top of the hour.");

$lstr['DowntimePageTitle'] = _("Scheduled Downtime");
$lstr['DowntimePageHeader'] = _("Scheduled Downtime");

$lstr['ConfigPermsCheckPageTitle'] = _("Config File Permissions Check");
$lstr['ConfigPermsCheckPageHeader'] = _("Config File Permissions Check");

$lstr['TacPageTitle'] = _("Tactical Overview");
$lstr['TacPageHeader'] = _("Tactical Overview");

$lstr['MobileCarriersPageTitle'] = _("Mobile Carriers");
$lstr['MobileCarriersPageHeader'] = _("Mobile Carriers");
$lstr['MobileCarriersPageMessage'] = _("Manage the mobile carrier settings that can be used for email-to-text mobile notifications.  Note: The <i>%number%</i> macro in the address format will be replaced with the user's phone number.");
$lstr['MobileCarriersUpdatedText'] = _("Mobile carriers updated.");

$lstr['DataTransferPageTitle'] = _("Check Data Transfer");
$lstr['DataTransferPageHeader'] = _("Check Data Transfer");
$lstr['DataTransferOverviewPageNotes'] = _("Configure settings for transferring host and service check results to and from this Nagios XI server.");

$lstr['OutboundDataTransferPageTitle'] = _("Outbound Check Transfer Settings");
$lstr['OutboundDataTransferPageHeader'] = _("Outbound Check Transfer Settings");

$lstr['InboundDataTransferPageTitle'] = _("Inbound Check Transfer Settings");
$lstr['InboundDataTransferPageHeader'] = _("Inbound Check Transfer Settings");

$lstr['ToolsPageTitle'] = _("Tools");

$lstr['PerformanceSettingsPageTitle'] = _("Performance Settings");
$lstr['PerformanceSettingsPageHeader'] = _("Performance Settings");
$lstr['PerformanceSettingsUpdatedText'] = _("Performance settings updated");

$lstr['DashletRefreshMultiplierText'] = _("Dashlet Refresh Multiplier");

$lstr['MyReportsPageTitle'] = _("My Reports");
$lstr['MyReportsPageHeader'] = _("My Reports");

$lstr['AddToMyReportsPageTitle'] = _("Add Report");
$lstr['AddToMyReportsPageHeader'] = _("Add Report");

$lstr['SaveReportButton'] = _("Save Report");

$lstr['ActivationPageTitle'] = _("Product Activation");
$lstr['ActivationPageHeader'] = _("Product Activation");
$lstr['ActivationPageMessage'] = _("<p>You must activate your license key in order to access certain features of Nagios XI.  You can obtain an activation code at <a href='https://www.nagios.com/activate/' target='_blank'>http://www.nagios.com/activate</a></p>");

$lstr['ActivationKeySectionTitle'] = _("Activation Information");
$lstr['ActivationKeyText'] = _("Activation Key");
$lstr['InvalidActivationKeyError'] = _("Invalid activation key.");

$lstr['LicenseActivationSectionTitle'] = _("License Activation");
$lstr['ActivationKeyUpdatedText'] = _("Activation key accepted.  Thank you!");

$lstr['ActivateKeyButton'] = _("Activate");

$lstr['MissingObjectsPageTitle'] = _("Unconfigured Objects");
$lstr['MissingObjectsPageHeader'] = _("Unconfigured Objects");

$lstr['AutoLoginPageTitle'] = _("Automatic Login");
$lstr['AutoLoginPageHeader'] = _("Automatic Login");
$lstr['AutoLoginPageNotes'] = _("These options allow you to configure a user account that should be used to automatically login visitors.  Visitors can logout of the default account and into their own if they wish.");
$lstr['AutoLoginButton'] = _("Auto-Login");

$lstr['OptionsUpdatedText'] = _("Options updated.");

$lstr['FinishButton'] = _("Finish");

$lstr['MIBsPageTitle'] = _("SNMP MIBs");
$lstr['MIBsPageHeader'] = _("SNMP MIBs");
$lstr['MIBsPageNotes'] = _("Manage the SNMP MIBs installed on this server.");

$lstr["UploadNewMIBBoxText"] = _("Upload A New MIB");
$lstr['UploadMIBButton'] = _("Upload MIB");
$lstr['MIBUploadedText'] = _("New MIB was installed successfully.");
$lstr['MIBUploadFailedText'] = _("MIB could not be installed - directory permissions may be incorrect.");
$lstr['MIBDeletedText'] = _("MIB deleted.");
$lstr['MIBDeleteFailedText'] = _("MIB delete failed - directory permissions may be incorrect.");
$lstr['NoMIBUploadedText'] = _("No MIB selected for upload.");
$lstr['MIBTableHeader'] = _("MIB");

$lstr['GraphTemplatesPageTitle'] = _("Graph Templates");
$lstr['GraphTemplatesPageHeader'] = _("Graph Templates");
$lstr['GraphTemplatesPageNotes'] = _("Manage the templates used to generate performance graphs.");

$lstr["UploadNewGraphTemplateBoxText"] = _("Upload A New Template");
$lstr['UploadGraphTemplateButton'] = _("Upload Template");
$lstr['GraphTemplateUploadedText'] = _("New graph template was installed successfully.");
$lstr['GraphTemplateUploadFailedText'] = _("Graph template could not be installed - directory permissions may be incorrect.");
$lstr['GraphTemplateDeletedText'] = _("Graph template deleted.");
$lstr['GraphTemplateDeleteFailedText'] = _("Graph template delete failed - directory permissions may be incorrect.");
$lstr['NoGraphTemplateUploadedText'] = _("No template selected for upload.");
$lstr['GraphTemplateDirTableHeader'] = _("Directory");

$lstr['EditGraphTemplatePageTitle'] = _("Edit Template");
$lstr['EditGraphTemplatePageHeader'] = _("Edit Template");
$lstr['EditGraphTemplatePageNotes'] = "";

$lstr['SaveButton'] = _("Save");
$lstr['ApplyButton'] = _("Apply");

$lstr['FileWriteErrorText'] = _("Error writing to file.");
$lstr['FileSavedText'] = _("File saved successfully.");

$lstr['MyToolsPageTitle'] = _("My Tools");
$lstr['MyToolsPageHeader'] = _("My Tools");

$lstr['AddToMyToolsPageTitle'] = _("Add Tool");
$lstr['AddToMyToolsPageHeader'] = _("Add Tool");

$lstr['EditMyToolsPageTitle'] = _("Edit Tool");
$lstr['EditMyToolsPageHeader'] = _("Edit Tool");


$lstr['ToolsPageTitle'] = _("Tools");
$lstr['ToolsPageHeader'] = _("Tools");


$lstr['CommonToolsPageTitle'] = _("Common Tools");
$lstr['CommonToolsPageHeader'] = _("Common Tools");

$lstr['AddToCommonToolsPageTitle'] = _("Add Tool");
$lstr['AddToCommonToolsPageHeader'] = _("Add Tool");

$lstr['EditCommonToolsPageTitle'] = _("Edit Tool");
$lstr['EditCommonToolsPageHeader'] = _("Edit Tool");

$lstr['SchedulePageAlt'] = _("Schedule Page");

$lstr['ConfigSnapshotRestoredText'] = _("Configuration snapshot restored.");
$lstr['ConfigSnapshotScheduledForRestoreText'] = _("Configure snapshot restore has been scheduled.");

$lstr['RestoreAlt'] = _("Restore");

$lstr['ShowHandled'] = _("Show Handled");
$lstr['HideHandled'] = _("Hide Handled");

