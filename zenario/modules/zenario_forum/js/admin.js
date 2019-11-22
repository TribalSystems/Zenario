/*
 * Copyright (c) 2019, Tribal Limited
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
(function(zenario, zenarioA, zenario_forum, undefined) {


zenario_forum.slotName = false;
zenario_forum.threadId = false;
zenario_forum.key = false;

zenario_forum.moveThread = function(slotName, choosePhrase, forumId, threadId, key) {
	zenario_forum.slotName = slotName;
	zenario_forum.threadId = threadId;
	zenario_forum.key = key;
	
	zenarioA.organizerSelect('zenario_forum', 'doMoveThread', false, 'zenario__social/nav/forums/panel/refiners/exclude_forum//' + forumId + '//', 'zenario__social/nav/forums/panel', 'zenario__social/nav/forums/panel', 'zenario__social/nav/forums/panel', true, true, choosePhrase);
}

zenario_forum.doMoveThread = function(path, key, row) {
	
	var cID, cType, cTypeAndCID = ('' + row.forum_link).split('_');
	
	if ((cType = cTypeAndCID[0]) && (cID = cTypeAndCID[1])) {
		zenario_forum.goToItem(cID, cType, {comm_request: 'move_thread', forum_thread_to_move: zenario_forum.threadId, comm_key: zenario_forum.key});
	}
}



})(zenario, zenarioA, zenario_forum);