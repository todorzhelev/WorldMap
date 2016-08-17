<?php
session_start();

if(!isset($_SESSION['login_user']))
{
    echo 'You have to be logged in to access this page';
    ?>
    <a href="index.php">index.php</a>
<?php

    exit();
}

?>

<html> 
<head> 
    <title>World domination</title> 
    <link rel="stylesheet" type="text/css" href="style.css"> 
</head> 

<body>

<script src="js/three.min.js"></script>
<script src="js/TrackballControls.js"></script>
<script src="js/Detector.js"></script> 
<script src="js/stats.min.js"></script>

<script type="text/javascript">
	
	var renderer, camera, scene, pointLight, worldPlane;
    var mouseDown = false, mouseX = 0, mouseY = 0;
    var minZoom = -300, maxZoom = 600;
    var tileMapSizeX = 15, tileMapSizeY = 15, tileWidth = 100, tileHeight = 100;
    var tileMap = [];
    var stats;

    var grassTexture = THREE.ImageUtils.loadTexture('textures/grass.jpg');
    var grassMaterial = new THREE.MeshPhongMaterial({ map: grassTexture });
	var defaultMaterial = new THREE.MeshBasicMaterial({ color:0xffff00, wireframe:true });

	var treeModelName = "models/tree4.json";
	var houseModelName = "models/house.json";
	var commandCenterModelName = "models/CommandCenter.json";
	var mineralFieldModelName = "models/MineralField.json";
	var galio = "models/galio.json";
	
    init();
    mainLoop();

    //gameplay logic
    var gold, trees;

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

    function init()
    {
        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        //renderer
        renderer = new THREE.WebGLRenderer();
        renderer.setSize(window.innerWidth, window.innerHeight);
        document.body.appendChild(renderer.domElement);

        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        //scene
        scene = new THREE.Scene();

        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        //camera
        //vertical field of view, aspect ratio, near plane, far plane
        camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 1, 2000);
        camera.position.set(700, 600, -200);
        camera.rotateX(-Math.PI/3);
        scene.add(camera);
 
        /////////////////////////////////////////////////////////////////////////////////////////////////////////

		generateTileMap();

		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		loadMeshWithMaterial(galio,"galio",650,-750, 0.3,0);

        loadMeshWithMaterial(houseModelName,"base",650,-500, 1,3.14);

		spawnTrees();
        spawnMineralFields();

		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		//statistics
        stats = new Stats();
        stats.setMode(0); // 0: fps, 1: ms

        stats.domElement.style.position = 'absolute';
        stats.domElement.style.left = '0px';
        stats.domElement.style.bottom = '0px';

        document.body.appendChild(stats.domElement);

        /////////////////////////////////////////////////////////////////////////////////////////////////////////     

        // ambient light
        scene.add(new THREE.AmbientLight(0xffffff));

        var light = new THREE.DirectionalLight(0xffffff, 0.2);
        light.position.set(0, 50, 0);
        scene.add(light);

        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        //listeners for mouse move, mouse down, mouse up, mouse wheel

        document.body.addEventListener('mousemove', function (e) { onMouseMove(e); }, false);
        document.body.addEventListener('mousedown', function (e) { onMouseDown(e); }, false);
        document.body.addEventListener('mouseup', function (e) { onMouseUp(e); }, false);
        document.body.addEventListener('mousewheel', function (e) { onMouseWheel(e); }, false);
        document.body.addEventListener('DOMMouseScroll', function (e) { onMouseWheel(e); }, false);//this is for Mozilla

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

	function generateTileMap()
    {
        tileMap = new Array(tileMapSizeX);

        for (var i = 0; i < tileMapSizeX; i++)
        {
            tileMap[i] = new Array(tileMapSizeY);
        }

        for (var i = 0; i < tileMapSizeX; i++)
        {
            for (var j = 0; j < tileMapSizeY; j++)
            {
                var geometry = new THREE.PlaneGeometry(tileWidth, tileHeight, 1, 1);
                var mesh = new THREE.Mesh(geometry, grassMaterial);

               	mesh.translateX(tileWidth * j);
               	mesh.translateZ(-tileHeight * i);
               	mesh.rotateX(-Math.PI / 2);

                tileMap[i][j] = mesh;
                scene.add(mesh);
            }
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

	function loadMesh(meshName, xPos, zPos, scale) 
    {
	    var loader = new THREE.JSONLoader();
	    loader.load(meshName, function(geometry) 
	    {
	        mesh = new THREE.Mesh(geometry);
	        mesh.translateX(xPos);
	        mesh.translateZ(zPos);
	        mesh.scale.x = mesh.scale.y = mesh.scale.z = scale;
	        scene.add(mesh);
	    });
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

    function loadMeshWithMaterial(meshPath,name, xPos, zPos,scale, rotY) 
    {
	    var loader = new THREE.JSONLoader();
	    loader.load(meshPath, function(geometry,materials) 
	    {
	    	var material = new THREE.MeshFaceMaterial(materials);
	    	material.transparent = true;
	    	material.opacity = 0.5;
	        mesh = new THREE.Mesh(geometry,material);
	        mesh.translateX(xPos);
	        mesh.translateZ(zPos);
	        mesh.scale.x = mesh.scale.y = mesh.scale.z = scale;
            mesh.rotateY(rotY);
            mesh.name = name;
	        scene.add(mesh);
	    });
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	function spawnTrees()
	{
		var scale = 3.5;

		var startX = 600, endX = 1200;
		var startZ = -900, endZ = -1300;
		var step = 100;

		for(var i = startX; i != endX; i+= step)
		{
			for(var j = startZ; j != endZ; j-=step)
			{
				loadMeshWithMaterial(treeModelName,"three",i,j, scale,0);
			}
		}
	}

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function spawnMineralFields()
    {
        var scale = 3.5;

        var startX = 1200;
        var startZ = -800, endZ = -300;
        var step = 100;

        for(var j = startZ; j != endZ; j+=step)
        {
            loadMeshWithMaterial(mineralFieldModelName,"mineralField",startX,j, scale,0);
        }
    }

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

    function onMouseWheel(event)
    {
        var delta = 0;

        if (event.wheelDelta) //Chrome
        {
            delta = -event.wheelDelta / 20;
        }
        else if (event.detail) //Mozilla
        {
            delta = event.detail*3;
        }

       // if (camera.position.z + delta > minZoom && camera.position.z + delta < maxZoom)
        {
            camera.translateZ(delta);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function onMouseMove(event)
    {
        if (!mouseDown) {
            return;
        }

        var deltaX = event.clientX - mouseX;
        var deltaY = event.clientY - mouseY;
        mouseX = event.clientX;
        mouseY = event.clientY;

        moveCamera(deltaX, deltaY);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function moveCamera(deltaX, deltaY)
    {
        camera.translateX(-deltaX);
        camera.translateY(deltaY);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function onMouseDown(event)
    {
        mouseDown = true;
        mouseX = event.clientX;
        mouseY = event.clientY;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function onMouseUp(event)
    {
        mouseDown = false;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function mainLoop()
    {
        requestAnimationFrame(mainLoop);
        stats.begin();

	        update();
	        render();

	    stats.end();
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function update()
    {
        var object = scene.getObjectByName("galio");
        if( object )
        {
          object.rotation.y += 0.03;  
        } 
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function render()
    {
        renderer.render(scene, camera);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

</script>

<!--2d mode button-->
<div style="position:absolute;bottom:0;left:0;margin-bottom:10px;margin-left:50px;">
    <form action="index2dMode.php">
        <input type="submit" id ="Submit2" style="font-size:15pt;font-family:'Times New Roman'" value ="Go to 2D mode">
    </form>
</div> 


</body>
</html>