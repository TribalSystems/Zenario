var m = [20, 120, 20, 120],
	canvas_w = 1160 - m[1] - m[3],
	canvas_h = svgHeight - m[0] - m[2],
	i = 0,
	mode = "redundancy",
	root;

$(document).ready(function () {
	$("#mode").change(function () {
		mode = $(this).val();
		
		$(".mode_key").hide();
		
		if (mode=="redundancy") {
			$("#redundancy_key").show();
		} else if (mode=="visibility") {
			$("#visibility_key").show();
		} else if (mode=="privacy") {
			$("#privacy_key").show();
		}
		update(root);
	});
});

var tree, diagonal, vis, tree_svg;

function resizeTreeSVG(pcanvas_w, pcanvas_h){
	canvas_h = pcanvas_h;
	canvas_w = pcanvas_w;

	tree.size([canvas_h, canvas_w]);
	
	tree_svg.attr("width", canvas_w + m[1] + m[3])
	.attr("height", canvas_h + m[0] + m[2]);
}

function createTreeSVG(pcanvas_w, pcanvas_h){
	canvas_h = pcanvas_h;
	canvas_w = pcanvas_w;
	var el = document.getElementById("body");
	var new_body = document.createElement("div");
	el.parentNode.replaceChild(new_body, el);
	new_body.id = "body";

	tree = d3.layout.tree();

	diagonal = d3.svg.diagonal()
	.projection(function(d) { return [d.y, d.x]; });

	tree_svg = d3.select("#body").append("svg:svg");
	vis = tree_svg.append("svg:g")
	.attr("transform", "translate(" + m[3] + "," + m[0] + ")");

	resizeTreeSVG(pcanvas_w, pcanvas_h);
}

createTreeSVG(canvas_w, canvas_h);

var tooltip = d3.select("body")
	.append("div")
	.style("position", "absolute")
	.style("z-index", "10")
	.style("visibility", "hidden")
	.style("background-color","white")
	.style("font-size","11px")
	.style("font-family","verdana");	

d3.json(JSONURL, function(json) {
	root = json;
	root.x0 = 0; //canvas_h / 2;
	root.y0 = 0;
	
	// Initialize the display to show a 2 levels beneath the parent node.
  
	toggleAll(root,2,0);
	  	
	update(root);
});

