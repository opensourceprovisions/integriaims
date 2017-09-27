<h1>Creación de un objeto de inventario</h1>
<p>.
	Para crear un nuevo objeto de inventario sólo tiene que pulsar en la opción Crear objeto de inventario del menú de la izquierda. Entonces verá el siguiente formulario con todas las opciones disponibles para configurar el objeto de inventario.
</p>
<p>
	<?php print_image("images/help/inventory7.png", false, false); ?>
</p>
<p>
	Los campos más importantes en este formulario son: <b>Propietario, Compañías asociadas, Usuarios asociados y Público.</b> Estos campos definen quién ve el objeto. De esta forma el objeto estará accesible por el propietario, los usuarios asociados directamente al objeto o los usuarios pertenecientes a una compañía asociada, además si se marca el flag Público todo el mundo podrá ver el objeto.
</p>
<p>
	Un objeto de inventario puede tener una “jerarquía” de objetos (un objeto puede ser hijo de otro). Esto se define al escoger un “padre”. Además de este parentesco padre/hijo, se pueden establecer relaciones entre objetos.
</p>
<p>
	El inventario de Integria IMS posee un sencillo sistema de control de stocks. Para gestionar el stock, todos los objetos de inventario tienen un campo “estado” que permite llevar un sistema de stock. Los estados posibles son: Nuevo, En uso, No usado o Dado de baja. También puede registrar la fecha de recepción y baja.
</p>
