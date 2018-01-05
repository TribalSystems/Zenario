

/**
 * Helper for implementing retries with backoff. Initial retry
 * delay is 1 second, increasing by 2x (+jitter) for subsequent retries
 *
 * @constructor
 */
var RetryHandler = function() {
  this.interval = 1000; // Start at one second
  this.maxInterval = 60 * 1000; // Don't wait longer than a minute 
};

/**
 * Invoke the function after waiting
 *
 * @param {function} fn Function to invoke
 */
RetryHandler.prototype.retry = function(fn) {
  setTimeout(fn, this.interval);
  this.interval = this.nextInterval_();
};

/**
 * Reset the counter (e.g. after successful request.)
 */
RetryHandler.prototype.reset = function() {
  this.interval = 1000;
};

/**
 * Calculate the next wait time.
 * @return {number} Next wait interval, in milliseconds
 *
 * @private
 */
RetryHandler.prototype.nextInterval_ = function() {
  var interval = this.interval * 2 + this.getRandomInt_(0, 1000);
  return Math.min(interval, this.maxInterval);
};

/**
 * Get a random int in the range of min to max. Used to add jitter to wait times.
 *
 * @param {number} min Lower bounds
 * @param {number} max Upper bounds
 * @private
 */
RetryHandler.prototype.getRandomInt_ = function(min, max) {
  return Math.floor(Math.random() * (max - min + 1) + min);
};


/**
 * Helper class for resumable uploads using XHR/CORS. Can upload any Blob-like item, whether
 * files or in-memory constructs.
 *
 * @example
 * var content = new Blob(["Hello world"], {"type": "text/plain"});
 * var uploader = new MediaUploader({
 *   file: content,
 *   token: accessToken,
 *   onComplete: function(data) { ... }
 *   onError: function(data) { ... }
 * });
 * uploader.upload();
 *
 * @constructor
 * @param {object} options Hash of options
 * @param {string} options.token Access token
 * @param {blob} options.file Blob-like item to upload
 * @param {string} [options.fileId] ID of file if replacing
 * @param {object} [options.params] Additional query parameters
 * @param {string} [options.contentType] Content-type, if overriding the type of the blob.
 * @param {object} [options.metadata] File metadata
 * @param {function} [options.onComplete] Callback for when upload is complete
 * @param {function} [options.onProgress] Callback for status for the in-progress upload
 * @param {function} [options.onError] Callback if upload fails
 */
var MediaUploader = function(options) {
  var noop = function() {};
  this.isCordovaApp = options.isCordovaApp;
  this.realUrl = options.realUrl;
  this.file = options.file;
  this.contentType = options.contentType || this.file.type || 'application/octet-stream';
  this.metadata = options.metadata || {
    'title': this.file.name,
    'mimeType': this.contentType
  };
  this.token = options.token;
  this.onComplete = options.onComplete || noop;
  this.onProgress = options.onProgress || noop;
  this.onError = options.onError || noop;
  this.offset = options.offset || 0;
  this.chunkSize = options.chunkSize || 0;
  this.retryHandler = new RetryHandler();

  this.url = options.url;

  if (!this.url) {
    var params = options.params || {};
    // params.uploadType = 'resumable';
    this.url = this.buildUrl_(options.fileId, params, options.baseUrl);
  }

  this.httpMethod = options.fileId ? 'PUT' : 'POST';

  this.currentXHR = null;
  this.chunkProgress = 0;
};

MediaUploader.prototype.destroy = function() {
  this.isCordovaApp = null;
  this.realUrl = null;
  this.file = null;
  this.contentType = null;
  this.metadata = null;
  this.token = null;
  this.onComplete = null;
  this.onProgress = null;
  this.onError = null;
  this.offset = null;
  this.chunkSize = null;
  this.retryHandler = null;
  this.url = null;
  this.httpMethod = null;

  this.currentXHR = null;
  this.chunkProgress = null;

  this.user = null;
  this.ticket_id = null;
  this.complete_url = null;
  this.token = null;

  this.offset = null;
}

/**
 * Initiate the upload (Get vimeo ticket number and upload url)
 */