function update(source) {
	var duration = d3.event && d3.event.altKey ? 5000 : 500;

	var nodes, nodes_hor_dist_ary;
	//two pass, first to calculate
	for(var j=0; j < 2; ++j){
		// Compute the new tree layout.
		nodes = tree.nodes(root).reverse();

		// Normalize for fixed-depth.
		var nodes_hor_dist = 40;
		nodes_hor_dist_ary = [0];
		var nodes_ver_dist_ary = [0];

		var one_char_width = 8;
		nodes.forEach(function(d, i) { 
			if(d.depth >= nodes_hor_dist_ary.length){
				for(var ni=nodes_hor_dist_ary.length; ni<= d.depth; ++ni){
					nodes_hor_dist_ary.push(nodes_hor_dist);
					nodes_ver_dist_ary.push(0);
				}
			}
			++nodes_ver_dist_ary[d.depth];
			if(d.name){
				var name_len = (d.name.length+2) * (d.name.length > 40 ? one_char_width-3 : one_char_width);
				if(name_len > nodes_hor_dist_ary[d.depth])
					nodes_hor_dist_ary[d.depth] = name_len;
			}
			});

		if(j == 0){
			var max_ver_dist = 0;
			var best_width = 0;
			var best_height = 0;
			for(var k=0, len=nodes_ver_dist_ary.length; k < len; ++k){
				if(nodes_ver_dist_ary[k] > max_ver_dist) max_ver_dist = nodes_ver_dist_ary[k];
				best_width += nodes_hor_dist_ary[k];
			}
			best_height = max_ver_dist * 34; //vertical space between nodes			
			if( 
					((best_width > canvas_w) || (best_height > canvas_h)) || 
					((canvas_h - best_height) > (best_height/3)) )
			{
				resizeTreeSVG(best_width, best_height);
			}
		}		
	}	

	//compute start x position for each node
	for(idx in nodes_hor_dist_ary){
		if(idx > 0) nodes_hor_dist_ary[idx] += nodes_hor_dist_ary[idx-1];
	}

	nodes.forEach(function(d) { d.y = d.depth ? nodes_hor_dist_ary[d.depth-1] : -nodes_hor_dist_ary[d.depth]; });

	// Update the nodes‚Ä¶
	var node = vis.selectAll("g.node")
	  	.data(nodes, function(d) { return d.id || (d.id = ++i); });

	// Enter any new nodes at the parent's previous position.
	var nodeEnter = node.enter().append("svg:g")
	  	.attr("class", "node")
	  	.attr("transform", function(d) { return "translate(" + source.y0 + "," + source.x0 + ")"; })
	  	.on("click", function(d) { toggle(d); update(d); tooltip.text(""); });

	nodeEnter.append("svg:circle")
	  	.attr("r", 1e-6)
	  	.on("mouseover", function(d) {
	  		var d2 = d;

			tooltip.style("visibility", "visible");
			tooltip.text(function () { return d2.children ? "Click to hide child menu nodes" : d2._children ? "Click to show child menu nodes" : "There are no child menu nodes"});
			return true;
		})
		.on("mousemove", function(d) {
			tooltip.style("top", (d3.event.pageY-10)+"px").style("left",(d3.event.pageX+10)+"px");
			return true;
		})
		.on("mouseout", function(d) {
			tooltip.style("visibility", "hidden");
			return true;
		});

	nodeEnter.append("svg:circle")
	  	.attr("r", 2)
	  	.attr("class","child_circle")
	  	.attr("cx",-5);

	nodeEnter.append("svg:circle")
	  	.attr("r", 2)
	  	.attr("class","child_circle");

	nodeEnter.append("svg:circle")
	  	.attr("r", 2)
	  	.attr("class","child_circle")
	  	.attr("cx",5);

	var child_text_xpos = 20; //28;
	
	nodeEnter.append("svg:rect")
		.attr("id",function (d) { return "menu_node_" + d.mID})
		.attr("width",6)
		.attr("height",9)
		.attr("x",12)
		.attr("y",-4)
		.attr("class", "rect_page")
		.style("display", function(d) { return (d.target_loc==undefined || d.target_loc=="none") ? "none" : "block"});
		
	nodeEnter.append("a")
		.attr("xlink:href", function (d) { if (d.content_href) return d.content_href; return undefined;})
		.on("click", function (d) { if (d.storekeeper_href) window.parent.zenarioO.go(d.storekeeper_href); return false; })
		.attr("xlink:show", "new")
		.attr("xlink:target", "_blank")
		.style("display", function(d) { return (d.content_href == undefined && d.storekeeper_href === undefined) ? "none" : "block"})
		.append("svg:text")
	  	//.attr("x", function(d) { return d.children || d._children ? -12 : (d.target_loc!=undefined && d.target_loc!="none") ? child_text_xpos : 12; })
	  	.attr("x", function(d) { return child_text_xpos; })
	  	.attr("dy", ".35em")
	  	//.attr("text-anchor", function(d) { return d.children || d._children ? "end" : "start"; })
	  	//.attr("text-anchor", function(d) { return "start"; })
	  	.text(function(d) { return d.name; })
	  	.style("fill-opacity", 1e-6)
			.on("mousemove", function(){
				tooltip.style("top", (d3.event.pageY-10)+"px").style("left",(d3.event.pageX+10)+"px");
				return true;
			})
	  	/*
			.on("mouseover", function(){
				tooltip.style("visibility", "visible");
				tooltip.text("Click to view the target content item in a new tab");
				return true;
			})
			.on("mouseout", function(){
				tooltip.style("visibility", "hidden");
				return true;
			})*/;

	// Transition nodes to their new position.
	var nodeUpdate = node.transition()
	  	.duration(duration)
	  	.attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });

	nodeUpdate.select("circle")
	  	.attr("r", 9)
	  	.attr("class",function (d){
	  		var circleClass = false;

	  		if (!d.section) {
		  		if (mode=="redundancy") {
				  	circleClass = (d.redundancy=="primary") ? "node_menu_node_primary" : "node_menu_node_secondary";
				} else if (mode=="visibility") {
				  	circleClass = (d.visibility=="visible") ? "node_menu_node_visible" : "node_menu_node_invisible";
				} else if (mode=="privacy") {
				  	circleClass = "node_menu_node_privacy_" + d.hide_private_item;
				}
				
				if (d.children || d._children) {
					circleClass += " children";
				}

				return circleClass;
	  		}
	  	})

	nodeUpdate.select("text")
	  	.style("fill-opacity", 1);

	// Transition exiting nodes to the parent's new position.
	var nodeExit = node.exit().transition()
	  	.duration(duration)
	  	.attr("transform", function(d) { return "translate(" + source.y + "," + source.x + ")"; })
	  	.remove();

	nodeExit.select("circle")
	  	.attr("r", 1e-6);

	nodeExit.select("text")
	  	.style("fill-opacity", 1e-6);

	// Update the links‚Ä¶
	var link = vis.selectAll("path.link")
	  	.data(tree.links(nodes), function(d) { return d.target.id; });

	// Enter any new links at the parent's previous position.
	link.enter().insert("svg:path", "g")
	  	.attr("class", "link")
	 	.attr("d", function(d) {
			var o = {x: source.x0, y: source.y0};
			return diagonal({source: o, target: o});
	  	})
	.transition()
	  	.duration(duration)
	  	.attr("d", diagonal);

	// Transition links to their new position.
	link.transition()
	  	.duration(duration)
	  	.attr("d", diagonal);

	// Transition exiting nodes to the parent's new position.
	link.exit().transition()
	  	.duration(duration)
	  	.attr("d", function(d) {
			var o = {x: source.x, y: source.y};
			return diagonal({source: o, target: o});
	  	})
	  	.remove();

	// Stash the old positions for transition.
	nodes.forEach(function(d) {
		d.x0 = d.x;
		d.y0 = d.y;
	});
}

// Toggle children.
function toggle(d) {
 	if (d.children) {
 		closeAll(d);
 	} else {
		d.children = d._children;
		d._children = null;
 	}
}

function closeAll(d) {
	if (d.children || d._children) {			
		if (d.children) {
			d._children = d.children;
			d.children = null;
		}

		for (childId in d._children) {
			closeAll(d._children[childId]);
		}					
	}		
}

function toggleAll(d,maxLevelCount,levelCount) {
	if (d.children || d._children) {
		levelCount++;
		
		if (levelCount>=maxLevelCount) {
			d._children = d.children;
			d.children = null;
		}

		if (d.children) {
			for (childId in d.children) {
				toggleAll(d.children[childId],maxLevelCount,levelCount);
			}
		} else if (d._children) {
			for (childId in d._children) {
				toggleAll(d._children[childId],maxLevelCount,levelCount);
			}					
		}
	}
}