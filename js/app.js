// --allow-file-access-from-files should be added when starting chrome
// so local files can be loaded. This is because of security restrictions

var renderer, camera, controls, scene, pointLight, planet, sky;

init();
mainLoop();

function init()
{
	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	//renderer
	renderer = new THREE.WebGLRenderer();
	renderer.setSize(window.innerWidth, window.innerHeight);
	document.body.appendChild(renderer.domElement);

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	//camera
	camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 1, 1000);
	camera.position.setZ(40);

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	//control over the mouse
	controls = new THREE.TrackballControls(camera);
	controls.addEventListener('change', render);
	controls.minDistance = 17;
	controls.maxDistance = 70;
	controls.zoomSpeed = 0.7;

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	//scene
	scene = new THREE.Scene();

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	// the planet
	var planetGeometry = new THREE.SphereGeometry(10, 100, 100);

	var planetMaterial = new THREE.MeshPhongMaterial;

	planetMaterial.map = THREE.ImageUtils.loadTexture('textures/2_no_clouds_4k.jpg');
    //bump map is not working
	//planetMaterial.bumpMap = THREE.ImageUtils.loadTexture('textures/elev_bump_4k.jpg');
	//planetMaterial.bumpScale = 2;
	planetMaterial.specularMap = THREE.ImageUtils.loadTexture('textures/water_4k.png');
	planetMaterial.specular = new THREE.Color('grey');

	planet = new THREE.Mesh(planetGeometry, planetMaterial);
	scene.add(planet);

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	// the environment map

	var skyGeometry = new THREE.SphereGeometry(200, 64, 64);

	var skyMaterial = new THREE.MeshPhongMaterial;
	skyMaterial.map = THREE.ImageUtils.loadTexture('textures/galaxy_starfield.png');
	skyMaterial.side = THREE.BackSide;

	sky = new THREE.Mesh(skyGeometry, skyMaterial);

	scene.add(sky);

	/////////////////////////////////////////////////////////////////////////////////////////////////////////     

	// ambient light
	scene.add(new THREE.AmbientLight(0xffffff));

	var light = new THREE.DirectionalLight(0xffffff, 0.2);
    //light.position.set(planet.position.x, planet.position.y, planet.position.z);
	light.position.set(0, 50, 0);
	scene.add(light);
}

function mainLoop()
{
	requestAnimationFrame(mainLoop);
	update();
	render();
}

function update()
{
	controls.update();
	planet.rotation.y += 0.0003;
}

function render()
{
	renderer.render(scene, camera);
}