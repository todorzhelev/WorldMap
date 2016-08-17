<?php
session_start();

$DUPLICATE_ENTRY_CODE = 1062;

function showErrorMessage($message)
{
?>
    <div id="openErrorMessage" class="registerDialog">
        <div>
            <a href="#openErrorMessage" onclick="setVisibility('openErrorMessage', 'hidden');" class="closeButtonRegister">X</a>

            <div style="position: relative; left: 10%;">
                <form action="" method="post" id="Form1">
                    <br /><br /><br />

                    <label style="font-size: 25pt; font-family: 'Times New Roman'"><?php echo $message; ?> </label> <br /><br />

                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('openErrorMessage').style.visibility = 'visible';
    </script>
<?php
}

if(!isset($_SESSION['login_user']))
{
    if( isset($_SESSION))
    {
        session_destroy(); 
    } 
}

if(isset($_POST['RegisterSubmitButton']))
{
    if( $_POST["email"] && $_POST["username"] && $_POST["password"] )
    {
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) 
        {
            showErrorMessage('Cannot register user. You have entered invalid email');
        }
        else
        {
            $host = "localhost";
            $db = "players";
            $user = "root";
            $pass = "";
            $conn = new PDO("mysql:host=$host;dbname=$db",$user,$pass);

            $stmt = $conn->prepare("INSERT INTO logindata (username, password, email)
                                    VALUES (:username, :password, :email)");

            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':email', $email);

            $username = $_POST["username"];
            $password = hash("sha256",$_POST["password"]);
            $email = $_POST["email"];
            $result = $stmt->execute(); 

            if ($result) 
            {
                showErrorMessage('You registered successfully.');
            } 
            else 
            {
                $error = $stmt->errorInfo();
                if ($error[1] == $DUPLICATE_ENTRY_CODE) 
                {
                    showErrorMessage('Such user exists in the database');
                }
            }
        }      
    }      
}
elseif (isset($_POST['LoginSubmitButton'])) 
{
    if($_POST["username"] && $_POST["password"] )
    {
        $host = "localhost";
        $db = "players";
        $user = "root";
        $pass = "";
        $conn = new PDO("mysql:host=$host;dbname=$db",$user,$pass);

        $stmt = $conn->prepare("SELECT * FROM logindata where username = :username and password= :password");

        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);

        $username = $_POST["username"];
        $password = hash("sha256",$_POST["password"]);

        $result = $stmt->execute();

        if ( $result && $stmt->rowCount() == 1) 
        {
            //the session should be started here, because if we switch to another page
            //it will not save the login_user data
            session_start();

            $row = $stmt->fetch();
            $_SESSION['login_user']= $row["username"];

        }
        else
        {
            showErrorMessage('No such user in the database');
        }
    } 
}
elseif (isset($_POST['logout'])) 
{
    if( isset($_SESSION) )
    {

        $_SESSION['login_user'] = null;
    }  
}

?>

<html> 
<head> 
    <title>World domination</title> 
    <link rel="stylesheet" type="text/css" href="style.css"> 
</head> 

<body> 

<script src="js/three.min.js"></script> 

<script>

    // --allow-file-access-from-files should be added when starting chrome
    // so local files can be loaded. This is because of security restrictions

    var renderer, camera, controls, scene, pointLight, planet, sky, stats;
    var mouseDown = false, mouseX = 0, mouseY = 0;
    var minZoom = 20, maxZoom = 50;

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

        //scene
        scene = new THREE.Scene();

        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        // the planet
        var planetGeometry = new THREE.SphereGeometry(10, 100, 100);

        var planetMaterial = new THREE.MeshPhongMaterial;

        planetMaterial.map = THREE.ImageUtils.loadTexture('textures/NASA_world_reduced1.jpg');
        planetMaterial.specularMap = THREE.ImageUtils.loadTexture('textures/water_4k.png');
        planetMaterial.specular = new THREE.Color('grey');

        planet = new THREE.Mesh(planetGeometry, planetMaterial);
        scene.add(planet);

        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        // the sky
        var skyGeometry = new THREE.SphereGeometry(900, 64, 64);

        var skyMaterial = new THREE.MeshPhongMaterial;
        var texture = THREE.ImageUtils.loadTexture('textures/galaxy_starfield1.jpg');
        texture.wrapS = THREE.RepeatWrapping;
        texture.wrapT = THREE.RepeatWrapping;
        texture.repeat.set(12, 12);

        skyMaterial.map = texture;

        skyMaterial.side = THREE.BackSide;

        sky = new THREE.Mesh(skyGeometry, skyMaterial);

        scene.add(sky);

        /////////////////////////////////////////////////////////////////////////////////////////////////////////     

        // ambient light
        scene.add(new THREE.AmbientLight(0xffffff));

        var light = new THREE.DirectionalLight(0xffffff, 0.2);
        light.position.set(0, 50, 0);
        scene.add(light);

        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        //listeners for mouse move mouse down and mouse up

        document.body.addEventListener('mousemove', function (e){ onMouseMove(e);}, false);
        document.body.addEventListener('mousedown', function (e) {onMouseDown(e);}, false);
        document.body.addEventListener('mouseup', function (e) { onMouseUp(e); }, false);
        document.body.addEventListener('mousewheel', function (e) { onMouseWheel(e); }, false);
        document.body.addEventListener('DOMMouseScroll', function (e) { onMouseWheel(e); }, false);//Mozilla

    }

    function onMouseWheel(event)
    {
        var delta = 0;

        if (event.wheelDelta) //Chrome
        { 
            delta = -event.wheelDelta / 120;
        }
        else if (event.detail) //Mozilla
        {
            delta = event.detail / 3;
        }

        if (camera.position.z + delta > minZoom && camera.position.z + delta < maxZoom)
        {
            camera.translateZ(delta);
        }
    }

    function onMouseMove(event)
    {
        if (!mouseDown)
        {
            return;
        }

        var deltaX = event.clientX - mouseX;
        var deltaY = event.clientY - mouseY;
        mouseX = event.clientX;
        mouseY = event.clientY;

        rotatePlanet(deltaX, deltaY);
    }

    function onMouseDown(event)
    {
        mouseDown = true;
        mouseX = event.clientX;
        mouseY = event.clientY;
    }

    function onMouseUp(event)
    {
        mouseDown = false;
    }

    function rotatePlanet(deltaX, deltaY)
    {
        planet.rotation.y += deltaX / 300;
        planet.rotation.x += deltaY / 300;
    }


    function mainLoop()
    {
        requestAnimationFrame(mainLoop);

        update();
        render();
    }

    function update()
    {
        //rotates the planet constantly
        planet.rotation.y += 0.0003;
    }

    function render()
    {
        renderer.render(scene, camera);
    }

    //changes the visiblity of certain element on the screen( div for instance)
    function setVisibility(id, visibility)
    {
        document.getElementById(id).style.visibility = visibility;
    }

    //basic client-side validation
    function validateForm()
    {
        var email          = document.forms["RegisterForm"]["email"].value;
        var username       = document.forms["RegisterForm"]["username"].value;
        var password       = document.forms["RegisterForm"]["password"].value;
        var repeatPassword = document.forms["RegisterForm"]["repeatPassword"].value;

        if (email=="" || username=="" || password=="" || repeatPassword=="")
        {
            alert("Fill all fields");
            return false;
        }

        if( password != repeatPassword )
        {
            alert("Passwords does not match");
            return false;
        }

        return true;
    }
