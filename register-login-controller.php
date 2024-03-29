<?php

require_once 'conexion.php';
	// Inicio la sesión para tener acceso a $_SESSION en todos los archivos
	//session_start();

	// Definimos las constantes que necesitamos en nuestro proyecto, de esta manera puedo usar las mismas dentro de las funciones sin tener que usar una variable global o pasarla por parámetro

  define('ALLOWED_IMAGE_FORMATS', ['jpg', 'jpeg', 'png', 'gif']);
	define('IMAGE_PATH', dirname(__FILE__) . '/data/avatars/');
	define('USERS_JSON_PATH', dirname(__FILE__) . '/data/users.json');
	// Si está la cookie almacenada y si NO está logueda la persona:
	if ( isset($_COOKIE['userLoged']) && !isLogged() ) {
		// Busco al usuario por el email que tengo almacenado en la cookie
		$theUser = getUserByEmail($_COOKIE['userLoged']);
		// Guardo en sesión al usuario que bisqué anteiormente
		$_SESSION['userLoged'] = $theUser;
	}
	// Función para validar el Registro
	/*
		No le pasamos parámetros pues usamos las variables super globales $_POST y $FILES
	*/
	function registerValidate(){
		// Defino el array local de errores que voy a retornar
		$errors = [];
		// Definimos las variables locales que almacenan lo que nos llegó por $_POST y $_FILES
		$name = trim($_POST['name']);
		$userName = trim($_POST["userName"]);
		$email = trim($_POST['email']);
		$password = trim($_POST['password']);
		$rePassword = trim($_POST['rePassword']);
		$country = $_POST['country'];
		$avatar = $_FILES['avatar'];
		// Si está vació el campo: $name
		if ( empty($name) ) {
			$errors['name'] = 'El campo nombre no puede estar vacío';
		}
		// Si está vació el campo: $email
		if ( empty($email) ) {
			$errors['email'] = 'El campo email es obligatorio';
		} elseif ( !filter_var($email, FILTER_VALIDATE_EMAIL) ) { // Si el campo $email NO es un formato de email válido
			$errors['email'] = 'Introducí un formato de email válido';
		} elseif ( emailExist($email) ) { // Si el email ya existe, es porque alguien ya se registró con el mismo
			$errors['email'] = 'Ese correo ya está registrado';
		}
		if ( empty($userName) ) {
			$errors['userName'] = 'El campo nombre no puede estar vacío';
		}elseif ( userNameExist($userName) ) { // Si el email ya existe, es porque alguien ya se registró con el mismo
			$errors['userName'] = 'Ese nombre de usuario ya está registrado';
		}
		// Si está vació el campo: $password
		if ( empty($password) ) {
			$errors['password'] = 'El campo password es obligatorio';
		}
		elseif (strlen($password)<6) {
		$errors["password"] = "La contraseña debe tener al menos 6 caracteres";
			}
			elseif (strpos($password," ")) {
				$errors["password"] = "La contraseña no puede tener espacios";
			}
			elseif ( count(explode("DH", $password)) == 1 ) {
				$errors["password"] = "La contraseña debe incluir la sigla DH en mayuscula";
			}
		// Si está vació el campo: $rePassword
		if ( empty($rePassword) ) {
			$errors['rePassword'] = 'El campo repetir password es obligatorio';
		} elseif ($password != $rePassword) { // Si el valor de los campos $password y $rePassword son distintos
			$errors['password'] = 'Las credenciales no coinciden';
			$errors['rePassword'] = 'Las credenciales no coinciden';
		}
		// Si está vació el campo: $country
		if ( empty($country) ) {
			$errors['country'] = 'Elegí un país';
		}
		// Si no cargaron ningún archivo
		if ( $avatar['error'] != UPLOAD_ERR_OK ) {
			$errors['avatar'] = 'Subí una imagen';
		} else {
			// Si cargaron algún archivo, obtengo su extensión
			$ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
			// Si la extesión del archivo que cargaron NO está en mi array de formatos permitidos
			if ( !in_array($ext, ALLOWED_IMAGE_FORMATS) ) {
				$errors['avatar'] = 'Los formatos permitidos son JPG, PNG y GIF';
			}
		}
		// Finalmente retornamos el array de errores
		return $errors;
	}
	// Función para guardar la imagen
	/*
		No le pasamos parámetros, pues $_FILES es una variable global
	*/
	function saveImage() {
		// Obtengo la extensión del archivo
		$ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
		// Obtengo el archivo temporal
		$tempFile = $_FILES['avatar']['tmp_name'];
		// Armo el nombre de la imagen
		$finalName = uniqid('img_') . '.' . $ext;
		// Destino final (NO OLVIDAR DAR LOS PERMISOS A LA CARPETA EN NUESTRO D.D.)
		$finalPath = IMAGE_PATH . $finalName;
		// Guardamos la imagen en nuestra carpeta
		move_uploaded_file($tempFile, $finalPath);
		// Retorno el nombre de la imagen para poder guardar el mismo en el JSON
		return $finalName;
	}
	// Función para generar un ID
	// function generateID() {
	// 	// Traigo a todos los usuarios
	// 	$allUsers = getAllUsers();
	// 	// Si el conteo del array de usuarios es igual a cero
	// 	if ( count($allUsers) == 0 ) {
	// 		return 1;
	// 	}
	// 	// Si el conteo del array de usuarios es superior a cero, obtengo el último usuario registrado
	// 	$lastUser = array_pop($allUsers);
	// 	// Retorno el ID del último usuario registrado + 1
	// 	return $lastUser['id'] + 1;
	// }
	// Función traer todo del JSON
	function getAllUsers() {

	try {
	global $base;
	//quiero todos los datos de usuario de la tabla
	$consulta = $base->query("SELECT id, name, userName,avatar,email, country, password, rePassword from registro_de_usuario");
} catch(PDOException $error) {
	die('Se ha producido un error al procesar los datos');
}
$allUsers = $consulta->fetchAll(PDO::FETCH_ASSOC);
    // Obtengo el contenido del archivo JSON
		// $fileContent = file_get_contents(USERS_JSON_PATH);
		// // Decodifico el JSON a un array asociativo, importante el "true"
		// $allUsers = json_decode($fileContent, true);
		// Retorno el array de usuarios
		return $allUsers;
	}
	// Función para guardar al usuario
	function saveUser() {
		$name = trim($_POST['name']);
		$userName = trim($_POST["userName"]);
		$email = trim($_POST['email']);
		// Hasheo el password del usuario
		// $_POST['password'] = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
		$password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
		$rePassword = trim($_POST['rePassword']);
		$country = $_POST['country'];
		$avatar = $_POST['avatar'];
		// Trimeamos los valores que vinieron por $_POST
		// Genero el ID y lo guardo en una posición de $_POST llamada "id"
		// $_POST['id'] = generateID();
		// Elimino de $_POST la posición "rePassword" ya que no me interesa guardar este dato en mi DB (Data Base)
		unset($_POST['rePassword']);
		// En la variable $finalUser guardo el array de $_POST
		$finalUser = $_POST;
		// Obtengo todos los usuarios
		// $allUsers = getAllUsers();
		// En la última posición del array de usuarios, inserto al usuario nuevo
		// $allUsers[] = $finalUser;
		// Guardo todos los usuarios de vuelta en el JSON
		// file_put_contents(USERS_JSON_PATH, json_encode($allUsers));
		try {
	global $base;
	$consulta = $base->prepare("INSERT INTO registro_de_usuario (name,userName, avatar,country,email,password,repassword) values ( ?, ? , ? , ? , ? , ? , ?)");
	$consulta->execute([$name,$userName, $avatar,$country,$email,$password,$rePassword]);
} catch(PDOException $error) {
	die('Se ha producido un error al procesar los datos');
}
		// Retorno al usuario que acabo de guardar para poder tenerlo listo y loguearlo
		return $finalUser;
	}
	// Función para loguear al usuario
	/*
		Recibe como parámetro un array que contenga la información del usuario.
	*/
	function login($user) {
		// Del usuario que recibo saco la posición "password" pues no me interesa tenerla en sesión
		unset($user['password']);
		// Guardo en sesión al usuario que recibo por parámetro
		$_SESSION['userLoged'] = $user;
		// Redirecciono al perfil del usuario
		header('location: profile.php');
		exit; // Siempre, después de una redirección se recomienda hacer un exit.
	}
	// Función para saber si está logueado la/el usuaria/o
	function isLogged() {
		// El return devuelve true o false, según lo que retorne la función isset()
	 	return isset($_SESSION['userLoged']);
	}
	// Función para preguntar si el email existe
	/*
		Recibe como parámetro el email a buscar
	*/