MediaUploader.prototype.upload = function() {

  var xhr = new XMLHttpRequest();

  xhr.open(this.httpMethod, this.url, true);
  xhr.setRequestHeader('Authorization', 'Bearer ' + this.token);
  xhr.setRequestHeader('Content-Type', 'application/json');

  xhr.onload = function(e) {
    // get vimeo upload  url, user (for available quote), ticket id and complete url
    if (e.target.status < 400) {
      var response = JSON.parse(e.target.responseText);
      this.url = response.upload_link_secure;
      this.user = response.user;
      this.ticket_id = response.ticket_id;
      this.complete_url = "https://api.vimeo.com"+response.complete_uri;
      this.sendFile_();
    } else {
      this.onUploadError_(e);
    }
  }.bind(this);

  xhr.onerror = this.onUploadError_.bind(this);
  xhr.send(JSON.stringify({
    type:'streaming'
  }));

  this.currentXHR = xhr;
};

MediaUploader.prototype.abort = function() {
  this.currentXHR.abort()
  this.onError("Upload aborted");
};

/**
 * Send the actual file content.
 * New @ 16 June 2015
 * This has been modified to support Cordova FileTransfer plugin
 *
 * @private
 */
MediaUploader.prototype.sendFile_ = function() {
  var content = this.file;
  var end     = this.file.size;

  if (this.offset || this.chunkSize) {
    // Only bother to slice the file if we're either resuming or uploading in chunks
    if (this.chunkSize) {
      end = Math.min(this.offset + this.chunkSize, this.file.size);
    }
    content = content.slice(this.offset, end);
  }

  this.chunkProgress = end

  if(this.isCordovaApp) {
    // Read the video file, 
    var reader = new FileReader();

    reader.onloadend = function (evt) {
        this.send_(evt.target.result, end);
    }.bind(this);

    reader.readAsArrayBuffer(content);

  } else{
    this.send_(content, end); 
  }

};


/**
 * Send the file
 * Added @ 16 June 2015, .. 
 * @private
 */
MediaUploader.prototype.send_ = function(content, end) {
  self = this

  var xhr = new XMLHttpRequest();
  xhr.open('PUT', this.url, true);
  xhr.setRequestHeader('Content-Type', this.contentType);
  // xhr.setRequestHeader('Content-Length', this.file.size);
  xhr.setRequestHeader('Content-Range', "bytes " + this.offset + "-" + (end - 1) + "/" + this.file.size);

  if (xhr.upload) {
    // xhr.upload.addEventListener('progress', this.onProgress);
    xhr.upload.addEventListener('progress', function(xmlHttpRequestProgressEvent) {
      loaded = self.offset + xmlHttpRequestProgressEvent.loaded;
      total = self.file.size;
      self.onProgress(loaded, total);
    });
  }
  xhr.onload = this.onContentUploadSuccess_.bind(this);
  xhr.onerror = this.onContentUploadError_.bind(this);
  xhr.send(content);

  this.currentXHR = xhr
}

/**
 * Verify for the state of the file for completion.
 * Added @ 16 June 2015, .. 
 * @private
 */
MediaUploader.prototype.verify_ = function() {
  var xhr = new XMLHttpRequest();
  xhr.open('PUT', this.url, true);
  xhr.setRequestHeader('Content-Length', "0");
  xhr.setRequestHeader('Content-Range', "bytes */*");
  
  xhr.onload = function(e) {
    if (e.target.status == 200 || e.target.status == 201) {
      // console.log('verify success!!');
    } else if (e.target.status == 308) {    
      // console.log('status 308');
    }
    
  };

  xhr.onerror = function(e) {
    if (e.target.status && e.target.status < 500) {
      // console.log(e.target.response);
    } else {
      // Do nothing, 
    }
  };
  xhr.send();
  this.currentXHR = xhr
};

/**
 * Query for the state of the file for resumption.
 *
 * @private
 */
