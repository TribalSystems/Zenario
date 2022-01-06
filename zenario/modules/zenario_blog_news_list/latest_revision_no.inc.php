<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


//The number in this file is the minor revision number of your module:
define(ze::moduleName(__FILE__). '_LATEST_REVISION_NO', 5);


//Most of the information in the description.yaml file is only read once, on the
//installation of your module.

//If you have made changes to your description.yaml file, you can force them to be
//reread by increasing the minor revision number of your module by incrementing the
//number above. When you next log in to the CMS in Admin mode, the CMS will see that
//the current revision is out of date, and reload your module's description.

//This revision number is also used in any SQL update files that you write. If you
//want to add more SQL updates to a file, you should incrementing the number above and
//use the new number when calling the ze\dbAdm::revision() function.