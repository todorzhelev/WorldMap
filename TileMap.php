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
    var mouseDown = false;
    var minZoom = -300, maxZoom = 600;
    var tileMapSizeX = 15, tileMapSizeY = 15, tileWidth = 100, tileHeight = 100;
    var tileMap = [];
    var stats;
    var mouse;

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
    var mineralsAmount = 0, treesAmount = 0;
    var workerMaxResources = 200;

    var states = 
    {
        IDLE: 0,
        MOVING: 1,
        COLLECTING_RESOURCES: 2,
        RETURNING_RESOURCES: 3
    };

    var worker1 =
    {
        workerName : "worker1",
        collectedTrees : 0,
        collectedMinerals: 0,
        currentState: states.IDLE,
        currentTree: null
    }

    var worker2 =
    {
        workerName : "worker2",
        collectedTrees : 0,
        collectedMinerals: 0,
        currentState: states.IDLE
    }

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

        mouse = new THREE.Vector2();

        /////////////////////////////////////////////////////////////////////////////////////////////////////////

		generateTileMap();

		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		loadMeshWithMaterial(galio,"worker1",650,-750, 0.3,0);

        loadMeshWithMaterial(galio,"worker2",800,-750, 0.3,0);

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

        var id = 0;
		for(var i = startX; i != endX; i+= step)
		{
			for(var j = startZ; j != endZ; j-=step)
			{
				loadMeshWithMaterial(treeModelName,"tree"+id.toString(),i,j, scale,0);
                id++;
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

        var deltaX = event.clientX - mouse.x;
        var deltaY = event.clientY - mouse.y;


        mouse.x = event.clientX;
        mouse.y = event.clientY;

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
        mouse.x = event.clientX;
        mouse.y = event.clientY;
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
        var worker1Mesh = scene.getObjectByName("worker1"); 
        var baseMesh = scene.getObjectByName("base");

        if( worker1Mesh && baseMesh)
        {
            switch(worker1.currentState)
            {
                case states.IDLE:
                {
                    //TODO: no magic numbers
                    var currentTree = getRandomInt(0,23);
                    worker1.currentTree = scene.getObjectByName("tree"+currentTree.toString());

                    worker1.currentState = states.MOVING;
                    break;
                }

                case states.MOVING:
                {
                    if( !isActorAtTarget(worker1Mesh,worker1.currentTree) )
                    {
                        moveActorToTarget(worker1Mesh,worker1.currentTree);
                    }
                    else
                    {
                       worker1.currentState = states.COLLECTING_RESOURCES; 
                    }

                    break;
                }

                case states.COLLECTING_RESOURCES:
                {
                    if( worker1.collectedTrees < workerMaxResources )
                    {
                        ++worker1.collectedTrees;
                    }
                    else
                    {
                        worker1.currentState =  states.RETURNING_RESOURCES;
                    }

                    break;
                }

                case states.RETURNING_RESOURCES:
                {
                    if( !isActorAtTarget(worker1Mesh,baseMesh) )
                    {
                        moveActorToTarget(worker1Mesh,baseMesh);
                    }
                    else
                    {
                        treesAmount += worker1.collectedTrees;
                        worker1.collectedTrees = 0;
                        worker1.currentState = states.IDLE; 
                    }

                    break;
                }
            }
        }

        PrintTreesAmount();
        PrintMineralsAmount();
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function moveActorToTarget(actor,target)
    {
        var coeff = 3;

        var dir = new THREE.Vector3();
        dir.subVectors(target.position,actor.position);
        dir.normalize();

        dir.multiplyScalar(coeff);
        var newPosition = new THREE.Vector3();
        newPosition.addVectors(actor.position,dir);
        actor.position.set(newPosition.x,newPosition.y,newPosition.z);  
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function isActorAtTarget(actor,target)
    {
        var epsilon = 2;
        var actorPos = actor.position;
        var targetPos = target.position;

        var dx = Math.abs(targetPos.x - actorPos.x);
        var dy = Math.abs(targetPos.y - actorPos.y);
        var dz = Math.abs(targetPos.z - actorPos.z);

        return ( dx < epsilon && dy < epsilon && dz < epsilon );
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function render()
    {
        renderer.render(scene, camera);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function PrintTreesAmount()
    {
        if(document.getElementById("treesAmount"))
        {
            document.getElementById("treesAmount").innerHTML = "Trees amount: " + treesAmount;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function PrintMineralsAmount()
    {
        if(document.getElementById("mineralsAmount"))
        {
            document.getElementById("mineralsAmount").innerHTML = "Minerals amount:" + mineralsAmount;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    function getRandomInt(min, max) 
    {
        min = Math.ceil(min);
        max = Math.floor(max);

        return Math.floor(Math.random() * (max - min)) + min;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

</script>

<!--2d mode button-->
<div style="position:absolute;bottom:0;left:0;margin-bottom:10px;margin-left:50px;">
    <form action="index2dMode.php">
        <input type="submit" id ="Submit2" value ="Go to 2D map">
    </form>
</div> 

<!-- document.write prints the returned value at this place -->
<div style="position:absolute;top:0;right:0;margin-top:10px;margin-right:50px;">
    <p id = "treesAmount" style="color:white"></p>
    <p id = "mineralsAmount" style="color:white"></p>
</div>

</body>
</html>