function emailExist($email) {

	try {
	global $base;
	//quiero ver si en la tabla existe el email que ingresa por post
	$consulta = $base->prepare("SELECT email from registro_de_usuario where email = ?");
	$consulta->execute([$email]);
} catch(PDOException $error) {
	die('Se ha producido un error al procesar los datos');
}
$existEmail = $consulta->fetchAll(PDO::FETCH_ASSOC);
// si existe el email en la tabla
if (count ($existEmail)!=0){
		return true;
}
return false;
}
    // Obtengo el contenido del archivo JSON
		// $fileContent = file_get_contents(USERS_JSON_PATH);
		// // Decodifico el JSON a un array asociativo, importante el "true"
		// $allUsers = json_decode($fileContent, true);
		// Retorno el array de usuarios

	// 	// Traigo a todos los usuarios
	// 	$allUsers = getAllUsers();
	// 	// Recorro el array de usuarios
	//
	// 	if ($allUsers) {
	// 		foreach ($allUsers as $oneUser) {
	// 			// Si la posición "email" del usuario en la iteración coincide con el email que pasé como parámetro
	// 			if ($oneUser['email'] == $email) {
	// 				return true;
	// 			}
	// 		}
	// 		// code...
	// 	}
	//
	// 	// Si termino de recorrer el array y no se encontró al email que pasé como parámetro
	// 	return false;
	// }
	// Función para preguntar si el user name existe
	/*
		Recibe como parámetro el userName a buscar
	*/
	function userNameExist($userName) {

		try {
		global $base;
		//quiero ver si en la tabla existe el email que ingresa por post
		$consulta = $base->prepare("SELECT userName from registro_de_usuario where userName = ?");
		$consulta->execute([$userName]);
	} catch(PDOException $error) {
		die('Se ha producido un error al procesar los datos');
	}
	$existUserName = $consulta->fetchAll(PDO::FETCH_ASSOC);
	// si existe el email en la tabla
	if (count ($existUserName)!=0){
			return true;
	}
	return false;
	}
