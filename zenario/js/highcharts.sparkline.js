
//
//	Sparkline charts functionality from https://www.highcharts.com/demo/highcharts/sparkline
//	Public domain.
//


	/**
	 * Create a constructor for sparklines this takes some sensible defaults and merges in the individual
	 * chart options. This function is also available from the jQuery plugin as $(element).highcharts('SparkLine').
	 */
	Highcharts.SparkLine = function (a, b, c) {
		var hasRenderToArg = typeof a === 'string' || a.nodeName,
			options = arguments[hasRenderToArg ? 1 : 0],
			defaultOptions = {
				chart: {
					renderTo: (options.chart && options.chart.renderTo) || this,
					backgroundColor: null,
					borderWidth: 0,
					type: 'area',
					margin: [2, 0, 2, 0],
					width: 120,
					height: 40,
					style: {
						overflow: 'visible'
					},
					skipClone: true
				},
				title: {
					text: ''
				},
				credits: {
					enabled: false
				},
				xAxis: {
					labels: {
						enabled: false
					},
					title: {
						text: null
					},
					startOnTick: false,
					endOnTick: false,
					tickPositions: []
				},
				yAxis: {
					endOnTick: false,
					startOnTick: false,
					labels: {
						enabled: false
					},
					title: {
						text: null
					},
					tickPositions: [0]
				},
				legend: {
					enabled: false
				},
				/*tooltip: { 
					enabled: true 
				},*/
				tooltip: {
					backgroundColor: null,
					borderWidth: 0,
					shadow: false,
					useHTML: true,
					hideDelay: 0,
					shared: true,
					padding: 0,
					positioner: function (w, h, point) {
						return { x: point.plotX - w / 2, y: point.plotY - h };
					}
				},
				plotOptions: {
					series: {
						animation: false,
						lineWidth: 1,
						shadow: false,
						states: {
							hover: {
								//lineWidth: 1
								enabled: false
							}
						},
						marker: {
							radius: 1,
							states: {
								hover: {
									radius: 2
								}
							}
						},
						fillOpacity: 0.25
					},
					column: {
						negativeColor: '#910000',
						borderColor: 'silver'
					}
				},
				exporting: {
					enabled: false
				}
			};

		options = Highcharts.merge(defaultOptions, options);

		return hasRenderToArg ?
			new Highcharts.Chart(a, options, c) :
			new Highcharts.Chart(options, b);
	};