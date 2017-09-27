<h1>Leads (Oportunidades)</h1>
<p>
	A través de la gestión de leads de Integria podemos hacer un seguimiento de posibles clientes. generalmente estos leads entran de forma “externa” (mediante API), aunque también se pueden crear manualmente desde el editor. Al igual que otros objetos de Integria, un Lead tiene un “propietario” que es la persona que lo gestiona
</p>
<p>
	El sistema permite ir anotando la actividad de ese lead, e ir modificando su estado, de forma que progrese desde un lead sin “clasificar” a una venta cerrada (o pérdida). Los leads se pueden reenviar (mail forward) o gestionar directamente por correo desde la herramienta. Si se hace desde el propio integria, se podrá gestionar el envío y la recepción de las respuestas por email, que quedarán reflejados en el seguimiento del lead, al llevar en CC la dirección de Integria, para que este pueda “capturar” el correo de respuesta del lead.
</p>
<p>
	<?php print_image("images/help/company17.png", false, false); ?>
</p>
<h2>Gestión de actividad de leads a través de emails</h2>
<p>
	Integria IMS permite gestionar la actividad comercial de los leads mediante emails. Esta funcionalidad Enterprise, le permitirá actualizar la conversación entre las partes y subir ficheros al lead de forma automática.
</p>
<p>
	Para ello se usa un buzón de correo electrónico como referencia del que Integria leerá los emails para extraer la información y los adjuntos. En la sección Configuración de e-mail puede consultar todos los detalles sobre la configuración del buzón de correo.
</p>
<p>
	Esta funcionalidad se usa por medio de la opción <b>Email de respuesta</b> disponible en los leads. Con esta opción Integría enviará un email añadiendo un token del tipo <b> [Lead#35] </b> al principio del subject del email, además al realizar el envío de emails se añadirá la dirección del buzón de referencia en el campo <b>Cc</b>.
</p>
<p>
	De esta forma, cuando un cliente o un comercial respondan al email, una copia llegará al buzón de referencia e Integria identificará el correo y lo procesará. Para actualizar el lead, se añadirá la información del cuerpo del email en al actividad del lead y los archivos adjuntos se subirán al servidor asociándose al lead correspondiente.
</p>
<p>
	<i>
		Es muy importante que tanto sus clientes como los comerciales utilicen la funcionalidad de <b> **Responder a todos** </b> de su cliente de correo para garantizar que una copia del email llega al buzón de referencia que está en el campo Cc
	</i>
</p>