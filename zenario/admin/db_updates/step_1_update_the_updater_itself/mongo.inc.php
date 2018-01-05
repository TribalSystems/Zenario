<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');



//Create the local_revision_numbers collection in MongoDB for recording what patches have been applied to the database,
//and add a unique key (this is just so it has the same definition as the MySQL version of this table).
if (ze\dbAdm::needRevision(1)) {
	
	$local_revision_numbers = ze\mongo::collection('local_revision_numbers');

	$local_revision_numbers->createIndex(
		['path' => 1, 'patchfile' => 1],
		['unique' => true]
	);
	unset($local_revision_numbers);
	
	ze\dbAdm::revision(1);
}