// 		// Traigo a todos los usuarios
// 		$allUsers = getAllUsers();
// 		// Recorro el array de usuariosif
// 		if ($allUsers) {
// 			// code...
// 			foreach ($allUsers as $oneUser) {
// 			// Si la posición "userName" del usuario en la iteración coincide con el email que pasé como parámetro
// 			if ($oneUser['userName'] == $userName) {
// 				return true;
// 			}
// 				}
// }
// 		// Si termino de recorrer el array y no se encontró al email que pasé como parámetro
// 		return false;
//
//
// }

	// Función para validar el login
	/*
		No le pasamos parámetros pues usamos la variables super global $_POST
	*/
	function loginValidate() {
		// Genero el array local de errores que retornaré al final
		$errors = [];
		// Trimeo los campos que recibo por $_POST
		$emailUserName = trim($_POST['emailUserName']);
		$password = trim($_POST['password']);
		// Si está vacío el campo: $emailUserName
		if ( empty($emailUserName) ) {
			$errors['emailUserName'] = 'El campo es obligatorio';
		} elseif ( !emailExist($emailUserName) && !userNameExist($emailUserName) ) { // Si no existe el email o el usuario
			// $errors['emailUserName'] = 'Ese correo o este usuario no está registrado en nuestra base de datos';
			$errors['emailUserName'] = 'El usuario no está registrado';
		} else {
			// Si el usuario o el usuario que pasaron corresponden, busco y  obtengo al usuario con el email que llegó por $_POST
			$theUser = getUserByEmail($emailUserName);
			// Si el password que recibí por $_POST NO coincide con el password hasheado que está guardado en el usuario
			if ( !password_verify($password, $theUser['password']) ) {
				$errors['password'] = 'Las credenciales no coinciden';
			}
		}
		// Si está vacío el campo: $password
		if ( empty($password) ) {
			$errors['password'] = 'El campo password es obligatorio';
		}
		// Retorno el array de errores con los mensajes de error
		return $errors;
	}
	// Función para traer a 1 usuario por email
	/*
		Recibe como parámetro el email que quiero buscar
	*/




	function getUserByEmail($emailUserName){
		//
		try {
		global $base;
		//quiero ver si en la tabla existe el email que ingresa por post
		$consulta = $base->prepare("SELECT id,name,userName, avatar,country,email,password,repassword from registro_de_usuario where email=? or userName = ?");
		$consulta->execute([$emailUserName,$emailUserName]);
	} catch(PDOException $error) {
		die('Se ha producido un error al procesar los datos');
	}
	$existemailUserName = $consulta->fetch(PDO::FETCH_ASSOC);
	// si existe el email en la tabla
	return $existemailUserName;

	}

	// 	// Obtengo a todos los usuarios
	// 	$allUsers = getAllUsers();
	//
	// 	// Recorro el array de usuarios
	// 	foreach ($allUsers as $oneUser) {
	// 		// Si la posición email del usuario de esa iteración es igual al email que me pasan por parámetro
	// 		if ($oneUser['email'] == $emailUserName or $oneUser['userName'] == $emailUserName) {
	// 			// Retorno al usuario encontrado
	// 			return $oneUser;
	// 		}
	// 	}
	// }
	// valido el formulario de edicion de perfil --- copio de registerValidate
	function editValidate(){
		// Defino el array local de errores que voy a retornar
		$errors = [];
		// Definimos las variables locales que almacenan lo que nos llegó por $_POST y $_FILES
		$name = trim($_POST['name']);
		$userName = trim($_POST["userName"]);
		$email = trim($_POST['email']);
		$country = $_POST['country'];
		$avatar = $_FILES['avatar'];
		// Si está vació el campo: $name
		if ( empty($name) ) {
			$errors['name'] = 'El campo nombre no puede estar vacío';
		}
		// Si está vació el campo: $email
		if ( empty($email) ) {
			$errors['email'] = 'El campo email es obligatorio';
		} elseif ( !filter_var($email, FILTER_VALIDATE_EMAIL) ) { // Si el campo $email NO es un formato de email válido
			$errors['email'] = 'Introducí un formato de email válido';
		} elseif ( emailExist($email)  ) { // Si el email ya existe, es porque alguien ya se registró con el mismo y
// si al editar el mail el nuevo mail es distinto del mail que el usuario en sesion tiene entonces
			if ($email != $_SESSION['userLoged']["email"]) {

			$errors['email'] = 'Ese correo ya está registrado';
		}
		}
		if ( empty($userName) ) {

			$errors['userName'] = 'El campo nombre no puede estar vacío';
		}
		elseif ( userNameExist($userName)  ) { // Si el email ya existe, es porque alguien ya se registró con el mismo y
	 // si al editar el mail el nuevo mail es distinto del mail que el usuario en sesion tiene entonces
	 	if ($userName != $_SESSION['userLoged']["userName"]) {

	 	$errors['userName'] = 'Ese nombre de usuario ya está registrado';
	 }
	 }
		// Si está vació el campo: $country
		if ( empty($country) ) {
			$errors['country'] = 'Elegí un país';
		}
		// Si no cargaron ningún archivo
		if ( $avatar['error'] != UPLOAD_ERR_OK ) {
			$errors['avatar'] = 'Subí una imagen';
		} else {
			// Si cargaron algún archivo, obtengo su extensión
			$ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
			// Si la extesión del archivo que cargaron NO está en mi array de formatos permitidos
			if ( !in_array($ext, ALLOWED_IMAGE_FORMATS) ) {
				$errors['avatar'] = 'Los formatos permitidos son JPG, PNG y GIF';
			}
		}
		// Finalmente retornamos el array de errores
		return $errors;
	}

	// funcion para editar usuario , copio saveUser
	function editUser() {
		$name = trim($_POST['name']);
		$userName = trim($_POST["userName"]);
		$email = trim($_POST['email']);
		$country = $_POST['country'];

		// Trimeamos los valores que vinieron por $_POST de los campos editables
		$_POST['name'] = trim($_POST['name']);
		$_POST['userName'] = trim($_POST['userName']);
		$_POST['email'] = trim($_POST['email']);

		// Genero el ID igual al id del usuario logueado en sesion
		$_POST['id'] = $_SESSION["userLoged"]["id"];

		// En la variable $finalUser guardo el array de $_POST
		$finalUser = $_POST;
		// Obtengo todos los usuarios
		// $allUsers = getAllUsers();
		// la posicion del array de usuarios, inserto al usuario nuevo( la posicion la tomo de id-1 ya que id era = a la posicion +1)
		// $finalUser['password'] = $allUsers[$_POST["id"]-1]['password'];
		// $allUsers[$_POST["id"]-1] = $finalUser;
		// Guardo todos los usuarios de vuelta en el JSON
		// file_put_contents(USERS_JSON_PATH, json_encode($allUsers));
		// Retorno al usuario que acabo de guardar para poder tenerlo listo
		try {
	global $base;
	$consulta = $base->prepare("UPDATE registro_de_usuario set name =?,userName=?,email=? ,country =? where id=?");
	$consulta->execute([$name,$userName,$email,$country,$_POST["id"]]);
} catch(PDOException $error) {
	die('Se ha producido un error al procesar los datos');
}
		return $finalUser;
	}
	// Función para hacer debug
	/*
		Esta función es solamente para debugear nuestro código cada vez que lo necesitemos
		Recibe como parámetro el dato que quiero debugear y lo muestra de manera legible gracias a la etiqueta <pre>
		Finaliza con un exit para que no se siga ejecutando el código
	*/
	function debug($dato) {
		echo "<pre>";
		var_dump($dato);
		echo "</pre>";
		exit;
	}
