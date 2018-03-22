<?php
/*
 * Copyright (c) 2018, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace ze;




class mongo {



	//Wrapper functions for the two different PHP MongoDB libraries
	//
	//Warning! The inputs to these functions are in the following order:
	//collection, columns, ids, sort
	//This is to be consistent with the rest of the Zenario database API functions,
	//but is not consistent with the normal order in MongoDB's functions

	const escapeKeyFromTwig = true;
	//Formerly "mongoEscapeKey()"
	public static function escapeKey($key) {
		return str_replace('.', '~', $key);
	}

	const unescapeKeyFromTwig = true;
	//Formerly "mongoUnescapeKey()"
	public static function unescapeKey($key) {
		return str_replace('~', '.', $key);
	}

	//Connect to MongoDB and return a pointer to a collection
	//Formerly "mongoCollection()"
	public static function collection($collection, $returnFalseOnError = false) {
	
		//Connect to MongoDB if we haven't already
		if (!isset(\ze::$mongoDB)) {
		
			//If the connection details were not defined, default to localhost/the default port/no username or password
			if (!defined('MONGODB_CONNECTION_URI')) {
				define('MONGODB_CONNECTION_URI', 'mongodb://localhost:27017');
			}
		
			if (!defined('MONGODB_DBNAME')) {
				if ($returnFalseOnError) {
					return false;
				} else {
					\ze\db::reportDatabaseErrorFromHelperFunction('The MONGODB_DBNAME constant was not defined in the zenario_siteconfig.php file.');
					exit;
				}
	
			} elseif (class_exists('MongoDB\Driver\Manager')) {
				//new logic for PHP 7
				$mongoClient = new \MongoDB\Client(MONGODB_CONNECTION_URI);
				\ze::$mongoDB = $mongoClient->{MONGODB_DBNAME};
		
			} else {
				if ($returnFalseOnError) {
					return false;
				} else {
					\ze\db::reportDatabaseErrorFromHelperFunction('The MongoDB PHP extension is not installed.');
					exit;
				}
			}
		}
	
		return \ze::$mongoDB->{$collection};
	}

	//Formerly "zenarioMongoParseInputs()"
	public static function parseInputs(&$collection, &$ids) {
		if (is_string($collection)) {
			$collection = \ze\mongo::collection($collection);
		}
		if (!is_array($ids) && !empty($ids)) {
			$ids = ['_id' => $ids];
		}
	}

	const countFromTwig = true;
	//Get a COUNT(*) of rows
	//Formerly "mongoCount()"
	public static function count($collection, $ids = []) {
		\ze\mongo::parseInputs($collection, $ids);
		return $collection->count($ids);
	}

	//Run a query on a collection
	//Formerly "mongoFind()"
	public static function find($collection, $cols = [], $ids = [], $sort = null, $limit = 0, $queryOptions = []) {
	
		\ze\mongo::parseInputs($collection, $ids);
		if (!empty($sort) && is_string($sort)) {
			if ($sort[0] == '-') {
				$sort = [substr($sort, 1) => -1];
			} else {
				$sort = [$sort => 1];
			}
		}
	
		if (is_array($cols) && $cols !== []) {
			$queryOptions['projection'] = $cols;
		}
		if (isset($limit)) {
			$queryOptions['limit'] = $limit;
		}
		if (isset($sort)) {
			$queryOptions['sort'] = $sort;
		}
	
		$cursor = $collection->find($ids, $queryOptions);
		$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
	
		try {
			$IteratorIterator = new \IteratorIterator($cursor);
			$IteratorIterator->rewind();
			return $IteratorIterator;
	
		} catch (\Exception $e) {
			$obj = new \ArrayObject([]);
			return $obj->getIterator();
		}
	}

	const findOneFromTwig = true;
	//Run a query on a collection, returning just one row or one property value
	//Formerly "mongoFindOne()"
	public static function findOne($collection, $cols = [], $ids = [], $sort = null, $queryOptions = []) {
	
		$col = false;
		if (is_array($cols) || $cols === true) {
			$col = false;
		} else {
			$col = $cols;
			$cols = [$col => 1];
		}
	
		$row = \ze\mongo::fetchRow(\ze\mongo::find($collection, $cols, $ids, $sort, 1, $queryOptions));
	
		if ($col === false) {
			return $row;
		} else {
			return ($row[$col] ?? false);
		}
	}

	//Formerly "mongoUpdateOne()"
	public static function updateOne($collection, $update, $ids, $queryOptions = []) {
		\ze\mongo::parseInputs($collection, $ids);
		$collection->updateOne($ids, $update, $queryOptions);
	}
	
	//Formerly "mongoUpdateMany()"
	public static function updateMany($collection, $update, $ids = [], $queryOptions = []) {
		\ze\mongo::parseInputs($collection, $ids);
		$collection->updateMany($ids, $update, $queryOptions);
	}

	//Formerly "mongoDeleteOne()"
	public static function deleteOne($collection, $ids, $queryOptions = []) {
		\ze\mongo::parseInputs($collection, $ids);
		$collection->deleteOne($ids, $queryOptions);
	}
	
	//Formerly "mongoDeleteMany()"
	public static function deleteMany($collection, $ids = [], $queryOptions = []) {
		\ze\mongo::parseInputs($collection, $ids);
		$collection->deleteMany($ids, $queryOptions);
	}

	//Fetch a row from a cursor returned by \ze\mongo::find()
	//Formerly "mongoFetchRow()"
	public static function fetchRow($cursor) {
		if ($row = $cursor->current()) {
			$cursor->next();
			return $row;
		}
	
		return false;
	}


}