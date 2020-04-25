(function() {

	// Define a new library
	var feaClass = createZenarioLibrary(undefined, zenarioFEA),
		methods = methodsOf(feaClass);

	methods.typeOfLogic = function() {
		
		return this.guessTypeOfLogic();
		
	};

	zenario_document_envelopes_fea.init = function(containerId, path, request, mode, pages, libraryName, idVarName) {
		zenario_abstract_fea.setupAndInit(libraryName, feaClass, containerId, path, request, mode, pages, idVarName);
	};
	
})();