</script>

<?php
 if(isset($_SESSION['login_user']))
 {
?>
    <form action="" method="post" id="Form2">
         <!--Logout button-->
        <div style="position:absolute;top:0;right:0;margin-top:50px;margin-right:100px;">
            <input name ="logout" type="submit" id ="logout" style="font-size:15pt;font-family:'Times New Roman'" value ="Logout"></button>
        </div>
    </form>

<?php
}
else
{
?>
     <!--Login button-->
    <div style="position:absolute;top:0;right:0;margin-top:50px;margin-right:100px;">
        <button name ="login" id ="login" style="font-size:15pt;font-family:'Times New Roman'" value ="Login" onclick="setVisibility('openLogin', 'visible');"> Login </button>
    </div>

    <!--Login dialog-->
    <div id="openLogin" class="loginDialog">
        <div>
            <a href="#openLogin" onclick="setVisibility('openLogin', 'hidden');" class="closeButtonLogin">X</a>

            <div style="position: relative; left: 10%;">
                <form action="" method="post" id="Form1">
                    <br /><br /><br />

                    <label style="font-size: 25pt; font-family: 'Times New Roman'">Login </label> <br /><br />

                    Username <br />
                    <input name="username" id="username" value="" style="font-size: 15pt; font-family: 'Times New Roman'" /><br /> <br />

                    Password<br />
                    <input name="password" type="password" id="password" value="" style="font-size: 15pt; font-family: 'Times New Roman'" /><br /><br />

                    <input type="submit" name="LoginSubmitButton" style="font-size: 15pt; font-family: 'Times New Roman'" value="Login" />
                </form>
            </div>
        </div>
    </div>

    <!--Register button-->
    <div style="position:absolute;top:0;right:0;margin-top:50px;margin-right:200px;">
        <button name ="register" id ="Button2" style="font-size:15pt;font-family:'Times New Roman'" onclick="setVisibility('openRegister', 'visible');"> Register </button>
    </div>

    <!--Register dialog-->
    <div id="openRegister" class="registerDialog">
        <div>
            <a href="#openRegister" onclick="setVisibility('openRegister', 'hidden');" title="Close" class="closeButtonRegister">X</a>

            <div style="position: relative; left: 13%;">
                <form action="" method="post" id="Form2" name="RegisterForm" onsubmit="return validateForm();">
                    <br /><br />

                    <label style="font-size: 25pt; font-family: 'Times New Roman'">Register</label><br /><br />

                    Email <br />
                    <input name="email" type="email" id="Text3" value="" style="font-size: 15pt; font-family: 'Times New Roman'" /><br /><br />

                    Username<br />
                    <input name="username" id="Text1" value="" style="font-size: 15pt; font-family: 'Times New Roman'" /> <br /><br />

                    Password
                    <br />
                    <input name="password" type="password" id="Text2" value="" style="font-size: 15pt; font-family: 'Times New Roman'" /><br /><br />

                    Repeat password<br />
                    <input name="repeatPassword" type="password" id="Text4" value="" style="font-size: 15pt; font-family: 'Times New Roman'" /><br /><br />

                    <input type="submit" name="RegisterSubmitButton"  style="font-size: 15pt; font-family: 'Times New Roman'" value="Register" />
                </form>
            </div>
        </div>
    </div>

<?php
}
?>

<?php
if(isset($_SESSION["login_user"]))
{
?>   

<!--2d mode button-->
<div style="position:absolute;bottom:0;left:0;margin-bottom:10px;margin-left:50px;">
    <form action="index2dMode.php">
        <input type="submit" id ="Submit2" style="font-size:15pt;font-family:'Times New Roman'" value ="Go to 2D mode">
    </form>
</div> 

<?php
}
?>
</body> 

</html>