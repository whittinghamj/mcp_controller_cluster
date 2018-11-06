<?php

include('/etc/mcp/global_vars.php');
include('/mcp_cluster/db.php');
include('/mcp_cluster/functions.php');

$nodes = get_nodes();

?>


<style>
	#map-canvas {
	height: 100%;
	margin: 0px;
	padding: 0px;
	width: 100%;
	}
</style>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDpMWtXLvl-a6YsAAB2HBQvK-_c0_zDtXg&v=3"></script>

<script>
	var map;
	function initialize() {
		var myLatlng = new google.maps.LatLng(41.850033, -87.6500523);

		var mapOptions = {
			zoom: 3,
			center: new google.maps.LatLng(41.850033, -87.6500523)
		};
		map = new google.maps.Map(document.getElementById('map-canvas'),
		mapOptions);

		<?php foreach($nodes as $node){ ?>
			add_markers('<?php echo $node['location']['latitude']; ?>', '<?php echo $node['location']['longitude']; ?>');
		<?php } ?>

		var flightPlanCoordinates = [
          {lat: 39.443901062012, lng: -104.85150146484},
          {lat: 21.291, lng: -157.821}
        ];
        var flightPath = new google.maps.Polyline({
          path: flightPlanCoordinates,
          geodesic: true,
          strokeColor: '#FF0000',
          strokeOpacity: 1.0,
          strokeWeight: 2
        });

        flightPath.setMap(map);

	}

	function add_marker(location) {
        marker = new google.maps.Marker({
            position: location,
            map: map
        });
    }

    function add_markers(lat, lng) {
		marker = new google.maps.LatLng(lat, lng);
        add_marker(marker);
    }

	google.maps.event.addDomListener(window, 'load', initialize);

</script>

<div id="map-canvas"></div>