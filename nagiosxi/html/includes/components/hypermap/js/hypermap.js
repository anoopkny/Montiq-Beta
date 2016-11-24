var labelType, useGradients, nativeTextSupport, animate;

var hypermap_rgraph;

(function () {
    var ua = navigator.userAgent,
        iStuff = ua.match(/iPhone/i) || ua.match(/iPad/i),
        typeOfCanvas = typeof HTMLCanvasElement,
        nativeCanvasSupport = (typeOfCanvas == 'object' || typeOfCanvas == 'function'),
        textSupport = nativeCanvasSupport
            && (typeof document.createElement('canvas').getContext('2d').fillText == 'function');
    //I'm setting this based on the fact that ExCanvas provides text support for IE
    //and that as of today iPhone/iPad current text support is lame
    labelType = (!nativeCanvasSupport || (textSupport && !iStuff)) ? 'Native' : 'HTML';
    nativeTextSupport = labelType == 'Native';
    useGradients = nativeCanvasSupport;
    animate = !(iStuff || !nativeCanvasSupport);
})();

var Log = {
    elem: false,
    write: function (text) {
        if (!this.elem)
            this.elem = document.getElementById('hypermap-log');
        this.elem.innerHTML = text;
        this.elem.style.left = (500 - this.elem.offsetWidth / 2) + 'px';
    }
};


function hypermap_init() {

    //init RGraph
    //var rgraph = new $jit.RGraph({
    hypermap_rgraph = new $jit.RGraph({
        //Where to append the visualization
        injectInto: 'hypermap-infovis',
        //Optional: create a background canvas that plots
        //concentric circles.
        background: {
            CanvasStyles: {
                //strokeStyle: '#555'
                strokeStyle: '#ddd'
            }
        },
        //Add navigation capabilities:
        //zooming by scrolling and panning.
        Navigation: {
            enable: true,
            panning: true,
            zooming: 10
        },
        //Set Node and Edge styles.
        Node: {
            overridable: true,
            //color: '#ddeeff',
            color: '#B9BCBF',
            //color: '#A1A4A7',
            //dim: 4
        },

        Edge: {
            overridable: true,
            // color: '#C17878',
            //color: '#79FF01',
            //color: '#9F9F9F',
            color: '#6C6C6C',
            //lineWidth:1.5
            lineWidth: 1.0
        },

        //Enable tips
        Tips: {
            enable: true,
            //add positioning offsets
            offsetX: 20,
            offsetY: 20,
            //implement the onShow method to
            //add content to the tooltip when a node
            //is hovered
            onShow: function (tip, node, isLeaf, domElement) {
                var html = node.data.info;
                /*
                 var html = "<div class=\"tip-title\">" + node.name
                 + "</div><div class=\"tip-text\">";
                 */
                /*
                 var data = node.data;
                 if(data.playcount) {
                 html += "play count: " + data.playcount;
                 }
                 if(data.image) {
                 html += "<img src=\""+ data.image +"\" class=\"album\" />";
                 }
                 */
                tip.innerHTML = html;
            }
        },

        onBeforeCompute: function (node) {
            Log.write("Centering " + node.name + "...");
            //Add the relation list in the right column.
            //This list is taken from the data property of each JSON node.
            //$jit.id('hypermap-inner-details').innerHTML = node.data.info;
            $jit.id('hypermap-inner-details').innerHTML = node.data.info + "<BR>" + node.data.statusurl;
        },

        onAfterCompute: function () {
            //Log.write("done");
            Log.write("");
        },
        //Add the name of the node in the correponding label
        //and a click handler to move the graph.
        //This method is called once, on label creation.
        onCreateLabel: function (domElement, node) {
            domElement.innerHTML = node.name;
            domElement.onclick = function () {
                hypermap_rgraph.onClick(node.id);
            };
        },
        //This method is called right before plotting
        //an edge. This method is useful to change edge styles
        //individually.
        onBeforePlotLine: function (adj) {
            if (adj.nodeTo.data.linecolor) {
                adj.data.$color = adj.nodeTo.data.linecolor;
            }
            //Add some random lineWidth to each edge.
            //if (!adj.data.$lineWidth)
            //adj.data.$lineWidth = Math.random() * 5 + 1;
            if (adj.nodeTo.data.linewidth) {
                adj.data.$lineWidth = adj.nodeTo.data.linewidth;
            }
        },
        //The data properties prefixed with a dollar
        //sign will override the global node style properties.
        onBeforePlotNode: function (node) {
            /*
             if (node.selected) {
             node.data.$color = "#ff0000";
             }
             else
             node.data.$color="#00ff00";
             */
        },

        //Change some label dom properties.
        //This method is called each time a label is plotted.
        onPlaceLabel: function (domElement, node) {
            var style = domElement.style;
            style.display = '';
            style.cursor = 'pointer';

            if (node._depth <= 1) {
                //style.fontSize = "0.8em";
                style.fontSize = "1.0em";
                //style.color = "#ccc";
                style.color = '#494949';

            } else if (node._depth == 2) {
                //style.fontSize = "0.7em";
                style.fontSize = "0.9em";
                style.color = "#646464";
                //style.color='#00ff00';

            } else {
                //style.display = 'none';
                //style.color='#ff0000';
                style.color = "#9F9F9F";
                style.fontSize = "0.8em";
            }

            var left = parseInt(style.left);
            var w = domElement.offsetWidth;
            style.left = (left - w / 2) + 'px';
        }
    });


    //init data
    // get JSON data via ajax
    var jdata = "{ }";
    var hypermapajaxurl = base_url + '/includes/components/hypermap/index.php';
    $.ajax({
        type: "POST",
        async: false,
        url: hypermapajaxurl,
        data: {mode: 'getdata', nsp: nsp_str},
        success: function (data) {
            jdata = data;
        }
    });
    var json = eval("(" + jdata + ")");


    //load JSON data
    hypermap_rgraph.loadJSON(json);
    //trigger small animation
    hypermap_rgraph.graph.eachNode(function (n) {
        var pos = n.getPos();
        pos.setc(-200, -200);
    });
    hypermap_rgraph.compute('end');
    hypermap_rgraph.fx.animate({
        modes: ['polar'],
        duration: 2000
    });
    //end
    //append information about the root relations in the right column
    $jit.id('hypermap-inner-details').innerHTML = hypermap_rgraph.graph.getNode(hypermap_rgraph.root).data.info;
}

// refresh graph
// from http://groups.google.com/group/javascript-information-visualization-toolkit/msg/4877c6f24e442fa7
function hypermap_refresh(i) {

    // save old root node
    //var oldroot=hypermap_rgraph.graph.getNode(hypermap_rgraph.root);
    var oldrootid = hypermap_rgraph.root;

    //Log.write("Refreshing map (" + i + ") = " + oldroot.data.info + "...");
    Log.write("Refreshing map...");

    // get new JSON data via ajax
    var jdata = "{ }";
    var hypermapajaxurl = base_url + '/includes/components/hypermap/index.php';
    $.ajax({
        type: "POST",
        async: false,
        url: hypermapajaxurl,
        data: {mode: 'getdata', nsp: nsp_str},
        success: function (data) {
            jdata = data;
        }
    });
    var json = eval("(" + jdata + ")");

    // load new JSON data
    hypermap_rgraph.loadJSON(json);

    // restore old root id
    // jit.js line 15709...
    hypermap_rgraph.root = oldrootid;

    // call with argument of true to reposition map...
    hypermap_rgraph.refresh(true);

    //Log.write("Map refreshed (" + i + ")");
    Log.write("");
}
