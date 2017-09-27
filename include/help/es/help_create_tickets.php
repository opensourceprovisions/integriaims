<h1>Creación de un ticket</h1>
<p>
	Crear un ticket no es tan simple. Para llegar a este paso hemos debido ser capaces de configurar y entender los siguientes aspectos:
	</br>
	</br>
	<ul>
		<li> <b>Qué es un usuario en Integria.</b>
			<ul>
				<li>Tipo de usuario (Externo, Normal)</li>
			</ul>
		</li>
		<li> <b>Qué es un grupo en Integria.</b>
			<ul>
				<li>Qué relación tiene el grupo con la visibilidad de ese usuario al resto de cosas de Integria.</li>
				<li>Qué relación hay entre el grupo, el perfil de acceso del usuario y el tipo de usuario que es (externo, normal)</li>
			</ul>
		</li>
		<li> <b>El inventario:</b>
			<ul>
				<li>Contrato</li>
				<li>SLA</li>
			</ul>
		</li>
	</ul>
</p>
<p>
	Una vez, que suponemos que todos estos conceptos están claros, crear un ticket será algo muy sencillo. Hay que tener en cuenta -como repaso- que las siguientes propiedades alteran el comportamiento de Integria a la hora de crear un ticket:
	</br>
	</br>
	<ul>
		<li><b>Usuario externo:</b> Los usuarios externos sólo podrán ver sus propios informes, así que el concepto de grupo, perfil y demás no tiene tanta relevancia para este tipo de usuarios. Los usuarios externos no pueden cambiar algunas propiedades del ticket que se configuran por defecto, tales como usuario asignado, u objeto por defecto, ya que van asociados al grupo.</li>
		<li><b>Usuario normal:</b> De igual manera que el anterior, no pueden cambiar algunas propiedades del ticket que se configuran por defecto, tales como usuario asignado, u objeto por defecto, ya que van asociados al grupo. Sin embargo un usuario con perfiles de “gestión” (IM) podrá cambiar aquí el estado del ticket, o incluso campos como el “creador original” (para que no sea el mismo), u otros aspectos.</li>
	</ul>
</p>
<p>
	<b>Por defecto un ticket nuevo, recién creado, está en estado “Nuevo”.</b>
</p>
<p>
	Existen algunos campos, asignados automáticamente como “SLA Desactivada” o “Notificación automática por email”, que en principio van ligados a los valores que tiene ese grupo por defecto (el grupo al que se le haya asignado el ticket). Esos valores, si se tienen permisos de gestor (IM), se pueden alterar. De lo contrario irán por defecto.
</p>

<h1>Limitación de tickets</h1>
<p>
	Al crear un ticket, existen dos valores definidos en un grupo, tal y como se vio en el capítulo de usuarios y grupos que permiten definir cuantos tickets de un grupo puede haber (abiertos o cerrados) para cada usuario (en total) y cuantos tickets puede haber abiertos (en estado no cerrado) para un usuario dado de este grupo. Un recordatorio de donde se configura ese comportamiento:
</p>
<p>
	<?php print_image("images/help/user17.png", false, false); ?>
</p>

<p>
	Si se sobrepasan estos valores, una ventana de advertencia se mostrará en Integria y no podremos crear el ticket, tal y como se puede ver en esta captura:
</p>
<p>
	<?php print_image("images/help/ticket17.png", false, false); ?>
</p>

<h1>Vista de un usuario normal al crear un ticket:</h1>
<p>
	<?php print_image("images/help/ticket18.png", false, false); ?>
</p>

<h1>Vista de un gestor al crear (o modificar) un ticket:</h1>
<p>
	<?php print_image("images/help/ticket19.png", false, false); ?>
</p>

<h1>Creación de la primera UT</h1>
<p>
	Bien, el ticket ha sido creado por un usuario (llamémosle A) y asignado a otro usuario (llamémosle B). ¿Ahora qué?.
</p>
<p>
	Al crear el ticket, el sistema habrá enviado un email al usuario A y otro al usuario B, informando sobre la creación del ticket. Al usuario A, este mensaje le sirve como confirmación de que el ticket ha sido registrado por el sistema.
</p>
<p>
	El usuario B, puede contestar ese mismo correo, que lleva un código especial y enviar su primera UT (Unidad de Trabajo), algo así como “He recibido el ticket” o para añadir información adicional al ticket por ejemplo. También puede conectarse al sistema, con la URL suministrada para poder agregar manualmente la UT a el ticket.
</p>
<p>
	En el momento que el ticket en estado “Nuevo” recibe una UT, automáticamente se cambia su estado a “Asignado”, es una forma de decir “se ha empezado a trabajar” con el ticket.
</p>