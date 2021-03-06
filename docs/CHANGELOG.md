EPESI CHANGELOG
===============

RELEASE 1.5.5-20140113
----------------------
- Fix recurrent meeting issue in Activities tab - [Forum thread](http://forum.epesibim.com/viewtopic.php?f=6&t=2023)
- Fix "Paste company data" button - [Forum thread](http://forum.epesibim.com/viewtopic.php?f=6&t=2026)
- Add option to use "Reply-to" header in SMTP settings
- Fix BBCode url matching
- Remove ckeditor's internal save button, that wasn't used
- Fix moving notes - some rare issue with directories
- Fix deleting files upon note removal
- Update RoundCube version to 0.9.5
- Fix dashboard's tab management
- Fix RecordBrowserReports column summary to not show last row doubled
- Fix wrong time and date in mobile view - [Forum thread](http://forum.epesibim.com/viewtopic.php?f=6&t=1925)
- Add new possible Home Page - company of current user
- Check access when copying company data into contact
- Clean up include path
- Fix creating new contact - [Forum thread](http://forum.epesibim.com/viewtopic.php?f=6&t=2082)
- Fix calendar event with duration less than 1h
- Several fixes for PostgreSQL engine
- Fix broken Contact's template (#2)
- Fix printing all records from RecordBrowser
- Fix watchdog email notifications (#3)
- Update translations

RELEASE 1.5.4-rev11060 (20131015)
---------------------------------
- update translations
- bugfixes to problems reported since original 1.5.4 release

RELEASE 1.5.4-rev11044 (20131014)
---------------------------------
- RoundCube 0.9.4
    **Warning** New RoundCube client requires PDO extension enabled in php.ini and PHP 5.2.1 or greater. When using MySQL database it **requires PHP version 5.3 or higher**
- fixed bugs in RecordBrowser and Attachments
- changed admin view for currencies
- do not report E_DEPRECATED errors - PHP 5.5.x [deprecates some features](http://php.net/manual/en/migration55.deprecated.php) used by Smarty templating engine
- EPESI - RoundCube archiving fixes
- RoundCube imap cache fixes
- fix RecordBrowser's field edit error when param is empty
- use reply-to header as default when sending emails from EPESI
- fix time issues in mobile view - [Forum thread](http://forum.epesibim.com/viewtopic.php?f=6&t=1925#p7132)
- improve CSV export

RELEASE 1.5.3-rev10944 (20130709)
---------------------------------
- fix calendar month view in certain timezone configuration - [forum thread](http://forum.epesibim.com/viewtopic.php?f=6&t=1523&p=5959#p5959)
- fix adding new record - rare issue
- add patch to create one of the ban variables - sometimes after installation admin could get error "undefined variable"
- fix template html for launchpad
- fix deprecated hook name in RoundCube EPESI plugin
- fix leightbox js issues
- fix searching for a lot of records
- sort meetings in activities tab
- fix issues with field names in record's history
- add filtering for currency field
- RBO - add set_style method for field definition, add get_access method to recordset
- fix add note from table view and record view - [forum thread](http://forum.epesibim.com/viewtopic.php?f=6&t=1760)
- updated translations

RELEASE 1.5.2-rev10766 (20130513)
---------------------------------
- Full version of CKEditor included.
- Fixed bugs:
    - commondata field created by user was causing error during search - [Forum thread](http://forum.epesibim.com/viewtopic.php?f=6&t=1678)
    - tooltips in calendar events were broken - [Polish forum thread](http://forum.epesibim.com/viewtopic.php?f=25&t=1685)
    - print browse mode of company or contact field didn't indicate record type.
- Icon of company or contact field has been changed to text indicator ([Company] / [Contact]) in some places. It's related to third bug listed above.

RELEASE 1.5.1-rev10757 (20130508)
---------------------------------
- A new version of CKEditor
- Fixed bug in Utils/Attachments - user was unable to edit note using Firefox.
    Now notes edit box is always on top of the notes.
- Updated translations

RELEASE 1.5.0-rev10738 (20130424)
-------------------------------
USER PERSPECTIVE
- new RoundCube email client
- new CKEditor version - modern look & feel
- click2fill appearance and help improvements
- multiple attachments per note
- shoutbox improvements - click to address person, changed user labels, tab+enter to send
- company or contact suggestbox - show icon based on type, always display several records from both recordsets
- watchdog - subscribe to categories (by default only for managers)
- sort mails archived in EPESI by thread

ADMIN PERSPECTIVE
- User ban system improvements and restore controls in administrator panel
- add option to disable EPESI store to faster module administration launch
- changed install process - allow translating from first screen
- allow run /admin tools before Base installation
- add option to set security in smtp server settings
- improved RecordBrowser fields administration
- changed HomePage mechanism - allow to set default home page for specific group of users
- link from Administrator panel to /admin tools
- add EPESI shell in /admin tools - disabled by default
- add patch utility in /admin tools

DEVELOPERS PERSPECTIVE
- RecordBrowser - allow disable "jump to record"
- RecordBrowser - add autonumbering field type
- new types for RBO - company, contact, employee, company or contact, email, time, phone
- allow to translate strings from smarty templates

SYSTEM
- RoundCube 0.8.2 with several EPESI integration fixes
- CKEditor 4.0.2
- optimize startup time
- allow to translate /admin tools
- interactive help system
- fixed automulti suggestboxes to display all selected fields
- attachments bug fixes
- display errors by default (config.php)
- RecordBrowser - fix permission check issues
- fix search engine for contacts and companies
- partial rewrite to jQuery (we are going to remove Prototype)
- several PostgreSQL fixes (thanks to forum user - pedro42)
- fixed EpesiStore on PHP 5.2.6 - [php.net](https://bugs.php.net/bug.php?id=45028)
- add option to store session in files instead of database
- appearance bug fixes
- translations improved - more string have been marked to translate
- clean up some parts of code

IMPORTANT NOTES
- PHP 5.2.0 is not supported due to bug in json_decode function. (PHP >= 5.2.1 and PHP < 5.2.0 works)