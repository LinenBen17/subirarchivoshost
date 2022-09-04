<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
	<link rel="stylesheet" href="css/crearReclamos.css">
    <link rel="stylesheet" type="" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="js/sweetalert2.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
</head>
<body>
<?php
	ini_set('display_errors', '0');

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	require 'PHPMailer/src/Exception.php';
	require 'PHPMailer/src/PHPMailer.php';
	require 'PHPMailer/src/SMTP.php';

	$mysqli = new mysqli("localhost", "root", "", "sistem");

	if ($_POST) {
		//DATOS ENVIADOS POR POST
		$datosPost = array(
			"nombre" => $_POST['nombre_cliente'],
			"telefono" => $_POST['telefono_cliente'],
			"correo" => $_POST['correo_cliente'],
			"no_guia" => $_POST['numero_guia']
		);

		$primerNombre = explode(" ", $datosPost['nombre']);

		//DATOS ENVIADOS POR FILES
		$datosFiles = array(
			"foto_guia" => $_FILES['foto_guia'],
			"foto_producto" => $_FILES['foto_producto'],
			"carta" => $_FILES['carta_reclamo']
		);
		//VALIDACION DE SI ALGÚN CAMPO ESTÁ VACÍO
		if (!(empty($datosPost["nombre"]) OR empty($datosPost["telefono"]) OR empty($datosPost["correo"]) OR empty($datosPost["no_guia"]))) {
			//VALIDACIÓN DE SI FALTA ALGÚN ARCHIVO
			if (($datosFiles['foto_guia']['name'] AND $datosFiles['foto_producto']['name'] AND $datosFiles['carta']['name']) != NULL) {
				$sqlSelectId = "SELECT id FROM reclamos ORDER BY id DESC LIMIT 1";

				$selectLastId=$mysqli->prepare($sqlSelectId);
				$selectLastId->execute();

				$result = $selectLastId->get_result();
				$assoc = $result->fetch_assoc();

				$selectLastId->close();

				$contador = $assoc['id'] + 1;
				$directorioDestino = 'uploads/reclamo_' . $contador . '/';

				if (!file_exists($directorioDestino)) {
					mkdir($directorioDestino, 0777, true);
				}else{
					die("Hubo un error al realizar el reclamo.");
				}
				//INSERTO LOS ARCHIVOS A SU CARPETA CORRESPONDIENTE
				if (move_uploaded_file($datosFiles['foto_guia']['tmp_name'], $directorioDestino . $datosFiles['foto_guia']['name'])) {
					if (move_uploaded_file($datosFiles['carta']['tmp_name'], $directorioDestino . $datosFiles['carta']['name'])) {
						foreach ($datosFiles['foto_producto']['tmp_name'] as $key => $tmp_name) {
							if($_FILES["foto_producto"]["name"][$key]) {
								$fotoProducto = $_FILES["foto_producto"]["name"][$key]; //Obtenemos el nombre original del archivo
								$nombreTemporal = $_FILES["foto_producto"]["tmp_name"][$key]; //Obtenemos un nombre temporal del archivo

								if(move_uploaded_file($nombreTemporal, $directorioDestino . $fotoProducto)) {	
									//INSERTO LOS DATOS A LA DB
									$sql = "INSERT INTO `reclamos`(`nombre_cliente`, `telefono_cliente`, `correo_cliente`, `numero_guia`, `foto_guia`, `foto_producto`, `carta_reclamo`) VALUES (?, ?, ?, ?, ?, ?, ?)";

									$insertar_datos = $mysqli->prepare($sql);
									$insertar_datos->bind_param("sssisss", $datosPost['nombre'], $datosPost['telefono'], $datosPost['correo'], $datosPost['no_guia'], $datosFiles['foto_guia']['name'], $fotoProducto, $datosFiles['carta']['name']);
									$insertar_datos->execute();
									$insertar_datos->close();

									if ($tmp_name === end($datosFiles['foto_producto']['tmp_name'])) {
?>
										<script type="text/javascript">
								            Swal.fire({
								                title: "¡Felicitaciones!",
								                icon: "success",
								                text: "Su reclamo se ha realizado de manera existosa y en breve recibirá un correo con su número de reclamo. ¡Gracias por preferirnos!.",
								                type: "success",
								                confirmButtonColor:"#b50001",
								                confirmButtonText: "Entiendo"
								            }).then(function() {
								                window.location.href = "crearReclamos.html";
								            });
										</script>
<?php
										//ENVIO CORREO AL RECLAMANTE
										//Create an instance; passing `true` enables exceptions
										$mail = new PHPMailer(true);

										try {
										    //Server settings
										    $mail->SMTPDebug = 2;
										    $mail->isSMTP();                                            //Send using SMTP
										    $mail->Host       = 'smtp.hostinger.com';                     	//Set the SMTP server to send through
										    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
											$mail->Username = 'impresiondeguias@transportesjrgt.com';
											$mail->Password = 'Impresion1';                            //SMTP password
										    $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

										    //Recipients
										    $mail->setFrom('impresiondeguias@transportesjrgt.com', 'Aaron Esquite');
										    $mail->addAddress($datosPost['correo'], utf8_decode($primerNombre[0]));     //Add a recipient

										    //Content
										    $mail->isHTML(true);                                  //Set email format to HTML
										    $mail->Subject = 'Confirmación del reclamo:';
										    $mail->Body    = '<p style="font-size: 18px;">Su reclamo se ha realizado de manera exitosa, ' . $datosPost['nombre'] . '</p><p style="font-size: 18px;">Su <b>número de reclamo</b> es: ' . $contador . '</p><p style="font-size: 18px;">Se le estará atendiendo con la mayor brevedad posible.</p> <hr> <h1 style="color: #123B1A;">¡Gracias por preferirnos!</h1>';

										    $mail->send();
										    echo 'El mensaje ha sido envíado correctamente';
										} catch (Exception $e) {
										    echo "Hubo un error al enviar el mensaje: {$mail->ErrorInfo}";
										}
									}
								} else {	
									echo "Ha ocurrido un error, por favor inténtelo de nuevo.<br>";
								}
							}
						}
					}else{
?>
				<script type="text/javascript">
		            Swal.fire({
		                title: "¡Upps!",
		                icon: "error",
		                text: "Hubo un problema al subir la foto del producto",
		                type: "success",
		                confirmButtonColor:"#b50001",
		                confirmButtonText: "Entiendo"
		            }).then(function() {
		                window.location.href = "crearReclamos.html";
		            });
				</script>
<?php
						die();
					}
				}else{
?>
					<script type="text/javascript">
			            Swal.fire({
			                title: "¡Upps!",
			                icon: "error",
			                text: "Hubo un problema al subir la foto de guía.",
			                type: "success",
			                confirmButtonColor:"#b50001",
			                confirmButtonText: "Entiendo"
			            }).then(function() {
			                window.location.href = "crearReclamos.html";
			            });
					</script>
<?php
					die();
				}

			}else{
?>
				<script type="text/javascript">
		            Swal.fire({
		                title: "¡Upps!",
		                icon: "error",
		                text: "Hace falta algún archivo requerido. Asegurese de subir todo lo que se le pide.",
		                type: "success",
		                confirmButtonColor:"#b50001",
		                confirmButtonText: "Entiendo"
		            }).then(function() {
		                window.location.href = "crearReclamos.html";
		            });
				</script>
<?php
				die();
			}
		}else{
?>
			<script type="text/javascript">
	            Swal.fire({
	                title: "¡Upps!",
	                icon: "error",
	                text: "Algún campo está vacío. Asegurese de llenar todos los datos.",
	                type: "success",
	                confirmButtonColor:"#b50001",
	                confirmButtonText: "Entiendo"
	            }).then(function() {
	                window.location.href = "crearReclamos.html";
	            });
			</script>
<?php
			die();
		}
	}else{
?>
		<script type="text/javascript">
            Swal.fire({
                title: "¡Upps!",
                icon: "error",
                text: "No se está enviando ningún dato :/.",
                type: "success",
                confirmButtonColor:"#b50001",
                confirmButtonText: "Entiendo"
            }).then(function() {
                window.location.href = "crearReclamos.html";
            });
		</script>
<?php
		die();
	}
?>

</body>
</html>