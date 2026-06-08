<?php
/*
 * Copyright (c) 2026, Tribal Limited
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



//A small little wrapper class for prepared statements
class preparedStatement {
	
	private $db;
	private $sql;
	private $types;
	private $statement;
	private $asciis = [];
	private $likes = [];
	private $cache = [];
	
	public function __construct($db, $sql, $types) {
		
		$len = strlen($types);
		for ($i = 0; $i < $len; ++$i) {
			switch ($types[$i]) {
				case 'a':
					$types[$i] = 's';
					$this->asciis[] = $i;
					break;
				case 'l':
					$types[$i] = 's';
					$this->likes[] = $i;
					break;
			}
		}
		
		$this->db = $db;
		$this->sql = $sql;
		$this->types = $types;
		$this->statement = $db->con->prepare($sql);
		
		if ($this->statement === false) {
			\ze\db::handleError($db->con, $sql);
		}
	}
	
	public function execute($values) {
		
		if ($this->asciis !== []) {
			foreach ($this->asciis as $i) {
				$values[$i] = \ze\escape::ascii($values[$i]);
			}
		}
		if ($this->likes !== []) {
			foreach ($this->likes as $i) {
				$values[$i] = \ze\escape::like($values[$i]);
			}
		}
		
		$this->statement->bind_param($this->types, ...$values);
		
		return $this->statement->execute();
	}
	
	public function select($values) {
		
		$this->execute($values);
		$result = $this->statement->get_result();
		
		if ($result) {
			return new queryCursor($this->db, $result);
	
		} else {
			\ze\db::handleError($this->db->con, $this->sql);
		}
	}
	
	public function update($values, $checkCache = true, $checkRevNo = true) {
		
		$result = $this->execute($values);
		
		if ($result) {
			if (!empty($this->db->con->insert_id)) {
				$return = $this->db->con->insert_id;
			} else {
				$return = true;
			}
		
			if ($checkRevNo && $this->db->con->affected_rows) {
				\ze\db::updateDataRevisionNumber();
		
				if ($checkCache) {
					$ids = $assocValues = false;
					$this->db->reviewQueryForChanges($this->sql, $ids, $assocValues);
				}
			}
			return $return;
	
		} else {
			\ze\db::handleError($this->db->con, $this->sql);
		}
	}
	
	//Run an update that doesn't need to clear the cache or data revision numbers
	public function cacheFriendlyUpdate($values) {
		return $this->update($values, false, false);
	}
	
	
	
	
	

	//Replacement for mysql_affected_rows()
	public function affectedRows() {
		return $this->db->con->affected_rows;
	}

	//Replacement for mysql_fetch_assoc()
	public function fetchAssoc($values, $useCache = false) {
		if ($useCache) {
			$key = '1'. json_encode($values);
			
			if (!isset($this->cache[$key])) {
				$this->cache[$key] = $this->fetchAssoc($values, false);
			}
			return $this->cache[$key];
		}
		
		$result = $this->select($values);
		return $result->fAssoc();
	}

	//Replacement for mysql_fetch_row()
	public function fetchRow($values, $useCache = false) {
		if ($useCache) {
			$key = '2'. json_encode($values);
			
			if (!isset($this->cache[$key])) {
				$this->cache[$key] = $this->fetchRow($values, false);
			}
			return $this->cache[$key];
		}
		
		$result = $this->select($values);
		return $result->fRow();
	}

	//Replacement for mysql_num_rows()
	public function numRows($values) {
		$result = $this->select($values);
		return $result->q->num_rows;
	}
	
	public function exists($values, $useCache = false) {
		if ($useCache) {
			$key = '3'. json_encode($values);
			
			if (!isset($this->cache[$key])) {
				$this->cache[$key] = $this->exists($values, false);
			}
			return $this->cache[$key];
		}
		
		$result = $this->select($values);
		return (bool) $result->q->num_rows;
	}

	//Fetch just one value from a SQL query
	public function fetchValue($values, $useCache = false) {
		if ($useCache) {
			$key = '4'. json_encode($values);
			
			if (!isset($this->cache[$key])) {
				$this->cache[$key] = $this->fetchValue($values, false);
			}
			return $this->cache[$key];
		}
		
		if ($row = $this->fetchRow($values)) {
			return $row[0];
		} else {
			return false;
		}
	}
	
	

	//Fetch multiple values from a SQL query (one column, multiple rows)
	public function fetchValues($values, $indexBySecondColumn = false, $useCache = false) {
		if ($useCache) {
			$key = '5'. json_encode($indexBySecondColumn). json_encode($values);
			
			if (!isset($this->cache[$key])) {
				$this->cache[$key] = $this->fetchValues($values, $indexBySecondColumn, false);
			}
			return $this->cache[$key];
		}
		
		$out = [];
		$result = $this->select($values);
		while ($row = $result->fRow()) {
			if ($indexBySecondColumn) {
				$out[$row[1]] = $row[0];
			} else {
				$out[] = $row[0];
			}
		}
		return $out;
	}

	//Fetch multiple values from a SQL query (multiple columns, multiple rows)
	public function fetchAssocs($values, $indexBy = false, $useCache = false) {
		if ($useCache) {
			$key = '6'. json_encode($indexBy). json_encode($values);
			
			if (!isset($this->cache[$key])) {
				$this->cache[$key] = $this->fetchAssocs($values, $indexBy, false);
			}
			return $this->cache[$key];
		}
		
		$out = [];
		$result = $this->select($values);
		while ($row = $result->fAssoc()) {
			if ($indexBy === false) {
				$out[] = $row;
			} else {
				$out[$row[$indexBy]] = $row;
			}
		}
		return $out;
	}
	public function fetchRows($values, $useCache = false) {
		if ($useCache) {
			$key = '7'. json_encode($values);
			
			if (!isset($this->cache[$key])) {
				$this->cache[$key] = $this->fetchRows($values, false);
			}
			return $this->cache[$key];
		}
		
		$out = [];
		$result = $this->select($values);
		while ($row = $result->fRow()) {
			$out[] = $row;
		}
		return $out;
	}


	
		
}