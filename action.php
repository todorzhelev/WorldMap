<html>

<head>

</head>

<body>

<?php

	if( $_POST["email"] && $_POST["username"] && $_POST["password"] )
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
		$password = $_POST["password"];
		$email = $_POST["email"];
		$stmt->execute();
	}
	
?>

</body>
</html>