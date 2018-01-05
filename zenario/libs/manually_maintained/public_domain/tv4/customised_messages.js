

tv4.addLanguage('en', {
	INVALID_TYPE: "Found type {type}, expected {expected}",
	ENUM_MISMATCH: "{value} is not in the list of allowed values",
	ANY_OF_MISSING: "Data does not match any schemas from \"anyOf\"",
	ONE_OF_MISSING: "Please include one of the following properties: {keys}",
	ONE_OF_MULTIPLE: "Propeties {index1} and {index2} may not appear together",
	NOT_PASSED: "Data matches schema from \"not\"",
	// Numeric errors
	NUMBER_MULTIPLE_OF: "Value {value} is not a multiple of {multipleOf}",
	NUMBER_MINIMUM: "Value {value} is less than minimum {minimum}",
	NUMBER_MINIMUM_EXCLUSIVE: "Value {value} is equal to exclusive minimum {minimum}",
	NUMBER_MAXIMUM: "Value {value} is greater than maximum {maximum}",
	NUMBER_MAXIMUM_EXCLUSIVE: "Value {value} is equal to exclusive maximum {maximum}",
	// String errors
	STRING_LENGTH_SHORT: "String is too short ({length} chars), minimum {minimum}",
	STRING_LENGTH_LONG: "String is too long ({length} chars), maximum {maximum}",
	STRING_PATTERN: "String does not match pattern: {pattern}",
	// Object errors
	OBJECT_PROPERTIES_MINIMUM: "Too few properties defined ({propertyCount}), minimum {minimum}",
	OBJECT_PROPERTIES_MAXIMUM: "Too many properties defined ({propertyCount}), maximum {maximum}",
	OBJECT_REQUIRED: "Missing required property: {key}",
	OBJECT_ADDITIONAL_PROPERTIES: "Additional properties not allowed",
	OBJECT_DEPENDENCY_KEY: "Dependency failed: {missing} must be set if {key} is set",
	OBJECT_DEPENDENCY_IF_TRUE_KEY: "Dependency failed: {missing} must be set if {key} is true",
	OBJECT_FORBIDDEN_IF_TRUE_KEY: "You may not use the {missing} property if {key} is set",
	// Array errors
	ARRAY_LENGTH_SHORT: "Array is too short ({length}), minimum {minimum}",
	ARRAY_LENGTH_LONG: "Array is too long ({length}), maximum {maximum}",
	ARRAY_UNIQUE: "Array items are not unique (indices {match1} and {match2})",
	ARRAY_ADDITIONAL_ITEMS: "Additional items not allowed",
	//Unrecognised properties
	OBJECT_ADDITIONAL_PROPERTIES: 'Warning: unrecognised property. You probably have a typo in the property name.\n' +
		'If you are trying to add your own meta-data, please prefix your custom property names with "custom_" to avoid this warning.\n'
	
	, STATIC_PROPERTY: 'This property cannot be changed in your php code'
});