MediaUploader.prototype.resume_ = function() {
  self = this
  var xhr = new XMLHttpRequest();
  xhr.open('PUT', this.url, true);
  xhr.setRequestHeader('Content-Range', "bytes */" + this.file.size);
  xhr.setRequestHeader('X-Upload-Content-Type', this.file.type);
  if (xhr.upload) {
    // xhr.upload.addEventListener('progress', this.onProgress);
    xhr.upload.addEventListener('progress', function(xmlHttpRequestProgressEvent) {
      loaded = self.offset + xmlHttpRequestProgressEvent.loaded;
      total = self.file.size;
      self.onProgress(loaded, total);
    });
  }
  xhr.onload = this.onContentUploadSuccess_.bind(this);
  xhr.onerror = this.onContentUploadError_.bind(this);
  xhr.send();
  this.currentXHR = xhr
};

/**
 * The final step is to call vimeo.videos.upload.complete to queue up 
 * the video for transcoding. 
 *
 * If successful call 'onComplete'
 *
 * @private
 */
MediaUploader.prototype.complete_ = function() {

  var xhr = new XMLHttpRequest();
  xhr.open('DELETE', this.complete_url, true);
  xhr.setRequestHeader('Authorization', 'Bearer ' + this.token);

  xhr.onload = function(e) {

    // Get the video location (videoId)
    if (e.target.status < 400) {

      var location = e.target.getResponseHeader('Location');

      // Example of location: ' /videos/115365719', extract the video id only
      var video_id = location.split('/').pop();

      this.onComplete(video_id);

    } else {
      this.onCompleteError_(e);
    }
  }.bind(this);

  xhr.onerror = this.onCompleteError_.bind(this);
  xhr.send();
};

/**
 * Handle successful responses for uploads. Depending on the context,
 * may continue with uploading the next chunk of the file or, if complete,
 * invokes vimeo complete service.
 *
 * @private
 * @param {object} e XHR event
 */
MediaUploader.prototype.onContentUploadSuccess_ = function(e) {

  if (e.target.status == 200 || e.target.status == 201 || e.target.status == 308) {
    this.offset = this.chunkProgress;
  
    if (this.offset == this.file.size) {
      this.complete_();
    } else {
      this.retryHandler.reset();
      this.sendFile_();
    }
  }
};

/**
 * Handles errors for uploads. Either retries or aborts depending
 * on the error.
 *
 * @private
 * @param {object} e XHR event
 */
MediaUploader.prototype.onContentUploadError_ = function(e) {
  console.error("MediaUploader.prototype.onContentUploadError_ e.target.status: ", e.target.status)
  if (e.target.status && e.target.status < 500) {
    this.onError(e.target.response);
  } else {
    this.retryHandler.retry(this.resume_.bind(this));
  }
};

/**
 * Handles errors for the complete request.
 *
 * @private
 * @param {object} e XHR event
 */
MediaUploader.prototype.onCompleteError_ = function(e) {
  console.error("MediaUploader.prototype.onCompleteError_ e.target.response: ", e.target.response)
  this.onError(e.target.response); // TODO - Retries for initial upload
};

/**
 * Handles errors for the initial request.
 *
 * @private
 * @param {object} e XHR event
 */
MediaUploader.prototype.onUploadError_ = function(e) {
  console.error("MediaUploader.prototype.onUploadError_ e.target.response: ", e.target.response)
  this.onError(e.target.response); // TODO - Retries for initial upload
};

/**
 * Construct a query string from a hash/object
 *
 * @private
 * @param {object} [params] Key/value pairs for query string
 * @return {string} query string
 */
MediaUploader.prototype.buildQuery_ = function(params) {
  params = params || {};
  return Object.keys(params).map(function(key) {
    return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
  }).join('&');
};

/**
 * Build the drive upload URL
 *
 * @private
 * @param {string} [id] File ID if replacing
 * @param {object} [params] Query parameters
 * @return {string} URL
 */
MediaUploader.prototype.buildUrl_ = function(id, params, baseUrl) {
  var url = baseUrl || 'https://api.vimeo.com/me/videos/';
  if (id) {
    url += id;
  }
  var query = this.buildQuery_(params);
  if (query) {
    url += '?' + query;
  }
  return url;
};

