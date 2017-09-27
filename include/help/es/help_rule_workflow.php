<h1>Reglas de flujo de trabajo</h1>
<p>
	Esta opción es Enterprise y permite definir reglas personalizadas que serán aplicadas en la gestión de tickets, es decir, 
	cuando un ticket que no está cerrado cumple alguna condición asociada a una regla, se ejecutan ciertas acciones definidas. 
	Por ejemplo: Cuando un ticket del grupo Soporte lleva 48 horas sin respuesta, se envía un email al usuario responsable informando de esta situación.
</p>
<p>
	Estas reglas sólo pueden ser definidas y gestionadas por usuarios administradores. Las reglas serán aplicadas una única vez.
	Una excepción a este comportamiento son las reglas que tienen en cuenta la fecha de actualización del ticket. 
	En este caso, la regla se lanzará siempre que corresponda.
</p>
<p>
	<strong >Gestión de reglas de workflow</strong>

	Las reglas tienen un campo descripción que nos ayudará a identificar su comportamiento. 
	A continuación se muestra el listado de las reglas creadas en el sistema.
	En este caso, tenemos una regla Añadir WU y enviar email a responsable cuando el ticket lleve 48hrs sin respuesta. 
</p>

<p>
	<?php print_image("images/help/workflow_rule.png", false); ?>
</p>

<p>
	Cada regla tiene condiciones asociadas que se deberán cumplir para ejecutar las acciones. 
	Si una regla tiene más de una condición, habrán de darse todas las condiciones para que se dispare la acción. 
	Para poder modificar la regla o acceder a sus condiciones y acciones, hay que hacer click sobre la descripción. 
	En la edición aparte de la descripción podremos elegir el modo de ejecución: 
</p>
<p>
<ul>
	<li><b>Cron:</b> chequeará cada x tiempo (por defecto 5 minutos) si se cumple las reglas del workflow.</li>
	<li><b>Realtime:</b> chequeará inmediatamente si se cumple las reglas del workflow.</li>
</ul>
</p>
<p>
	<?php print_image("images/help/workflow_rule_2.png", false); ?>
</p>
