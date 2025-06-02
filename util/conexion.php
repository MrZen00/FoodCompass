<?php
    $_servidor = "localhost:3306";
    $_usuario = "test";
    $_contrasena = "Sandro.1?";
    $_base_de_datos = "foodcomp_";

    $_conexion = new mysqli($_servidor,$_usuario,$_contrasena,$_base_de_datos)
        or die("Error de conexión: " . $_conexion->connect_error);
?>